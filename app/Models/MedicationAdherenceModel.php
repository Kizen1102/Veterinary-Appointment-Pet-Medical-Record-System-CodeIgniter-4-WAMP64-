<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Read-only access to the medication_adherence view (MySQL / MariaDB).
 */
class MedicationAdherenceModel extends Model
{
    protected $table      = 'medication_adherence';
    protected $primaryKey = 'medication_id';
    protected $returnType = 'array';

    public function forPet(int $petId): array
    {
        return $this->where('pet_id', $petId)->orderBy('name')->findAll();
    }
}
