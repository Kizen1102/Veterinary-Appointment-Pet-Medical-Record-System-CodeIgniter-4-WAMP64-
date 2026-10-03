<?php

namespace App\Models;

use CodeIgniter\Model;

class MedicationModel extends Model
{
    public const FORMS    = ['tablet', 'capsule', 'liquid', 'injection', 'topical', 'drops', 'powder', 'other'];
    public const STATUSES = ['active', 'completed', 'discontinued', 'paused'];

    protected $table         = 'medications';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pet_id', 'medical_record_id', 'prescribed_by', 'name', 'dosage', 'form', 'route', 'instructions',
        'purpose', 'start_date', 'end_date', 'total_doses', 'status', 'discontinued_reason',
    ];
    protected $validationRules = [
        'pet_id'      => 'required|is_natural_no_zero',
        'name'        => 'required|max_length[120]',
        'dosage'      => 'required|max_length[60]',
        'form'        => 'permit_empty|in_list[tablet,capsule,liquid,injection,topical,drops,powder,other]',
        'start_date'  => 'required|valid_date[Y-m-d]',
        'end_date'    => 'permit_empty|valid_date[Y-m-d]',
        'total_doses' => 'permit_empty|is_natural_no_zero',
        'status'      => 'permit_empty|in_list[active,completed,discontinued,paused]',
    ];

    public function activeForPet(int $petId): array
    {
        return $this->where('pet_id', $petId)->where('status', 'active')->orderBy('name')->findAll();
    }
}
