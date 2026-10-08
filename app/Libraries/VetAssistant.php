<?php

namespace App\Libraries;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use Config\AI;
use Throwable;

/**
 * AI helper for the clinic, powered by Claude or Google Gemini (see Config\AI).
 *
 * - triage():          urgency level + advice from an owner's symptom description
 * - summarizeRecord(): plain-language explanation of a medical record for the owner
 * - summarizeJournal(): summary of the Symptom & Behavior Journal for the veterinarian
 * - chat():            AI Medical Chatbot that explains veterinary terms in plain language
 *
 * Every method degrades gracefully: without an API key, or if the API call fails,
 * a rule-based / template result is returned instead so the app keeps working.
 */
class VetAssistant
{
    public const LEVELS = ['low', 'medium', 'high', 'emergency'];

    private const TRIAGE_SYSTEM = <<<'TXT'
        You are a veterinary triage assistant for a small-animal clinic. Clinic staff use your
        assessment to prioritise appointment requests, and pet owners see your advice.

        Given a pet's details and the owner's description of the problem, classify urgency:
        - emergency: potentially life-threatening, needs immediate care (e.g. breathing difficulty,
          seizures, collapse, suspected poisoning, bloat, trauma, urinary blockage in cats)
        - high: should be seen today
        - medium: should be seen within 1-3 days
        - low: routine or preventive (check-ups, vaccines, mild stable issues)

        When unsure between two levels, choose the more urgent one. Take species, age and known
        allergies into account. Write for a worried pet owner: calm, clear, and short. Do not
        give a diagnosis or medication doses; recommend seeing the veterinarian instead.
        TXT;

    private const SUMMARY_SYSTEM = <<<'TXT'
        You explain veterinary medical records to pet owners. Rewrite the record in plain,
        friendly language (no jargon, or explain it briefly) in at most 150 words: what was
        found, what was done, what the owner should do at home, and any follow-up date.
        Only use information present in the record; do not add diagnoses or doses.
        TXT;

    public const CONCERN_LEVELS = ['none', 'low', 'moderate', 'high'];

    private const JOURNAL_SYSTEM = <<<'TXT'
        You help veterinarians prepare for a visit by reading a pet owner's daily health journal.
        Summarise how the pet has been over the period: trends in appetite, activity, mood, sleep,
        water intake, stool, vomiting and weight, and any symptoms the owner wrote down. Point out
        changes over time (for example "appetite dropped from 4 to 2 over the last 3 days") and
        anything the vet should ask about. Only use what is in the journal; do not diagnose.

        concern_level: none = stable and normal; low = minor changes worth mentioning;
        moderate = clear changes the vet should check at the next visit; high = signs that need
        prompt attention (for example blood in stool, repeated vomiting, not eating for days).
        TXT;

    private const CHAT_SYSTEM = <<<'TXT'
        You are PawDoc, the AI Medical Information Chatbot of a veterinary clinic. Pet owners ask
        you what veterinary words, test results, diagnoses and instructions in their pet's records
        mean. Explain in plain, friendly language that a non-medical person understands, in short
        paragraphs (usually under 150 words). Explain a medical word the first time you use it.

        You give information, not veterinary care: do not diagnose new problems, do not change or
        suggest medicine doses, and say when a question needs the veterinarian. If the owner
        describes signs of an emergency (trouble breathing, seizures, collapse, poisoning, heavy
        bleeding, a swollen hard belly, not peeing), tell them to contact the clinic or an
        emergency vet right away. Use the pet details and records below when they are relevant.
        Plain text only, no Markdown headings or tables.
        TXT;

    private AI $config;
    private ?Client $client = null;

    public function __construct(?AI $config = null, ?Client $client = null)
    {
        $this->config = $config ?? config(AI::class);
        $this->client = $client;
    }

    public function isEnabled(): bool
    {
        return $this->config->apiKey !== '';
    }

