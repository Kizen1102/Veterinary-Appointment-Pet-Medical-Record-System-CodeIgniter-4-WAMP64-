<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * One row per scheduled dose: pending → taken | missed | skipped.
 */
class MedicationLogModel extends Model
{
    public const STATUSES = ['pending', 'taken', 'missed', 'skipped'];

    protected $table         = 'medication_logs';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'medication_id', 'schedule_id', 'scheduled_for', 'status', 'taken_at', 'logged_by', 'notes', 'missed_alert_sent_at',
    ];
    protected $validationRules = [
        'medication_id' => 'required|is_natural_no_zero',
        'scheduled_for' => 'required|valid_date[Y-m-d H:i:s]',
        'status'        => 'permit_empty|in_list[pending,taken,missed,skipped]',
    ];

    /** Today's doses for one pet with the medication details ("8:00 AM dose due"). */
    public function todayForPet(int $petId): array
    {
        return $this->select('medication_logs.*, medications.name, medications.dosage, medications.instructions, medications.pet_id')
            ->join('medications', 'medications.id = medication_logs.medication_id')
            ->where('medications.pet_id', $petId)
            ->where('medication_logs.scheduled_for >=', date('Y-m-d 00:00:00'))
            ->where('medication_logs.scheduled_for <=', date('Y-m-d 23:59:59'))
            ->orderBy('medication_logs.scheduled_for')
            ->findAll();
    }

    /** Owner taps "Mark": the dose is recorded as taken. */
    public function markTaken(int $logId, int $userId): bool
    {
        return $this->update($logId, [
            'status'    => 'taken',
            'taken_at'  => date('Y-m-d H:i:s'),
            'logged_by' => $userId,
        ]);
    }

    /** Turns pending doses older than the grace period into "missed". Returns how many changed. */
    public function markOverdueAsMissed(int $graceMinutes = 60): int
    {
        $this->where('status', 'pending')
            ->where('scheduled_for <', date('Y-m-d H:i:s', time() - $graceMinutes * 60))
            ->set('status', 'missed')
            ->update();

        return $this->db->affectedRows();
    }
}
