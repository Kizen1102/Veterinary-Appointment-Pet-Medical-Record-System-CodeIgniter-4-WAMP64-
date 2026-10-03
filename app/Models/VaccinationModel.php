<?php

namespace App\Models;

use CodeIgniter\Model;

class VaccinationModel extends Model
{
    protected $table         = 'vaccinations';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pet_id', 'vet_id', 'medical_record_id', 'vaccine_name', 'dose_number',
        'batch_number', 'date_given', 'next_due_date', 'notes',
    ];
    protected $validationRules = [
        'pet_id'        => 'required|is_natural_no_zero',
        'vaccine_name'  => 'required|max_length[100]',
        'date_given'    => 'required|valid_date[Y-m-d]',
        'next_due_date' => 'permit_empty|valid_date[Y-m-d]',
    ];

    public function forPet(int $petId): array
    {
        return $this->where('pet_id', $petId)->orderBy('date_given', 'DESC')->findAll();
    }

    /** "All vaccines current" badge: no vaccine of this pet is past its due date. */
    public function allCurrent(int $petId): bool
    {
        return $this->where('pet_id', $petId)
            ->where('next_due_date <', date('Y-m-d'))
            ->countAllResults() === 0;
    }

    /** Vaccines due within the next $days days (overdue ones included). */
    public function dueSoon(int $days = 30, ?int $ownerId = null): array
    {
        $builder = $this->select('vaccinations.*, pets.name AS pet_name')
            ->join('pets', 'pets.id = vaccinations.pet_id')
            ->where('next_due_date IS NOT NULL')
            ->where('next_due_date <=', date('Y-m-d', strtotime("+{$days} days")))
            ->orderBy('next_due_date');

        if ($ownerId !== null) {
            $builder->where('pets.owner_id', $ownerId);
        }

        return $builder->findAll();
    }
}
