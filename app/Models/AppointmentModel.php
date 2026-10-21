<?php

namespace App\Models;

use CodeIgniter\Model;

class AppointmentModel extends Model
{
    public const TYPES    = ['consultation', 'wellness_exam', 'vaccination', 'follow_up', 'surgery', 'dental', 'grooming', 'emergency'];
    public const STATUSES = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'];

    /** Names shown on screen for each appointment type. */
    public const TYPE_LABELS = [
        'consultation'  => 'Consultation',
        'wellness_exam' => 'Wellness Exam',
        'vaccination'   => 'Vaccination',
        'follow_up'     => 'Follow-up',
        'surgery'       => 'Surgery',
        'dental'        => 'Dental',
        'grooming'      => 'Grooming',
        'emergency'     => 'Emergency',
    ];

    /** Types a Pet Owner can book online. Surgery and emergencies are arranged by the clinic. */
    public const OWNER_TYPES = ['consultation', 'wellness_exam', 'vaccination', 'follow_up', 'dental', 'grooming'];

    /** Step 15: AI urgency levels, most urgent first (used to put urgent requests on top). */
    public const URGENCY_RANK = ['emergency' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];

    /** Clinic hours for online booking: 8:00 AM to 5:00 PM, one visit = 30 minutes. */
    public const OPEN_HOUR    = 8;
    public const CLOSE_HOUR   = 17;
    public const SLOT_MINUTES = 30;

