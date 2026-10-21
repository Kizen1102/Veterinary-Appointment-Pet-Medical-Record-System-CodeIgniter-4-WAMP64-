<?php

namespace App\Libraries;

use App\Models\MedicationLogModel;
use App\Models\MedicationModel;
use App\Models\MedicationScheduleModel;
use App\Models\NotificationModel;

/**
 * Keeps one owner's medication data up to date. Called every time the owner opens
 * the dashboard or the Meds page (XAMPP has no scheduled jobs, so we check on page load):
 *
 *   1. Medications whose end date has passed become "completed".
 *   2. A "pending" dose row is created for every dose time, from the start date until today.
 *   3. Pending doses more than 1 hour late become "missed", and the owner gets a notification.
 */
class MedicationTracker
{
    /** How late a dose can be before it counts as missed. */
    public const GRACE_MINUTES = 60;

    /** Doses are created for at most this many past days (when the owner has not opened the app for a while). */
    private const MAX_BACKFILL_DAYS = 14;

    public function refresh(int $ownerId): void
    {
        $this->completeEndedMedications($ownerId);
        $this->createDoses($ownerId);
        $this->flagMissedDoses($ownerId);
    }

    /** The active medications of all of the owner's pets, with the pet name and owner id. */
    private function activeMedications(int $ownerId): array
    {
        return (new MedicationModel())
            ->select('medications.*, pets.name AS pet_name, pets.owner_id')
            ->join('pets', 'pets.id = medications.pet_id')
            ->where('pets.owner_id', $ownerId)
            ->where('pets.deleted_at', null)
            ->where('medications.status', 'active')
            ->findAll();
    }

    private function completeEndedMedications(int $ownerId): void
    {
        $medications = new MedicationModel();

        foreach ($this->activeMedications($ownerId) as $medication) {
            if ($medication['end_date'] !== null && $medication['end_date'] < date('Y-m-d')) {
                $medications->update($medication['id'], ['status' => 'completed']);
            }
        }
    }

    private function createDoses(int $ownerId): void
    {
        $schedules = new MedicationScheduleModel();
        $rows      = [];
        $today     = date('Y-m-d');
        $earliest  = date('Y-m-d', strtotime('-' . self::MAX_BACKFILL_DAYS . ' days'));

        foreach ($this->activeMedications($ownerId) as $medication) {
            // Doses start on the start date, but never before the medication was added to the app
            $addedAt  = $medication['created_at'];
            $firstDay = max($medication['start_date'], substr($addedAt, 0, 10), $earliest);
            $lastDay  = min($medication['end_date'] ?? $today, $today);

            for ($day = $firstDay; $day <= $lastDay; $day = date('Y-m-d', strtotime($day . ' +1 day'))) {
                foreach ($schedules->forMedication((int) $medication['id']) as $schedule) {
                    $scheduledFor = $day . ' ' . $schedule['dose_time'];

                    if ($scheduledFor < $addedAt) {
                        continue; // e.g. the 8:00 AM dose of the day the medication was added at 3:00 PM
                    }

                    $rows[] = [
                        'medication_id' => $medication['id'],
                        'schedule_id'   => $schedule['id'],
                        'scheduled_for' => $scheduledFor,
                        'status'        => 'pending',
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        if ($rows !== []) {
            // "Ignore" skips doses that already exist (the table allows one row per medication and time)
            db_connect()->table('medication_logs')->ignore(true)->insertBatch($rows);
        }
    }

    private function flagMissedDoses(int $ownerId): void
    {
        $logs          = new MedicationLogModel();
        $notifications = new NotificationModel();

        $lateDoses = $logs
            ->select('medication_logs.*, medications.name, medications.dosage, medications.pet_id, pets.name AS pet_name')
            ->join('medications', 'medications.id = medication_logs.medication_id')
            ->join('pets', 'pets.id = medications.pet_id')
            ->where('pets.owner_id', $ownerId)
            ->where('medication_logs.status', 'pending')
            ->where('medication_logs.scheduled_for <', date('Y-m-d H:i:s', time() - self::GRACE_MINUTES * 60))
            ->findAll();

        foreach ($lateDoses as $dose) {
            $logs->update($dose['id'], ['status' => 'missed', 'missed_alert_sent_at' => date('Y-m-d H:i:s')]);

            $notifications->insert([
                'user_id'       => $ownerId,
                'pet_id'        => $dose['pet_id'],
                'type'          => 'missed_dose',
                'title'         => 'Missed dose: ' . $dose['name'] . ' ' . date('g:i A', strtotime($dose['scheduled_for'])),
                'message'       => $dose['pet_name'] . ' did not get ' . $dose['dosage'] . ' on ' . date('M j', strtotime($dose['scheduled_for'])) . '.',
                'link_url'      => 'meds?pet=' . $dose['pet_id'],
                'related_table' => 'medication_logs',
                'related_id'    => $dose['id'],
            ]);
        }
    }
}
