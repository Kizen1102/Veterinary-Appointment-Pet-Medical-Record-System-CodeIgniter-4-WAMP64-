<?php

namespace App\Models;

use CodeIgniter\Model;

class MedicalRecordModel extends Model
{
    protected $table         = 'medical_records';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pet_id', 'vet_id', 'appointment_id', 'visit_date', 'weight_kg', 'temperature_c',
        'symptoms', 'diagnosis', 'treatment', 'prescription', 'notes', 'follow_up_date', 'ai_summary',
    ];
    protected $validationRules = [
        'pet_id'         => 'required|is_natural_no_zero',
        'visit_date'     => 'required|valid_date[Y-m-d]',
        'weight_kg'      => 'permit_empty|decimal',
        'temperature_c'  => 'permit_empty|decimal',
        'follow_up_date' => 'permit_empty|valid_date[Y-m-d]',
    ];

    public function forPet(int $petId): array
    {
        return $this->select('medical_records.*, users.name AS vet_name')
            ->join('users', 'users.id = medical_records.vet_id', 'left')
            ->where('pet_id', $petId)
            ->orderBy('visit_date', 'DESC')
            ->findAll();
    }
}
