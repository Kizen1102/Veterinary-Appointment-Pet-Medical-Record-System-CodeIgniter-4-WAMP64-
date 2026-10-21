<?php

namespace App\Database\Seeds;

use App\Libraries\RuleBasedJournalSummary;
use App\Libraries\RuleBasedTriage;
use CodeIgniter\Database\Seeder;

/**
 * Sample data for the demo / defense, so every screen has something to show.
 *
 *   php spark db:seed DemoSeeder
 *
 * - Adds 2 vets, 1 clinic staff and 3 pet owners with 4 pets, plus their visits,
 *   vaccines, medicines, dose history, journal, chat and notifications.
 * - All dates are counted from today, so "Today's schedule" is never empty.
 * - Your own accounts and data are not touched.
 * - Runs only once: if the demo accounts already exist, it stops.
 *
 * Every demo account uses the password below. Do not run this on a real clinic database.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'demo1234';

    private const FIRST_EMAIL = 'dr.santos@pawrecord.test';

    public function run()
    {
        if ($this->db->table('users')->where('email', self::FIRST_EMAIL)->countAllResults() > 0) {
            echo "Demo data is already loaded. Nothing was added.\n";

            return;
        }

        $this->db->transStart();

        // ---------- 1. Accounts ----------
        $santos = $this->user('vet', 'Dr. Maria Santos', self::FIRST_EMAIL, '09170000101', ['license_number' => 'DEMO-VET-0001', 'specialization' => 'Small Animal Medicine']);
        $reyes  = $this->user('vet', 'Dr. Jose Reyes', 'dr.reyes@pawrecord.test', '09170000102', ['license_number' => 'DEMO-VET-0002', 'specialization' => 'Surgery']);
        $this->user('admin', 'Clinic Staff (Demo)', 'staff@pawrecord.test', '09170000103');
        $ana   = $this->user('owner', 'Ana Reyes', 'ana@pawrecord.test', '09171230001', ['address' => 'Quezon City']);
        $carlo = $this->user('owner', 'Carlo Mendoza', 'carlo@pawrecord.test', '09171230002', ['address' => 'Marikina City']);
        $bea   = $this->user('owner', 'Bea Santos', 'bea@pawrecord.test', '09171230003', ['address' => 'Pasig City']);

        // ---------- 2. Pets ----------
        $luna  = $this->pet($ana, $santos, 'Luna', 'Cat', 'Persian', 'female', 1, '-5 years -2 months', 4.20, 'White and grey', 'Chicken protein');
        $shiro = $this->pet($ana, $santos, 'Shiro', 'Dog', 'Aspin', 'male', 0, '-3 years -1 month', 12.50, 'White');
        $mochi = $this->pet($carlo, $santos, 'Mochi', 'Dog', 'Shih Tzu', 'female', 1, '-2 years -4 months', 6.10, 'Gold and white');
        $kiko  = $this->pet($bea, $reyes, 'Kiko', 'Cat', 'Puspin', 'male', 0, '-1 year -3 months', 3.80, 'Orange tabby');

        // ---------- 3. Appointments ----------
        // Past visits
        $lunaVisit  = $this->appointment($luna, $ana, $santos, 'consultation', 'Skin itching check', $this->at(-20, '09:30'), 'completed', 'Scratching and red skin on the neck');
        $shiroVisit = $this->appointment($shiro, $ana, $santos, 'vaccination', 'Booster shots', $this->at(-30, '10:00'), 'completed', 'Yearly booster');
        $kikoVisit  = $this->appointment($kiko, $bea, $reyes, 'consultation', 'Sneezing', $this->at(-45, '14:00'), 'completed', 'Sneezing and watery eyes');
        $this->appointment($mochi, $carlo, $santos, 'grooming', 'Grooming', $this->at(-5, '13:00'), 'cancelled', 'Bath and nail trim', ['cancellation_reason' => 'The owner asked to move the visit.']);
        $this->appointment($kiko, $bea, $reyes, 'follow_up', 'Recheck', $this->at(-10, '11:00'), 'no_show', 'Recheck after medicine');

        // Today (vet dashboards)
        $this->appointment($mochi, $carlo, $santos, 'wellness_exam', 'Wellness exam', $this->at(0, '10:00'), 'confirmed', 'Yearly check-up');
        $this->appointment($kiko, $bea, $reyes, 'consultation', 'Eye check', $this->at(0, '14:00'), 'confirmed', 'Eyes still a bit watery');

        // Upcoming
        $lunaRecheck = $this->appointment($luna, $ana, $santos, 'follow_up', 'Skin recheck', $this->at(7, '10:00'), 'confirmed', 'Recheck skin after antibiotics');

        // Requests waiting for a vet: the urgency check (Step 15) puts the emergency first
        $this->appointment($shiro, $ana, null, 'emergency', 'Ate chocolate', $this->at(1, '09:30'), 'pending', 'He ate a whole bar of chocolate and is shaking');
        $this->appointment($kiko, $bea, $santos, 'consultation', 'Sneezing again', $this->at(2, '11:00'), 'pending', 'Sneezing and watery eyes for 3 days');
        $this->appointment($mochi, $carlo, null, 'vaccination', 'Booster', $this->at(3, '15:00'), 'pending', 'Annual check-up and booster shot');

        // ---------- 4. Medical records (Health Timeline) ----------
        $lunaRecord = $this->record($luna, $santos, $lunaVisit, 'consultation', -20, 'Skin itching check', [
            'chief_complaint' => 'Scratching and red skin on the neck for one week',
            'weight_kg'       => 4.20, 'temperature_c' => 38.6, 'heart_rate_bpm' => 180, 'respiratory_rate' => 28,
            'findings'        => 'Erythematous papules on the dorsal neck. No fleas or mites seen.',
            'diagnosis'       => 'Atopic dermatitis with secondary superficial pyoderma',
            'treatment'       => 'Cephalexin BID for 14 days, chlorhexidine wipes and omega-3 supplement.',
            'vet_notes'       => 'Owner may be giving chicken treats. Discuss diet at the recheck.',
            'follow_up_date'  => $this->day(7), 'follow_up_notes' => 'Recheck skin',
        ]);
        $shiroRecord = $this->record($shiro, $santos, $shiroVisit, 'treatment', -30, 'Booster vaccination', [
            'chief_complaint' => 'Yearly booster',
            'weight_kg'       => 12.50, 'temperature_c' => 38.5, 'heart_rate_bpm' => 96, 'respiratory_rate' => 22,
            'findings'        => 'Bright, alert and responsive. Body condition score 5/9.',
            'diagnosis'       => 'Healthy',
            'treatment'       => 'DHPP and anti-rabies vaccines given.',
        ]);
        $this->record($shiro, $santos, null, 'lab_test', -90, 'Routine blood work', [
            'weight_kg' => 12.30, 'findings' => 'CBC and blood chemistry within normal limits.', 'diagnosis' => 'Healthy',
        ]);
        $kikoRecord = $this->record($kiko, $reyes, $kikoVisit, 'consultation', -45, 'Sneezing and watery eyes', [
            'chief_complaint' => 'Sneezing and watery eyes for 4 days',
            'weight_kg'       => 3.60, 'temperature_c' => 39.4, 'heart_rate_bpm' => 190, 'respiratory_rate' => 32,
            'findings'        => 'Serous ocular and nasal discharge. Mild conjunctivitis on both eyes.',
            'diagnosis'       => 'Feline upper respiratory infection (URI)',
            'treatment'       => 'Doxycycline SID for 10 days. Keep eyes clean.',
        ]);

        // ---------- 5. Vaccinations ----------
        $this->vaccine($luna, $santos, null, 'FVRCP', 3, '-5 months', '+7 months');
        $this->vaccine($luna, $santos, null, 'Anti-Rabies', 5, '-4 months', '+8 months');
        $this->vaccine($shiro, $santos, $shiroRecord, 'DHPP', 4, '-30 days', '+335 days');
        $this->vaccine($shiro, $santos, $shiroRecord, 'Anti-Rabies', 3, '-30 days', '+335 days');
        $this->vaccine($mochi, $santos, null, 'DHPP', 3, '-13 months', '-1 month');   // overdue: shown in red
        $this->vaccine($kiko, $reyes, $kikoRecord, 'FVRCP', 1, '-45 days', '+5 days'); // due soon

        // ---------- 6. Medicines and dose history (Medication Tracker) ----------
        $cephalexin = $this->medication($luna, $santos, $lunaRecord, 'Cephalexin', '75mg', 'capsule', 'Give with food, every 12 hours', 'Skin infection', -5, 9, 28, ['08:00', '20:00']);
        $omega      = $this->medication($luna, $santos, $lunaRecord, 'Omega-3 Supplement', '500mg', 'capsule', 'With the morning meal', 'Skin and coat health', -20, null, null, ['08:00'], -6);
        $doxy       = $this->medication($kiko, $reyes, $kikoRecord, 'Doxycycline', '25mg', 'tablet', 'Once a day, followed by water', 'Upper respiratory infection', -45, -36, 10, ['09:00']);

        // Cephalexin since day -5: one missed and one skipped dose, so the adherence % is not 100
        $this->doses($cephalexin, $ana, 5, ['-2 20:00' => 'missed', '-4 08:00' => 'skipped']);
        $this->doses($omega, $ana, 6);          // the app has tracked the Omega-3 for the last 6 days
        $this->doses($doxy, $bea, 10, [], -35); // finished course: days -45 to -36
        $this->db->table('medications')->where('id', $doxy['id'])->update(['status' => 'completed']);

        // ---------- 7. Daily journal (Luna's appetite dropped) and its summary ----------
        $journal = [
            // days ago => appetite, activity, mood, sleep hours, sleep quality, water, stool, vomited, symptoms
            6 => [4, 4, 'playful', 13.0, 4, 'normal', 'normal', 0, 'Some scratching on the neck'],
            5 => [4, 3, 'calm', 13.5, 4, 'normal', 'normal', 0, 'Started the antibiotic'],
            4 => [4, 3, 'calm', 14.0, 4, 'normal', 'normal', 0, null],
            3 => [3, 2, 'lethargic', 16.0, 3, 'less', 'normal', 1, 'Vomited once after dinner'],
            2 => [2, 2, 'withdrawn', 16.5, 3, 'less', 'normal', 0, 'Hid under the bed'],
            1 => [2, 3, 'calm', 15.0, 3, 'normal', 'normal', 0, 'Ate half of her breakfast'],
        ];
        foreach ($journal as $daysAgo => [$appetite, $activity, $mood, $sleep, $quality, $water, $stool, $vomited, $symptoms]) {
            $this->db->table('journal_entries')->insert([
                'pet_id' => $luna['id'], 'logged_by' => $ana, 'entry_date' => $this->day(-$daysAgo),
                'appetite_score' => $appetite, 'activity_score' => $activity, 'mood' => $mood,
                'sleep_hours' => $sleep, 'sleep_quality' => $quality, 'water_intake' => $water,
                'bowel_movement' => $stool, 'vomited' => $vomited, 'symptoms' => $symptoms,
            ]);
        }
        foreach ([3, 2, 1] as $daysAgo) {
            $this->db->table('journal_entries')->insert([
                'pet_id' => $shiro['id'], 'logged_by' => $ana, 'entry_date' => $this->day(-$daysAgo),
                'appetite_score' => 5, 'activity_score' => 5, 'mood' => 'playful', 'sleep_hours' => 12.0,
                'sleep_quality' => 5, 'water_intake' => 'normal', 'bowel_movement' => 'normal', 'vomited' => 0,
            ]);
        }

        // The same offline summary the owner gets with "Create summary" (not yet reviewed by the vet)
        $entries = $this->db->table('journal_entries')->where('pet_id', $luna['id'])->orderBy('entry_date', 'ASC')->get()->getResultArray();
        $summary = (new RuleBasedJournalSummary())->summarize($entries, $luna);
        $this->db->table('journal_ai_summaries')->insert([
            'pet_id' => $luna['id'], 'requested_by' => $ana,
            'period_start' => $entries[0]['entry_date'], 'period_end' => $entries[count($entries) - 1]['entry_date'],
            'entries_count' => count($entries), 'summary' => $summary['summary'],
            'notable_changes' => json_encode($summary['notable_changes']), 'concern_level' => $summary['concern_level'],
            'ai_model' => 'rules',
        ]);

        // ---------- 8. AI chat example ----------
        $this->db->table('chat_conversations')->insert([
            'user_id' => $ana, 'pet_id' => $luna['id'], 'medical_record_id' => $lunaRecord, 'title' => 'What does atopic dermatitis mean?',
        ]);
        $chatId = $this->db->insertID();
        $this->db->table('chat_messages')->insertBatch([
            ['conversation_id' => $chatId, 'sender' => 'user', 'content' => 'What does atopic dermatitis mean?', 'ai_model' => null],
            ['conversation_id' => $chatId, 'sender' => 'assistant', 'ai_model' => 'rules',
                'content' => 'Atopic dermatitis is a skin allergy to things in the environment, like dust mites or pollen. '
                    . 'It makes the skin itchy and red. It can be controlled but usually not cured, so your vet may suggest '
                    . 'long-term care such as special baths or supplements. This is general information, not a diagnosis.'],
        ]);

        // ---------- 9. Notifications ----------
        $this->db->table('notifications')->insertBatch([
            ['user_id' => $ana, 'pet_id' => $luna['id'], 'type' => 'appointment_update', 'title' => 'Appointment confirmed',
                'message' => 'Luna\'s skin recheck on ' . date('M j', strtotime($this->day(7))) . ' at 10:00 AM is confirmed.',
                'link_url' => 'appointments', 'related_table' => 'appointments', 'related_id' => $lunaRecheck],
            ['user_id' => $ana, 'pet_id' => $luna['id'], 'type' => 'general', 'title' => 'New visit record',
                'message' => 'Dr. Maria Santos added the record of Luna\'s skin check.',
                'link_url' => 'timeline?pet=' . $luna['id'], 'related_table' => 'medical_records', 'related_id' => $lunaRecord],
            ['user_id' => $bea, 'pet_id' => $kiko['id'], 'type' => 'vaccine_due', 'title' => 'Kiko\'s FVRCP booster is due soon',
                'message' => 'Due on ' . date('M j', strtotime('+5 days')) . '.', 'link_url' => 'timeline?pet=' . $kiko['id'],
                'related_table' => 'pets', 'related_id' => $kiko['id']],
        ]);

        $this->db->transComplete();

        echo "Demo data added. Password of every demo account: " . self::PASSWORD . "\n"
            . "  Vets:   dr.santos@pawrecord.test, dr.reyes@pawrecord.test\n"
            . "  Staff:  staff@pawrecord.test\n"
            . "  Owners: ana@pawrecord.test (Luna, Shiro), carlo@pawrecord.test (Mochi), bea@pawrecord.test (Kiko)\n";
    }

    // ---------- small helpers ----------

    /** Date $offset days from today, e.g. day(-3) = 3 days ago. */
    private function day(int $offset): string
    {
        return date('Y-m-d', strtotime($offset . ' days'));
    }

    /** Date and time $offset days from today, e.g. at(1, '09:30') = tomorrow 9:30 AM. */
    private function at(int $offset, string $time): string
    {
        return $this->day($offset) . ' ' . $time . ':00';
    }

    private function user(string $role, string $name, string $email, string $phone, array $extra = []): int
    {
        $this->db->table('users')->insert($extra + [
            'role' => $role, 'full_name' => $name, 'email' => $email, 'phone' => $phone,
            'password_hash' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
            'is_active' => 1, 'email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    private function pet(int $owner, int $vet, string $name, string $species, string $breed, string $sex, int $neutered,
        string $age, float $weight, string $color, ?string $allergies = null): array
    {
        $pet = [
            'owner_id' => $owner, 'primary_vet_id' => $vet, 'name' => $name, 'species' => $species, 'breed' => $breed,
            'sex' => $sex, 'is_neutered' => $neutered, 'birth_date' => date('Y-m-d', strtotime($age)),
            'weight_kg' => $weight, 'color_markings' => $color, 'allergies' => $allergies,
        ];
        $this->db->table('pets')->insert($pet);

        return ['id' => (int) $this->db->insertID()] + $pet;
    }

    private function appointment(array $pet, int $owner, ?int $vet, string $type, string $title, string $at,
        string $status, string $reason, array $extra = []): int
    {
        // Pending requests get the same urgency check as a real booking (offline rules)
        $triage = (new RuleBasedTriage())->assess($reason);

        $this->db->table('appointments')->insert($extra + [
            'pet_id' => $pet['id'], 'owner_id' => $owner, 'vet_id' => $vet, 'appointment_type' => $type, 'title' => $title,
            'scheduled_at' => $at, 'duration_minutes' => 30, 'reason' => $reason, 'status' => $status,
            'triage_level' => $triage['level'], 'triage_summary' => mb_substr($triage['summary'], 0, 255),
            'created_by' => $owner, 'created_at' => min($at, date('Y-m-d H:i:s', strtotime('-1 day'))),
        ]);

        return (int) $this->db->insertID();
    }

    private function record(array $pet, int $vet, ?int $appointment, string $type, int $daysAgo, string $title, array $fields): int
    {
        $this->db->table('medical_records')->insert($fields + [
            'pet_id' => $pet['id'], 'vet_id' => $vet, 'appointment_id' => $appointment,
            'record_type' => $type, 'visit_date' => $this->day($daysAgo), 'title' => $title,
        ]);

        return (int) $this->db->insertID();
    }

    private function vaccine(array $pet, int $vet, ?int $record, string $name, int $dose, string $given, string $nextDue): void
    {
        $this->db->table('vaccinations')->insert([
            'pet_id' => $pet['id'], 'vet_id' => $vet, 'medical_record_id' => $record, 'vaccine_name' => $name,
            'dose_number' => $dose, 'date_given' => date('Y-m-d', strtotime($given)),
            'next_due_date' => date('Y-m-d', strtotime($nextDue)),
        ]);
    }

    /**
     * A medicine with its dose times; returns ['id' => ..., 'times' => [schedule id => 'HH:MM']].
     * $addedOffset = the day it was added to the app (the tracker counts doses from that day).
     */
    private function medication(array $pet, int $vet, int $record, string $name, string $dosage, string $form, string $instructions,
        string $purpose, int $startOffset, ?int $endOffset, ?int $totalDoses, array $times, ?int $addedOffset = null): array
    {
        $this->db->table('medications')->insert([
            'pet_id' => $pet['id'], 'medical_record_id' => $record, 'prescribed_by' => $vet, 'name' => $name,
            'dosage' => $dosage, 'form' => $form, 'route' => $form === 'topical' ? 'skin' : 'oral',
            'instructions' => $instructions, 'purpose' => $purpose,
            'start_date' => $this->day($startOffset), 'end_date' => $endOffset === null ? null : $this->day($endOffset),
            'total_doses' => $totalDoses, 'status' => 'active',
            'created_at' => $this->day($addedOffset ?? $startOffset) . ' 07:00:00',
        ]);
        $medication = ['id' => (int) $this->db->insertID(), 'times' => []];

        foreach ($times as $time) {
            $this->db->table('medication_schedules')->insert(['medication_id' => $medication['id'], 'dose_time' => $time . ':00']);
            $medication['times'][(int) $this->db->insertID()] = $time;
        }

        return $medication;
    }

    /**
     * Dose history for the $days days before $beforeDay (0 = up to yesterday). Every dose is "taken",
     * except the ones in $exceptions, e.g. ['-2 20:00' => 'missed'] = the 8:00 PM dose 2 days ago.
     * Today's doses are left to the Medication Tracker, which adds them when the owner opens the app.
     */
    private function doses(array $medication, int $owner, int $days, array $exceptions = [], int $beforeDay = 0): void
    {
        $rows = [];

        for ($offset = $beforeDay - $days; $offset < $beforeDay; $offset++) {
            foreach ($medication['times'] as $scheduleId => $time) {
                $status = $exceptions[$offset . ' ' . $time] ?? 'taken';
                $rows[] = [
                    'medication_id' => $medication['id'], 'schedule_id' => $scheduleId,
                    'scheduled_for' => $this->at($offset, $time), 'status' => $status,
                    'taken_at'      => $status === 'taken' ? date('Y-m-d H:i:s', strtotime($this->at($offset, $time) . ' +10 minutes')) : null,
                    'logged_by'     => $status === 'missed' ? null : $owner,
                ];
            }
        }

        if ($rows !== []) {
            $this->db->table('medication_logs')->insertBatch($rows);
        }
    }
}
