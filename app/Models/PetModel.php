<?php

namespace App\Models;

use CodeIgniter\Model;

class PetModel extends Model
{
    protected $table         = 'pets';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'owner_id', 'name', 'species', 'breed', 'sex', 'birth_date',
        'weight_kg', 'color', 'allergies', 'notes',
    ];
    protected $validationRules = [
        'owner_id'   => 'required|is_natural_no_zero',
        'name'       => 'required|max_length[100]',
        'species'    => 'required|max_length[50]',
        'sex'        => 'permit_empty|in_list[Male,Female,Unknown]',
        'birth_date' => 'permit_empty|valid_date[Y-m-d]',
        'weight_kg'  => 'permit_empty|decimal',
    ];

    /**
     * Pets joined with their owner's name. Pass an owner id to restrict the list.
     */
    public function withOwner(?int $ownerId = null): array
    {
        $builder = $this->select('pets.*, users.name AS owner_name')
            ->join('users', 'users.id = pets.owner_id')
            ->orderBy('pets.name');

        if ($ownerId !== null) {
            $builder->where('pets.owner_id', $ownerId);
        }

        return $builder->findAll();
    }

    public static function ageLabel(?string $birthDate): string
    {
        if (empty($birthDate)) {
            return 'Unknown';
        }
        $diff = (new \DateTime($birthDate))->diff(new \DateTime('today'));

        return $diff->y > 0 ? $diff->y . ' yr ' . $diff->m . ' mo' : $diff->m . ' mo';
    }
}