    /**
     * @param array<string, mixed> $pet Row from the pets table (optional fields may be missing)
     *
     * @return array{level: string, summary: string, advice: string, red_flags: list<string>, source: string}
     */
    public function triage(string $symptoms, array $pet = []): array
    {
        if (! $this->isEnabled()) {
            return (new RuleBasedTriage())->assess($symptoms);
        }

        $schema = [
            'type'       => 'object',
            'properties' => [
                'level'     => ['type' => 'string', 'enum' => self::LEVELS],
                'summary'   => ['type' => 'string', 'description' => 'One or two sentences for clinic staff.'],
                'advice'    => ['type' => 'string', 'description' => 'What the owner should do now, 2-4 sentences.'],
                'red_flags' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Warning signs found in the description.'],
            ],
            'required'             => ['level', 'summary', 'advice', 'red_flags'],
            'additionalProperties' => false,
        ];

        $prompt = "Pet details:\n" . $this->describePet($pet)
            . "\n\nOwner's description:\n<description>\n" . trim($symptoms) . "\n</description>";

        $result = $this->ask(self::TRIAGE_SYSTEM, $prompt, $schema);

        if (! is_array($result) || ! in_array($result['level'] ?? null, self::LEVELS, true)) {
            return (new RuleBasedTriage())->assess($symptoms);
        }

        return [
            'level'     => $result['level'],
            'summary'   => (string) $result['summary'],
            'advice'    => (string) $result['advice'],
            'red_flags' => array_map('strval', (array) $result['red_flags']),
            'source'    => 'ai',
        ];
    }

    /**
     * @param array<string, mixed> $record Row from medical_records
     * @param array<string, mixed> $pet    Row from pets
     *
     * @return array{text: string, source: string}
     */
    public function summarizeRecord(array $record, array $pet): array
    {
        // What the owner may see. The vet's private notes (vet_notes) are never sent or shown.
        $fields = [
            'Type of visit'         => isset($record['record_type']) ? str_replace('_', ' ', $record['record_type']) : null,
            'Visit date'            => $record['visit_date'] ?? null,
            'Title'                 => $record['title'] ?? null,
            'Reason for the visit'  => $record['chief_complaint'] ?? null,
            'Weight (kg)'           => $record['weight_kg'] ?? null,
            'Temperature °C'        => $record['temperature_c'] ?? null,
            'Heart rate (per min)'  => $record['heart_rate_bpm'] ?? null,
            'Breathing (per min)'   => $record['respiratory_rate'] ?? null,
            'Findings'              => $record['findings'] ?? null,
            'Diagnosis'             => $record['diagnosis'] ?? null,
            'Treatment'             => $record['treatment'] ?? null,
            'Lab results'           => $record['lab_results'] ?? null,
            'Follow-up date'        => $record['follow_up_date'] ?? null,
            'Follow-up notes'       => $record['follow_up_notes'] ?? null,
        ];
        $lines = [];
        foreach ($fields as $label => $value) {
            if ($value !== null && $value !== '') {
                $lines[] = "{$label}: {$value}";
            }
        }

        if ($this->isEnabled()) {
            $prompt = "Pet details:\n" . $this->describePet($pet) . "\n\nMedical record:\n" . implode("\n", $lines);
            $text   = $this->ask(self::SUMMARY_SYSTEM, $prompt);
            if (is_string($text) && $text !== '') {
                return ['text' => $text, 'source' => 'ai'];
            }
        }

        // Template fallback: the record in short sentences, then the vet words explained by the glossary
        $name   = $pet['name'] ?? 'Your pet';
        $reason = ! empty($record['chief_complaint']) ? ' because of: ' . rtrim($record['chief_complaint'], '.') : '';
        $parts  = ["{$name} was seen on " . date('F j, Y', strtotime((string) $record['visit_date'])) . $reason . '.'];
        if (! empty($record['findings'])) {
            $parts[] = 'What the vet found: ' . rtrim($record['findings'], '.') . '.';
        }
        if (! empty($record['diagnosis'])) {
            $parts[] = 'The veterinarian\'s finding: ' . rtrim($record['diagnosis'], '.') . '.';
        }
        if (! empty($record['treatment'])) {
            $parts[] = 'Treatment given: ' . rtrim($record['treatment'], '.') . '.';
        }
        if (! empty($record['follow_up_date'])) {
            $parts[] = 'Please come back for a follow-up on ' . date('F j, Y', strtotime((string) $record['follow_up_date']))
                . (! empty($record['follow_up_notes']) ? ' (' . rtrim($record['follow_up_notes'], '.') . ').' : '.');
        }

        $text  = implode(' ', $parts);
        $words = (new VetGlossary())->explain(implode(' ', [$record['diagnosis'] ?? '', $record['findings'] ?? '', $record['treatment'] ?? '']));
        if ($words !== []) {
            $text .= "\n\nWhat the words mean:";
            foreach ($words as $term => $meaning) {
                $text .= "\n• {$term}: {$meaning}";
            }
        }

        return ['text' => $text, 'source' => 'template'];
    }

