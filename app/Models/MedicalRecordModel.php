<?php

namespace App\Models;

use CodeIgniter\Model;

class MedicalRecordModel extends Model
{
    public const TYPES = ['consultation', 'treatment', 'surgery', 'lab_test', 'follow_up', 'emergency', 'other'];

    protected $table         = 'medical_records';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pet_id', 'vet_id', 'appointment_id', 'record_type', 'visit_date', 'title', 'chief_complaint',
        'weight_kg', 'temperature_c', 'heart_rate_bpm', 'respiratory_rate', 'findings', 'diagnosis',
        'treatment', 'lab_results', 'vet_notes', 'follow_up_date', 'follow_up_notes',
        'owner_summary', 'owner_summary_at',
    ];
    protected $validationRules = [
        'pet_id'         => 'required|is_natural_no_zero',
        'vet_id'         => 'permit_empty|is_natural_no_zero',
        'appointment_id' => 'permit_empty|is_natural_no_zero',
        'record_type'    => 'required|in_list[consultation,treatment,surgery,lab_test,follow_up,emergency,other]',
        'visit_date'     => 'required|valid_date[Y-m-d]',
        'title'          => 'required|max_length[150]',
        'weight_kg'      => 'permit_empty|decimal|greater_than[0]',
        'temperature_c'  => 'permit_empty|decimal|greater_than_equal_to[25]|less_than_equal_to[45]',
        'follow_up_date' => 'permit_empty|valid_date[Y-m-d]',
    ];

    public function forPet(int $petId): array
    {
        return $this->select('medical_records.*, users.full_name AS vet_name')
            ->join('users', 'users.id = medical_records.vet_id', 'left')
            ->where('medical_records.pet_id', $petId)
            ->orderBy('visit_date', 'DESC')
            ->findAll();
    }
}