    protected $table         = 'appointments';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pet_id', 'owner_id', 'vet_id', 'appointment_type', 'title', 'scheduled_at', 'duration_minutes',
        'reason', 'triage_level', 'triage_summary', 'status', 'remind_owner', 'reminder_sent_at', 'cancellation_reason', 'staff_notes', 'created_by',
    ];
    protected $validationRules = [
        'pet_id'           => 'required|is_natural_no_zero',
        'owner_id'         => 'required|is_natural_no_zero',
        'vet_id'           => 'permit_empty|is_natural_no_zero',
        'appointment_type' => 'required|in_list[consultation,wellness_exam,vaccination,follow_up,surgery,dental,grooming,emergency]',
        'scheduled_at'     => 'required|valid_date[Y-m-d H:i:s]',
        'duration_minutes' => 'permit_empty|integer|greater_than_equal_to[5]|less_than_equal_to[480]',
        'status'           => 'permit_empty|in_list[pending,confirmed,checked_in,completed,cancelled,no_show]',
    ];

    /** Appointments with pet, owner and vet names attached. */
    public function detailed()
    {
        return $this->select('appointments.*, pets.name AS pet_name, pets.species,
                owners.full_name AS owner_name, vets.full_name AS vet_name')
            ->join('pets', 'pets.id = appointments.pet_id')
            ->join('users AS owners', 'owners.id = appointments.owner_id')
            ->join('users AS vets', 'vets.id = appointments.vet_id', 'left');
    }

    /** Appointments a vet can see: the ones assigned to them, and the ones with no vet yet. */
    public function visibleToVet(int $vetId)
    {
        return $this->detailed()
            ->groupStart()
                ->where('appointments.vet_id', $vetId)
                ->orWhere('appointments.vet_id', null)
            ->groupEnd();
    }

    /**
     * The appointments of one tab of the vet's Appointments page:
     * today | pending (requests) | upcoming (confirmed) | past.
     */
    public function forVetTab(int $vetId, string $tab, int $limit = 50): array
    {
        $now     = date('Y-m-d H:i:s');
        $builder = $this->visibleToVet($vetId);

        switch ($tab) {
            case 'today':
                $builder->where('appointments.scheduled_at >=', date('Y-m-d 00:00:00'))
                    ->where('appointments.scheduled_at <=', date('Y-m-d 23:59:59'))
                    ->where('appointments.status !=', 'cancelled')
                    ->orderBy('appointments.scheduled_at');
                break;

            case 'pending':
                $builder->where('appointments.status', 'pending')
                    ->where('appointments.scheduled_at >', $now)
                    ->orderBy('appointments.scheduled_at');

                return self::urgentFirst($builder->findAll($limit));

            case 'upcoming':
                $builder->where('appointments.status', 'confirmed')
                    ->where('appointments.scheduled_at >', $now)
                    ->orderBy('appointments.scheduled_at');
                break;

            default: // past
                $builder->where('appointments.scheduled_at <', date('Y-m-d 00:00:00'))
                    ->orderBy('appointments.scheduled_at', 'DESC');
        }

        return $builder->findAll($limit);
    }

    /**
     * The appointments of one tab of the admin's Appointments page:
     * today | upcoming | unassigned (requests with no vet) | past.
     */
    public function forAdminTab(string $tab, int $limit = 100): array
    {
        $now     = date('Y-m-d H:i:s');
        $builder = $this->detailed();

        switch ($tab) {
            case 'today':
                $builder->where('appointments.scheduled_at >=', date('Y-m-d 00:00:00'))
                    ->where('appointments.scheduled_at <=', date('Y-m-d 23:59:59'))
                    ->orderBy('appointments.scheduled_at');
                break;

            case 'unassigned':
                $builder->where('appointments.vet_id', null)
                    ->where('appointments.status', 'pending')
                    ->where('appointments.scheduled_at >', $now)
                    ->orderBy('appointments.scheduled_at');

                return self::urgentFirst($builder->findAll($limit));

            case 'upcoming':
                $builder->whereIn('appointments.status', ['pending', 'confirmed'])
                    ->where('appointments.scheduled_at >', $now)
                    ->orderBy('appointments.scheduled_at');
                break;

            default: // past
                $builder->where('appointments.scheduled_at <', date('Y-m-d 00:00:00'))
                    ->orderBy('appointments.scheduled_at', 'DESC');
        }

        return $builder->findAll($limit);
    }

    /** Step 15: requests sorted by AI urgency (emergency first), then by time. */
    public static function urgentFirst(array $appointments): array
    {
        usort($appointments, static fn ($a, $b) => [self::URGENCY_RANK[$b['triage_level'] ?? ''] ?? 0, $a['scheduled_at']]
            <=> [self::URGENCY_RANK[$a['triage_level'] ?? ''] ?? 0, $b['scheduled_at']]);

        return $appointments;
    }

    /** All appointments of one owner's pets, newest first. */
    public function forOwner(int $ownerId): array
    {
        return $this->detailed()
            ->where('appointments.owner_id', $ownerId)
            ->orderBy('appointments.scheduled_at', 'DESC')
            ->findAll();
    }

    /** Booking times: '08:00' => '8:00 AM', '08:30' => '8:30 AM', and so on until '16:30' => '4:30 PM'. */
    public static function timeSlots(): array
    {
        $slots = [];

        for ($minutes = self::OPEN_HOUR * 60; $minutes < self::CLOSE_HOUR * 60; $minutes += self::SLOT_MINUTES) {
            $time         = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            $slots[$time] = date('g:i A', strtotime($time));
        }

        return $slots;
    }

    /** Next pending/confirmed visit of a pet ("Next Appointment" on the dashboard). */
    public function nextForPet(int $petId): ?array
    {
        return $this->detailed()
            ->where('appointments.pet_id', $petId)
            ->whereIn('appointments.status', ['pending', 'confirmed'])
            ->where('appointments.scheduled_at >=', date('Y-m-d H:i:s'))
            ->orderBy('appointments.scheduled_at')
            ->first();
    }

    /** True when the vet already has an active appointment overlapping [start, start + duration). */
    public function hasConflict(int $vetId, string $start, int $durationMinutes, ?int $ignoreId = null): bool
    {
        $startTs = strtotime($start);
        $endTs   = $startTs + $durationMinutes * 60;

        $builder = $this->where('vet_id', $vetId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('scheduled_at <', date('Y-m-d H:i:s', $endTs))
            ->where('scheduled_at >=', date('Y-m-d H:i:s', $startTs - 8 * 3600)); // longest visit is 8 h

        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        foreach ($builder->findAll() as $other) {
            $otherStart = strtotime($other['scheduled_at']);
            if ($otherStart + (int) $other['duration_minutes'] * 60 > $startTs) {
                return true;
            }
        }

        return false;
    }
}
