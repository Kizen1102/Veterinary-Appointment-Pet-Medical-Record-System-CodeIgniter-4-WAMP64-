<?php

use Anthropic\Client;
use App\Libraries\VetAssistant;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AI;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Exercises the real Anthropic SDK request/response path against a mocked HTTP transport.
 *
 * @internal
 */
final class VetAssistantTest extends CIUnitTestCase
{
    /** @var list<array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    private function assistant(Response ...$responses): VetAssistant
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $config         = new AI();
        $config->apiKey = 'test-key';
        $config->model  = 'claude-opus-5-5';

        $client = new Client(apiKey: 'test-key', requestOptions: [
            'transporter' => new HttpClient(['handler' => $stack]),
            'maxRetries'  => 0,
        ]);

        return new VetAssistant($config, $client);
    }

    private static function message(string $text, string $stopReason = 'end_turn'): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id'            => 'msg_test',
            'type'          => 'message',
            'role'          => 'assistant',
            'model'         => 'claude-opus-5-5',
            'content'       => [['type' => 'text', 'text' => $text]],
            'stop_reason'   => $stopReason,
            'stop_sequence' => null,
            'usage'         => ['input_tokens' => 50, 'output_tokens' => 40],
        ]));
    }

    public function testTriageUsesStructuredOutputFromClaude(): void
    {
        $assistant = $this->assistant(self::message(json_encode([
            'level'     => 'high',
            'summary'   => 'Repeated vomiting with lethargy.',
            'advice'    => 'Please bring her in today.',
            'red_flags' => ['lethargy'],
        ])));

        $result = $assistant->triage('Vomited 4 times and very tired', ['name' => 'Muning', 'species' => 'Cat', 'allergies' => 'Penicillin']);

        $this->assertSame('high', $result['level']);
        $this->assertSame('ai', $result['source']);
        $this->assertSame(['lethargy'], $result['red_flags']);

        $request = $this->history[0]['request'];
        $body    = json_decode((string) $request->getBody(), true);

        $this->assertStringEndsWith('/v1/messages', $request->getUri()->getPath());
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $request->getHeaderLine('anthropic-beta'));
        $this->assertSame('claude-opus-5-5', $body['model']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertSame('json_schema', $body['output_config']['format']['type']);
        $this->assertSame(VetAssistant::LEVELS, $body['output_config']['format']['schema']['properties']['level']['enum']);
        $this->assertStringContainsString('Penicillin', $body['messages'][0]['content']);
    }

    public function testRefusalFallsBackToRules(): void
    {
        $result = $this->assistant(self::message('', 'refusal'))->triage('seizure and collapse');

        $this->assertSame('rules', $result['source']);
        $this->assertSame('emergency', $result['level']);
    }

    public function testApiErrorFallsBackToRules(): void
    {
        $error  = new Response(500, ['Content-Type' => 'application/json'], '{"type":"error","error":{"type":"api_error","message":"boom"}}');
        $result = $this->assistant($error)->triage('mild itching');

        $this->assertSame('rules', $result['source']);
    }

    public function testRecordSummaryReturnsClaudeText(): void
    {
        $summary = $this->assistant(self::message('Bantay has a mild ear infection.'))
            ->summarizeRecord(['visit_date' => '2026-09-01', 'diagnosis' => 'Otitis externa'], ['name' => 'Bantay', 'species' => 'Dog']);

        $this->assertSame(['text' => 'Bantay has a mild ear infection.', 'source' => 'ai'], $summary);
    }

    public function testWithoutApiKeyUsesTemplate(): void
    {
        $config         = new AI();
        $config->apiKey = '';

        $summary = (new VetAssistant($config))->summarizeRecord(
            ['visit_date' => '2026-09-01', 'diagnosis' => 'Otitis externa', 'follow_up_date' => '2026-09-15'],
            ['name' => 'Bantay'],
        );

        $this->assertSame('template', $summary['source']);
        $this->assertStringContainsString('September 15, 2026', $summary['text']);
    }

    public function testJournalSummaryUsesStructuredOutputFromClaude(): void
    {
        $assistant = $this->assistant(self::message(json_encode([
            'summary'         => 'Appetite dropped over the last 3 days.',
            'notable_changes' => ['Appetite 4 → 2', 'Vomited once'],
            'concern_level'   => 'moderate',
        ])));

        $entries = [
            ['entry_date' => '2026-09-28', 'appetite_score' => 4, 'activity_score' => 4, 'mood' => 'happy', 'vomited' => 0, 'symptoms' => null],
            ['entry_date' => '2026-09-30', 'appetite_score' => 2, 'activity_score' => 3, 'mood' => 'lethargic', 'vomited' => 1, 'symptoms' => 'Ate half her food'],
        ];
        $result = $assistant->summarizeJournal($entries, ['name' => 'Luna', 'species' => 'Cat']);

        $this->assertSame('moderate', $result['concern_level']);
        $this->assertSame('ai', $result['source']);
        $this->assertSame(['Appetite 4 → 2', 'Vomited once'], $result['notable_changes']);

        $body = json_decode((string) $this->history[0]['request']->getBody(), true);
        $this->assertSame(VetAssistant::CONCERN_LEVELS, $body['output_config']['format']['schema']['properties']['concern_level']['enum']);
        $this->assertStringContainsString('Ate half her food', $body['messages'][0]['content']);
        $this->assertStringContainsString('appetite: 2', $body['messages'][0]['content']);
    }

    public function testJournalSummaryFallsBackToRules(): void
    {
        $config         = new AI();
        $config->apiKey = '';

        $entries = [
            ['entry_date' => '2026-09-27', 'appetite_score' => 4, 'activity_score' => 4, 'mood' => 'happy', 'water_intake' => 'normal', 'bowel_movement' => 'normal', 'vomited' => 0, 'weight_kg' => '4.20', 'symptoms' => null],
            ['entry_date' => '2026-09-28', 'appetite_score' => 4, 'activity_score' => 4, 'mood' => 'calm', 'water_intake' => 'normal', 'bowel_movement' => 'normal', 'vomited' => 0, 'weight_kg' => null, 'symptoms' => null],
            ['entry_date' => '2026-09-29', 'appetite_score' => 2, 'activity_score' => 3, 'mood' => 'lethargic', 'water_intake' => 'less', 'bowel_movement' => 'diarrhea', 'vomited' => 1, 'weight_kg' => null, 'symptoms' => null],
            ['entry_date' => '2026-09-30', 'appetite_score' => 1, 'activity_score' => 3, 'mood' => 'withdrawn', 'water_intake' => 'less', 'bowel_movement' => 'bloody', 'vomited' => 1, 'weight_kg' => '3.90', 'symptoms' => 'Hiding all day'],
        ];
        $result = (new VetAssistant($config))->summarizeJournal($entries, ['name' => 'Luna']);

        $this->assertSame('rules', $result['source']);
        $this->assertSame('high', $result['concern_level']);
        $this->assertStringContainsString('Luna has 4 journal entries', $result['summary']);
        $this->assertContains('Appetite dropped from about 4.0 to 1.5 (out of 5).', $result['notable_changes']);
        $this->assertContains('Blood in the stool on 1 day(s).', $result['notable_changes']);
        $this->assertContains('Owner noted: "Hiding all day"', $result['notable_changes']);
    }

    public function testStableJournalHasNoConcern(): void
    {
        $config         = new AI();
        $config->apiKey = '';

        $entry   = ['appetite_score' => 4, 'activity_score' => 4, 'mood' => 'happy', 'water_intake' => 'normal', 'bowel_movement' => 'normal', 'vomited' => 0, 'symptoms' => null];
        $entries = [['entry_date' => '2026-09-29'] + $entry, ['entry_date' => '2026-09-30'] + $entry];
        $result  = (new VetAssistant($config))->summarizeJournal($entries, ['name' => 'Bantay']);

        $this->assertSame('none', $result['concern_level']);
        $this->assertSame([], $result['notable_changes']);
    }

    public function testChatSendsTheWholeConversation(): void
    {
        $assistant = $this->assistant(self::message('BID means twice a day, about every 12 hours.'));

        $history = [
            ['sender' => 'user', 'content' => 'What is otitis?'],
            ['sender' => 'assistant', 'content' => 'It is an ear infection.'],
            ['sender' => 'user', 'content' => 'And what does BID mean?'],
        ];
        $records = [['visit_date' => '2026-09-01', 'title' => 'Ear check', 'diagnosis' => 'Otitis externa', 'treatment' => 'Ear drops BID']];

        $reply = $assistant->chat($history, ['name' => 'Bantay', 'species' => 'Dog'], $records);

        $this->assertSame(['text' => 'BID means twice a day, about every 12 hours.', 'source' => 'ai'], $reply);

        $body = json_decode((string) $this->history[0]['request']->getBody(), true);
        $this->assertSame(['user', 'assistant', 'user'], array_column($body['messages'], 'role'));
        $this->assertSame('And what does BID mean?', $body['messages'][2]['content']);
        $this->assertStringContainsString('Otitis externa', $body['system']);
        $this->assertArrayNotHasKey('format', $body['output_config']);
    }

    public function testChatWithoutApiKeyUsesGlossary(): void
    {
        $config         = new AI();
        $config->apiKey = '';

        $reply = (new VetAssistant($config))->chat(
            [['sender' => 'user', 'content' => 'The vet said otitis externa, give drops BID. Is it poison?']],
            ['name' => 'Bantay'],
        );

        $this->assertSame('glossary', $reply['source']);
        $this->assertStringContainsString('Otitis externa: an infection', $reply['text']);
        $this->assertStringContainsString('BID: twice a day', $reply['text']);
        $this->assertStringNotContainsString('Otitis: ', $reply['text']); // covered by the longer term
        $this->assertStringNotContainsString('PO: ', $reply['text']);     // "po" inside "poison" is not a match
    }
}
