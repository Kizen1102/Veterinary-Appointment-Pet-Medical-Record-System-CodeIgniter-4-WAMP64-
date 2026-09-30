<?php

namespace App\Models;

use CodeIgniter\Model;

class AppointmentModel extends Model
{
    public const TYPES    = ['consultation', 'wellness_exam', 'vaccination', 'follow_up', 'surgery', 'dental', 'grooming', 'emergency'];
    public const STATUSES = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'];

    protected $table         = 'appointments';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pet_id', 'owner_id', 'vet_id', 'appointment_type', 'title', 'scheduled_at', 'duration_minutes',
        'reason', 'status', 'remind_owner', 'reminder_sent_at', 'cancellation_reason', 'staff_notes', 'created_by',
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
