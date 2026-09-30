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
}
