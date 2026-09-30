<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTime;

class PetModel extends Model
{
    public const SEXES = ['male', 'female', 'unknown'];

    /** Species offered in the "Add Pet" form, with the emoji used as the pet's picture. */
    public const SPECIES = [
        'Dog'     => '🐶',
        'Cat'     => '🐱',
        'Rabbit'  => '🐰',
        'Bird'    => '🐦',
        'Hamster' => '🐹',
        'Other'   => '🐾',
    ];

    protected $table          = 'pets';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'owner_id', 'primary_vet_id', 'name', 'species', 'breed', 'sex', 'is_neutered', 'birth_date',
        'weight_kg', 'color_markings', 'microchip_number', 'blood_type', 'allergies',
        'chronic_conditions', 'photo_path', 'notes', 'is_deceased',
    ];
    protected $validationRules = [
        'id'               => 'permit_empty|is_natural_no_zero',
        'owner_id'         => 'required|is_natural_no_zero',
        'primary_vet_id'   => 'permit_empty|is_natural_no_zero',
        'name'             => 'required|max_length[80]',
        'species'          => 'required|max_length[40]',
        'breed'            => 'permit_empty|max_length[80]',
        'sex'              => 'permit_empty|in_list[male,female,unknown]',
        'birth_date'       => 'permit_empty|valid_date[Y-m-d]',
        'weight_kg'        => 'permit_empty|decimal|greater_than[0]',
        'microchip_number' => 'permit_empty|max_length[30]|is_unique[pets.microchip_number,id,{id}]',
    ];

    public function forOwner(int $ownerId): array
    {
        return $this->where('owner_id', $ownerId)->orderBy('name')->findAll();
    }

    /** Pets with owner and primary vet names (optionally only one owner's pets). */
    public function withPeople(?int $ownerId = null): array
    {
        $builder = $this->select('pets.*, owners.full_name AS owner_name, vets.full_name AS vet_name')
            ->join('users AS owners', 'owners.id = pets.owner_id')
            ->join('users AS vets', 'vets.id = pets.primary_vet_id', 'left')
            ->orderBy('pets.name');

        if ($ownerId !== null) {
            $builder->where('pets.owner_id', $ownerId);
        }

        return $builder->findAll();
    }

    /** "Female (Spayed)", "Male (Neutered)", "Male" — shown on the pet card. */
    public static function sexLabel(array $pet): string
    {
        if ($pet['sex'] === 'unknown') {
            return 'Sex unknown';
        }

        $label = ucfirst($pet['sex']);

        if ($pet['is_neutered']) {
            $label .= $pet['sex'] === 'female' ? ' (Spayed)' : ' (Neutered)';
        }

        return $label;
    }

    /** "5 yrs", "8 mos", "Unknown" — shown on the pet card. */
    public static function ageLabel(?string $birthDate): string
    {
        if (empty($birthDate)) {
            return 'Unknown';
        }

        $diff = (new DateTime($birthDate))->diff(new DateTime('today'));

        if ($diff->y > 0) {
            return $diff->y . ($diff->y === 1 ? ' yr' : ' yrs');
        }

        return max($diff->m, 0) . ($diff->m === 1 ? ' mo' : ' mos');
    }
}