    /**
     * Summarises a pet's Symptom & Behavior Journal for the veterinarian.
     *
     * @param list<array<string, mixed>> $entries Rows from journal_entries, oldest first
     * @param array<string, mixed>       $pet     Row from pets
     *
     * @return array{summary: string, notable_changes: list<string>, concern_level: string, source: string}
     */
    public function summarizeJournal(array $entries, array $pet): array
    {
        if ($this->isEnabled()) {
            $schema = [
                'type'       => 'object',
                'properties' => [
                    'summary'         => ['type' => 'string', 'description' => '3-5 sentences for the veterinarian.'],
                    'notable_changes' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Short bullet points, most important first.'],
                    'concern_level'   => ['type' => 'string', 'enum' => self::CONCERN_LEVELS],
                ],
                'required'             => ['summary', 'notable_changes', 'concern_level'],
                'additionalProperties' => false,
            ];

            $prompt = "Pet details:\n" . $this->describePet($pet)
                . "\n\nJournal entries (oldest first, scores are 1 = very low to 5 = very high):\n<journal>\n"
                . implode("\n", array_map([$this, 'describeJournalEntry'], $entries))
                . "\n</journal>";

            $result = $this->ask(self::JOURNAL_SYSTEM, $prompt, $schema);

            if (is_array($result) && in_array($result['concern_level'] ?? null, self::CONCERN_LEVELS, true)) {
                return [
                    'summary'         => (string) $result['summary'],
                    'notable_changes' => array_map('strval', (array) $result['notable_changes']),
                    'concern_level'   => $result['concern_level'],
                    'source'          => 'ai',
                ];
            }
        }

        return (new RuleBasedJournalSummary())->summarize($entries, $pet);
    }

    /** One journal entry as a single line for the prompt. */
    private function describeJournalEntry(array $entry): string
    {
        $parts  = [$entry['entry_date']];
        $fields = [
            'appetite'      => $entry['appetite_score'] ?? null,
            'activity'      => $entry['activity_score'] ?? null,
            'mood'          => $entry['mood'] ?? null,
            'sleep hours'   => $entry['sleep_hours'] ?? null,
            'sleep quality' => $entry['sleep_quality'] ?? null,
            'water'         => $entry['water_intake'] ?? null,
            'stool'         => $entry['bowel_movement'] ?? null,
            'weight kg'     => $entry['weight_kg'] ?? null,
        ];
        foreach ($fields as $label => $value) {
            if ($value !== null && $value !== '') {
                $parts[] = "{$label}: {$value}";
            }
        }
        if (! empty($entry['vomited'])) {
            $parts[] = 'vomited';
        }
        if (! empty($entry['symptoms'])) {
            $parts[] = 'symptoms: ' . $entry['symptoms'];
        }
        if (! empty($entry['behavior_notes'])) {
            $parts[] = 'notes: ' . $entry['behavior_notes'];
        }

        return '- ' . implode('; ', $parts);
    }

