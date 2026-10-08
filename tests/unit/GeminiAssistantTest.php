<?php

use App\Libraries\VetAssistant;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCURLRequest;
use Config\AI;
use Config\App;

/**
 * VetAssistant with Google Gemini as the provider, against a fake HTTP connection
 * (nothing is sent to Google).
 *
 * @internal
 */
final class GeminiAssistantTest extends CIUnitTestCase
{
    private MockCURLRequest $http;

    private function assistant(int $status, array $body): VetAssistant
    {
        $this->http = new MockCURLRequest(new App(), new URI('https://generativelanguage.googleapis.com/'), new Response(new App()));
        $this->http->setOutput("HTTP/1.1 {$status} OK\r\nContent-Type: application/json\r\n\r\n" . json_encode($body));
        Services::injectMock('curlrequest', $this->http);

        $config           = new AI();
        $config->provider = 'gemini';
        $config->apiKey   = 'gem-test-key';
        $config->model    = 'gemini-flash-latest';

        return new VetAssistant($config);
    }

    private static function answer(string $text, string $finish = 'STOP'): array
    {
        return ['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => $text]]], 'finishReason' => $finish]]];
    }

    /** What was sent: URL, headers and the JSON body. */
    private function sent(): array
    {
        $options = $this->http->curl_options;

        return [$options[CURLOPT_URL], implode("\n", $options[CURLOPT_HTTPHEADER]), json_decode($options[CURLOPT_POSTFIELDS], true)];
    }

    public function testTriageReadsGeminiJson(): void
    {
        $assistant = $this->assistant(200, self::answer(json_encode([
            'level' => 'emergency', 'summary' => 'Possible chocolate poisoning.', 'advice' => 'Go to the clinic now.', 'red_flags' => ['chocolate'],
        ])));

        $result = $assistant->triage('He ate chocolate and is shaking', ['species' => 'Dog', 'name' => 'Shiro']);

        $this->assertSame('emergency', $result['level']);
        $this->assertSame('ai', $result['source']);

        [$url, $headers, $body] = $this->sent();
        $this->assertStringContainsString('/models/gemini-flash-latest:generateContent', $url);
        $this->assertStringContainsString('x-goog-api-key: gem-test-key', $headers);
        $this->assertSame('application/json', $body['generationConfig']['responseMimeType']);
        $this->assertStringContainsString('JSON Schema', $body['systemInstruction']['parts'][0]['text']);
        $this->assertStringContainsString('chocolate', $body['contents'][0]['parts'][0]['text']);
    }

    public function testChatSendsTheConversationWithGeminiRoles(): void
    {
        $assistant = $this->assistant(200, self::answer('Otitis externa is an infection of the outer ear canal.'));

        $result = $assistant->chat([
            ['sender' => 'user', 'content' => 'Hi'],
            ['sender' => 'assistant', 'content' => 'Hello! How can I help?'],
            ['sender' => 'user', 'content' => 'What is otitis externa?'],
        ]);

        $this->assertSame('ai', $result['source']);
        $this->assertStringContainsString('outer ear', $result['text']);
        $this->assertSame(['user', 'model', 'user'], array_column($this->sent()[2]['contents'], 'role'));
    }

    public function testErrorsFallBackToTheOfflineRules(): void
    {
        $assistant = $this->assistant(400, ['error' => ['code' => 400, 'message' => 'API key not valid.']]);
        $this->assertSame('rules', $assistant->triage('He ate chocolate and is shaking')['source']);

        $blocked = $this->assistant(200, self::answer('', 'SAFETY'));
        $this->assertSame('glossary', $blocked->chat([['sender' => 'user', 'content' => 'What is otitis externa?']])['source']);
    }

    public function testGeminiIsChosenWhenOnlyItsKeyIsSet(): void
    {
        $saved = [$_ENV['ANTHROPIC_API_KEY'] ?? null, $_ENV['GEMINI_API_KEY'] ?? null, $_ENV['AI_PROVIDER'] ?? null];
        $_ENV['ANTHROPIC_API_KEY'] = '';
        $_ENV['GEMINI_API_KEY']    = 'gem-test-key';
        unset($_ENV['AI_PROVIDER']);

        try {
            $config = new AI();
            $this->assertSame('gemini', $config->provider);
            $this->assertSame('gem-test-key', $config->apiKey);
        } finally {
            foreach (['ANTHROPIC_API_KEY', 'GEMINI_API_KEY', 'AI_PROVIDER'] as $i => $name) {
                if ($saved[$i] === null) {
                    unset($_ENV[$name]);
                } else {
                    $_ENV[$name] = $saved[$i];
                }
            }
        }
    }

    public function testABusyModelIsRetriedThenTheLighterModelIsUsed(): void
    {
        $busy = "HTTP/1.1 503 Service Unavailable\r\nContent-Type: application/json\r\n\r\n"
            . json_encode(['error' => ['code' => 503, 'message' => 'This model is currently experiencing high demand.']]);
        $ok = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n" . json_encode(self::answer('BID means twice a day.'));

        // A fake connection that answers busy, busy, then OK, and remembers each URL
        $http = new class (new App(), new URI('https://generativelanguage.googleapis.com/'), new Response(new App())) extends MockCURLRequest {
            public array $outputs = [];
            public array $urls    = [];

            protected function sendRequest(array $curlOptions = []): string
            {
                $this->response = clone $this->responseOrig;
                $this->urls[]   = $curlOptions[CURLOPT_URL];

                return array_shift($this->outputs);
            }
        };
        $http->outputs = [$busy, $busy, $ok];
        Services::injectMock('curlrequest', $http);

        $config                = new AI();
        $config->provider      = 'gemini';
        $config->apiKey        = 'gem-test-key';
        $config->model         = 'gemini-flash-latest';
        $config->fallbackModel = 'gemini-flash-lite-latest';

        $result = (new VetAssistant($config))->chat([['sender' => 'user', 'content' => 'What does BID mean?']]);

        $this->assertSame('ai', $result['source']);
        $this->assertCount(3, $http->urls);
        $this->assertStringContainsString('/models/gemini-flash-latest:', $http->urls[1]);
        $this->assertStringContainsString('/models/gemini-flash-lite-latest:', $http->urls[2]);
    }
}
