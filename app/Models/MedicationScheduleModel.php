<?php

namespace App\Models;

use CodeIgniter\Model;

class MedicationScheduleModel extends Model
{
    protected $table         = 'medication_schedules';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['medication_id', 'dose_time', 'days_of_week'];
    protected $validationRules = [
        'medication_id' => 'required|is_natural_no_zero',
        'dose_time'     => 'required|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
    ];

    public function forMedication(int $medicationId): array
    {
        return $this->where('medication_id', $medicationId)->orderBy('dose_time')->findAll();
    }
}