    /**
     * One reply of the AI Medical Chatbot.
     *
     * @param list<array{sender: string, content: string}> $history Earlier messages of the conversation, oldest first, ending with the owner's new question
     * @param array<string, mixed>                          $pet     Row from pets (may be empty)
     * @param list<array<string, mixed>>                    $records Recent medical_records rows of the pet
     *
     * @return array{text: string, source: string}
     */
    public function chat(array $history, array $pet = [], array $records = []): array
    {
        $question = (string) end($history)['content'];

        if ($this->isEnabled()) {
            $system = self::CHAT_SYSTEM . "\n\nPet details:\n" . $this->describePet($pet);

            if ($records !== []) {
                $system .= "\n\nRecent medical records:";
                foreach ($records as $record) {
                    $system .= "\n- " . $record['visit_date'] . ': ' . $record['title']
                        . (empty($record['diagnosis']) ? '' : ' | diagnosis: ' . $record['diagnosis'])
                        . (empty($record['treatment']) ? '' : ' | treatment: ' . $record['treatment']);
                }
            }

            // The whole conversation, so Claude remembers the earlier questions
            $messages = array_map(
                static fn ($m) => ['role' => $m['sender'] === 'user' ? 'user' : 'assistant', 'content' => (string) $m['content']],
                $history
            );

            $text = $this->ask($system, $messages);
            if (is_string($text) && $text !== '') {
                return ['text' => $text, 'source' => 'ai'];
            }
        }

        return ['text' => (new VetGlossary())->answer($question, $pet), 'source' => 'glossary'];
    }

    /**
     * Sends one request to the AI (Claude, or Gemini when it is the provider). Returns decoded JSON when a schema is given, otherwise text.
     * Returns null on any failure so callers can fall back.
     *
     * @param string|list<array{role: string, content: string}> $prompt One question, or a whole conversation
     * @param array<string, mixed>|null                         $schema
     *
     * @return array<string, mixed>|string|null
     */
    private function ask(string $system, string|array $prompt, ?array $schema = null): array|string|null
    {
        $messages = is_string($prompt) ? [['role' => 'user', 'content' => $prompt]] : $prompt;

        if ($this->config->provider === 'gemini') {
            return (new GeminiClient($this->config))->generate($system, $messages, $schema);
        }

        $outputConfig = ['effort' => $this->config->effort];
        if ($schema !== null) {
            $outputConfig['format'] = ['type' => 'json_schema', 'schema' => $schema];
        }

        try {
            $message = $this->client()->beta->messages->create(
                model: $this->config->model,
                maxTokens: $this->config->maxTokens,
                system: $system,
                messages: $messages,
                outputConfig: $outputConfig,
                // Retry on a substitute model if the request is declined by safety classifiers.
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (APIException $e) {
            log_message('error', 'VetAssistant API error: {message}', ['message' => $e->getMessage()]);

            return null;
        } catch (Throwable $e) {
            log_message('error', 'VetAssistant unexpected error: {message}', ['message' => $e->getMessage()]);

            return null;
        }

        if ($message->stopReason === 'refusal' || $message->stopReason === 'max_tokens') {
            log_message('warning', 'VetAssistant stopped early: {reason}', ['reason' => $message->stopReason]);

            return null;
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }
        $text = trim($text);

        if ($schema === null) {
            return $text;
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function client(): Client
    {
        return $this->client ??= new Client(
            apiKey: $this->config->apiKey,
            requestOptions: ['timeout' => $this->config->timeout, 'maxRetries' => 1],
        );
    }

    /**
     * @param array<string, mixed> $pet
     */
    private function describePet(array $pet): string
    {
        if ($pet === []) {
            return 'Not specified.';
        }

        $age = empty($pet['birth_date']) ? 'unknown' : \App\Models\PetModel::ageLabel($pet['birth_date']);

        return implode("\n", [
            'Species: ' . ($pet['species'] ?? 'unknown'),
            'Breed: ' . ($pet['breed'] ?? 'unknown'),
            'Sex: ' . ($pet['sex'] ?? 'unknown'),
            'Age: ' . $age,
            'Weight (kg): ' . ($pet['weight_kg'] ?? 'unknown'),
            'Known allergies: ' . (($pet['allergies'] ?? null) ?: 'none recorded'),
        ]);
    }
}
