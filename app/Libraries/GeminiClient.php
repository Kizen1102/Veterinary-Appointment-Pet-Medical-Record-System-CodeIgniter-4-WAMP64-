<?php

namespace App\Libraries;

use Config\AI;
use Throwable;

/**
 * Sends one request to Google Gemini (generateContent REST API) for VetAssistant.
 * Returns the text, the decoded JSON (when a schema is given), or null on any failure
 * so VetAssistant can fall back to the offline rules.
 */
class GeminiClient
{
    private const URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function __construct(private AI $config)
    {
    }

    /**
     * @param list<array{role: string, content: string}> $messages Oldest first; role "user" or "assistant"
     * @param array<string, mixed>|null                  $schema   JSON Schema of the answer, or null for plain text
     *
     * @return array<string, mixed>|string|null
     */
    public function generate(string $system, array $messages, ?array $schema = null): array|string|null
    {
        $generation = ['maxOutputTokens' => max($this->config->maxTokens, 8192)];

        if ($schema !== null) {
            // JSON mode, with the expected fields described in the instructions
            $generation['responseMimeType'] = 'application/json';
            $system .= "\n\nReply with one JSON object only, matching this JSON Schema:\n"
                . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $body = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents'          => array_map(static fn ($m) => [
                'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) $m['content']]],
            ], $messages),
            'generationConfig'  => $generation,
        ];

        try {
            $response = service('curlrequest')->post(sprintf(self::URL, rawurlencode($this->config->model)), [
                'headers'     => ['x-goog-api-key' => $this->config->apiKey, 'Content-Type' => 'application/json'],
                'body'        => json_encode($body, JSON_UNESCAPED_UNICODE),
                'timeout'     => $this->config->timeout,
                'http_errors' => false,
            ]);
        } catch (Throwable $e) {
            log_message('error', 'VetAssistant Gemini error: {message}', ['message' => $e->getMessage()]);

            return null;
        }

        $data = json_decode((string) $response->getBody(), true);

        if ($response->getStatusCode() !== 200 || ! is_array($data)) {
            // Only Google's error message is logged, never the key
            $reason = is_array($data) ? ($data['error']['message'] ?? 'unknown error') : 'not JSON';
            log_message('error', 'VetAssistant Gemini API error {status}: {message}', ['status' => $response->getStatusCode(), 'message' => $reason]);

            return null;
        }

        $candidate = $data['candidates'][0] ?? null;
        $finish    = $candidate['finishReason'] ?? 'none';

        if ($candidate === null || ! in_array($finish, ['STOP', 'FINISH_REASON_UNSPECIFIED'], true)) {
            // SAFETY, MAX_TOKENS, blocked prompt, ...
            log_message('warning', 'VetAssistant Gemini stopped early: {reason}', ['reason' => $data['promptFeedback']['blockReason'] ?? $finish]);

            return null;
        }

        $text = '';
        foreach ($candidate['content']['parts'] ?? [] as $part) {
            if (empty($part['thought']) && isset($part['text'])) {
                $text .= $part['text'];
            }
        }
        $text = trim($text);

        if ($schema === null) {
            return $text;
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }
}
