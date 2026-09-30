<?php

namespace App\Models;

use CodeIgniter\Model;

class VaccinationModel extends Model
{
    protected $table         = 'vaccinations';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['pet_id', 'vet_id', 'vaccine_name', 'date_given', 'next_due_date', 'batch_number', 'notes'];
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

    /**
     * Vaccinations due within the next $days days (including overdue ones).
     */
    public function dueSoon(int $days = 30, ?int $ownerId = null): array
    {
        $builder = $this->select('vaccinations.*, pets.name AS pet_name, users.name AS owner_name')
            ->join('pets', 'pets.id = vaccinations.pet_id')
            ->join('users', 'users.id = pets.owner_id')
            ->where('next_due_date IS NOT NULL')
            ->where('next_due_date <=', date('Y-m-d', strtotime("+{$days} days")))
            ->orderBy('next_due_date');

        if ($ownerId !== null) {
            $builder->where('pets.owner_id', $ownerId);
        }

        return $builder->findAll();
    }
}
