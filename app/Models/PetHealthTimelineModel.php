<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Read-only access to the pet_health_timeline view (MySQL / MariaDB).
 */
class PetHealthTimelineModel extends Model
{
    protected $table      = 'pet_health_timeline';
    protected $primaryKey = 'source_id';
    protected $returnType = 'array';

    public function forPet(int $petId): array
    {
        return $this->where('pet_id', $petId)->orderBy('event_date', 'DESC')->findAll();
    }
}
