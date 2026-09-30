<?php

namespace App\Models;

use CodeIgniter\Model;

class AppointmentModel extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    protected $table         = 'appointments';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pet_id', 'owner_id', 'vet_id', 'appointment_date', 'appointment_time',
        'duration_minutes', 'reason', 'status', 'triage_level', 'triage_notes', 'notes',
    ];
    protected $validationRules = [
        'pet_id'           => 'required|is_natural_no_zero',
        'owner_id'         => 'required|is_natural_no_zero',
        'vet_id'           => 'permit_empty|is_natural_no_zero',
        'appointment_date' => 'required|valid_date[Y-m-d]',
        'appointment_time' => 'required|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
        'duration_minutes' => 'permit_empty|in_list[15,30,45,60,90,120]',
        'reason'           => 'required|min_length[3]',
        'status'           => 'permit_empty|in_list[pending,confirmed,completed,cancelled]',
    ];

    /**
     * Base query with pet, owner and vet names attached.
     */
    public function detailed()
    {
        return $this->select('appointments.*, pets.name AS pet_name, pets.species,
                owners.name AS owner_name, vets.name AS vet_name')
            ->join('pets', 'pets.id = appointments.pet_id')
            ->join('users AS owners', 'owners.id = appointments.owner_id')
            ->join('users AS vets', 'vets.id = appointments.vet_id', 'left');
    }

    /**
     * True when the vet already has a non-cancelled appointment overlapping the slot.
     */
    public function hasConflict(int $vetId, string $date, string $time, int $duration, ?int $ignoreId = null): bool
    {
        $start = strtotime($date . ' ' . $time);
        $end   = $start + $duration * 60;

        $builder = $this->where('vet_id', $vetId)
            ->where('appointment_date', $date)
            ->where('status !=', 'cancelled');

        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        foreach ($builder->findAll() as $appt) {
            $otherStart = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']);
            $otherEnd   = $otherStart + (int) $appt['duration_minutes'] * 60;
            if ($start < $otherEnd && $otherStart < $end) {
                return true;
            }
        }

        return false;
    }
}
