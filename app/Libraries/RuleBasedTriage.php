<?php

namespace App\Libraries;

/**
 * Offline keyword triage used when the AI service is not configured or unavailable.
 * Deliberately conservative: when in doubt it ranks a case as more urgent.
 */
class RuleBasedTriage
{
    private const KEYWORDS = [
        'emergency' => [
            'not breathing', 'difficulty breathing', 'trouble breathing', 'choking', 'seizure', 'convuls',
            'unconscious', 'collapse', 'hit by', 'heavy bleeding', 'bleeding heavily', 'poison', 'toxic',
            'antifreeze', 'rat bait', 'chocolate', 'xylitol', 'bloat', 'swollen abdomen', 'pale gums',
            'blue gums', 'cannot urinate', "can't urinate", 'straining to urinate', 'heatstroke', 'snake bite',
        ],
        'high' => [
            'vomiting blood', 'blood in', 'bloody', 'not eating', 'refuses food', 'lethargic', 'weak',
            'high fever', 'dehydrat', 'limp and', 'broken', 'fracture', 'eye injury', 'wound', 'swelling',
            'labor', 'giving birth', 'diarrhea for', 'vomiting for', 'repeated vomiting',
        ],
        'medium' => [
            'vomit', 'diarrh', 'limp', 'cough', 'sneez', 'itch', 'scratch', 'rash', 'ear', 'eye',
            'discharge', 'lump', 'fever', 'pain', 'not drinking', 'drinking a lot', 'urinat', 'hair loss',
        ],
    ];

    private const ADVICE = [
        'emergency' => 'These signs can be life-threatening. Please bring your pet to the clinic or the nearest emergency vet immediately — do not wait for a scheduled slot.',
        'high'      => 'Your pet should be seen by a veterinarian today. Keep them calm and comfortable, and call the clinic if their condition gets worse.',
        'medium'    => 'Schedule a visit within the next 1–3 days. Monitor appetite, energy, and bathroom habits and contact us if things worsen.',
        'low'       => 'This sounds routine. Book a convenient appointment; no urgent action appears to be needed.',
    ];

    /**
     * @return array{level: string, summary: string, advice: string, red_flags: list<string>, source: string}
     */
    public function assess(string $symptoms): array
    {
        $text = strtolower($symptoms);

        foreach (self::KEYWORDS as $level => $words) {
            $matched = array_values(array_filter($words, static fn ($w) => str_contains($text, $w)));
            if ($matched !== []) {
                return [
                    'level'     => $level,
                    'summary'   => 'Keyword screening matched: ' . implode(', ', $matched) . '.',
                    'advice'    => self::ADVICE[$level],
                    'red_flags' => $matched,
                    'source'    => 'rules',
                ];
            }
        }

        return [
            'level'     => 'low',
            'summary'   => 'No urgent warning signs were detected in the description.',
            'advice'    => self::ADVICE['low'],
            'red_flags' => [],
            'source'    => 'rules',
        ];
    }
}
