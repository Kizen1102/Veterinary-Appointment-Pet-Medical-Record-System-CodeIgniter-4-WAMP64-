<?php

namespace App\Libraries;

/**
 * Offline journal summary used when the AI service is not configured or unavailable.
 * Compares the first half of the period with the second half and counts warning signs.
 */
class RuleBasedJournalSummary
{
    /**
     * @param list<array<string, mixed>> $entries Rows from journal_entries, oldest first
     * @param array<string, mixed>       $pet     Row from pets
     *
     * @return array{summary: string, notable_changes: list<string>, concern_level: string, source: string}
     */
    public function summarize(array $entries, array $pet): array
    {
        $name    = $pet['name'] ?? 'The pet';
        $changes = [];
        $points  = 0; // the more warning signs, the higher the concern level

        // 1. Trends in the 1-5 scores: first half of the period vs. second half
        $scores = ['appetite_score' => 'Appetite', 'activity_score' => 'Activity', 'sleep_quality' => 'Sleep quality'];
        foreach ($scores as $field => $label) {
            [$before, $after] = $this->halfAverages($entries, $field);
            if ($before === null || $after === null) {
                continue;
            }
            if ($after <= $before - 1) {
                $changes[] = sprintf('%s dropped from about %.1f to %.1f (out of 5).', $label, $before, $after);
                $points += $after <= 2 ? 2 : 1;
            } elseif ($after >= $before + 1) {
                $changes[] = sprintf('%s improved from about %.1f to %.1f (out of 5).', $label, $before, $after);
            }
        }

        // 2. Warning signs counted over the whole period
        $vomitDays  = count(array_filter($entries, static fn ($e) => ! empty($e['vomited'])));
        $bloodyDays = count(array_filter($entries, static fn ($e) => ($e['bowel_movement'] ?? null) === 'bloody'));
        $looseDays  = count(array_filter($entries, static fn ($e) => ($e['bowel_movement'] ?? null) === 'diarrhea'));
        $lowMood    = count(array_filter($entries, static fn ($e) => in_array($e['mood'] ?? null, ['lethargic', 'withdrawn'], true)));
        $lessWater  = count(array_filter($entries, static fn ($e) => ($e['water_intake'] ?? null) === 'less'));

        if ($bloodyDays > 0) {
            $changes[] = "Blood in the stool on {$bloodyDays} day(s).";
            $points += 4;
        }
        if ($vomitDays > 0) {
            $changes[] = "Vomited on {$vomitDays} day(s).";
            $points += $vomitDays >= 2 ? 2 : 1;
        }
        if ($looseDays > 0) {
            $changes[] = "Diarrhea on {$looseDays} day(s).";
            $points += $looseDays >= 2 ? 2 : 1;
        }
        if ($lowMood > 0) {
            $changes[] = "Seemed lethargic or withdrawn on {$lowMood} day(s).";
            $points += 1;
        }
        if ($lessWater > 0) {
            $changes[] = "Drank less water than usual on {$lessWater} day(s).";
            $points += 1;
        }

        // 3. Weight change between the first and the last recorded weight
        $weights = array_values(array_filter(array_column($entries, 'weight_kg'), static fn ($w) => $w !== null && $w !== ''));
        if (count($weights) >= 2 && (float) $weights[0] > 0) {
            $percent = ((float) end($weights) - (float) $weights[0]) / (float) $weights[0] * 100;
            if (abs($percent) >= 5) {
                $changes[] = sprintf('Weight changed by %+.1f%% (%s kg → %s kg).', $percent, $weights[0], end($weights));
                $points += 1;
            }
        }

        // 4. Symptoms the owner wrote down (most recent last)
        $symptoms = array_values(array_filter(array_column($entries, 'symptoms')));
        if ($symptoms !== []) {
            $changes[] = 'Owner noted: "' . end($symptoms) . '"';
        }

        $level = match (true) {
            $points >= 4 => 'high',
            $points >= 2 => 'moderate',
            $points >= 1 => 'low',
            default      => 'none',
        };

        $first   = date('M j', strtotime((string) $entries[0]['entry_date']));
        $last    = date('M j', strtotime((string) end($entries)['entry_date']));
        $summary = "{$name} has " . count($entries) . " journal entries from {$first} to {$last}. "
            . ($changes === []
                ? 'Appetite, activity and mood stayed stable, and no warning signs were logged.'
                : 'The owner logged ' . count($changes) . ' change(s) or sign(s) that the veterinarian may want to ask about (listed below).');

        return [
            'summary'         => $summary,
            'notable_changes' => $changes,
            'concern_level'   => $level,
            'source'          => 'rules',
        ];
    }

    /**
     * Average of one score in the older half and in the newer half of the entries.
     *
     * @return array{0: float|null, 1: float|null}
     */
    private function halfAverages(array $entries, string $field): array
    {
        $values = array_values(array_filter(array_column($entries, $field), static fn ($v) => $v !== null && $v !== ''));
        if (count($values) < 2) {
            return [null, null];
        }

        $middle = intdiv(count($values), 2);
        $older  = array_slice($values, 0, $middle);
        $newer  = array_slice($values, $middle);

        return [array_sum($older) / count($older), array_sum($newer) / count($newer)];
    }
}
