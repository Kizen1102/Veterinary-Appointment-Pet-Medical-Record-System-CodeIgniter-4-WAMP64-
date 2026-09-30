<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Accounts: Pet Owner (owner), Veterinarian (vet), Clinic Staff (admin).
 */
class UserModel extends Model
{
    public const ROLES = ['owner', 'vet', 'admin'];

    /** Minimum password length for all accounts. Change this one number to make it stricter (8 is recommended). */
    public const MIN_PASSWORD_LENGTH = 3;

    public const ROLE_LABELS = [
        'owner' => 'Pet Owner',
        'vet'   => 'Veterinarian',
        'admin' => 'Clinic Staff',
    ];

    protected $table          = 'users';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'role', 'full_name', 'email', 'password_hash', 'phone', 'address', 'avatar_path',
        'license_number', 'specialization', 'is_active', 'email_verified_at', 'last_login_at',
        'remember_token_hash', 'remember_expires_at', 'reset_token_hash', 'reset_expires_at',
    ];
    protected $validationRules = [
        'id'             => 'permit_empty|is_natural_no_zero',
        'role'           => 'required|in_list[owner,vet,admin]',
        'full_name'      => 'required|min_length[2]|max_length[120]',
        'email'          => 'required|valid_email|max_length[150]|is_unique[users.email,id,{id}]',
        'password_hash'  => 'required',
        'phone'          => 'permit_empty|max_length[30]',
        'license_number' => 'permit_empty|max_length[50]|is_unique[users.license_number,id,{id}]',
    ];
    protected $validationMessages = [
        'email' => ['is_unique' => 'This email address is already registered.'],
    ];
    protected $beforeInsert = ['normalizeEmail'];
    protected $beforeUpdate = ['normalizeEmail'];

    protected function normalizeEmail(array $data): array
    {
        if (isset($data['data']['email'])) {
            $data['data']['email'] = strtolower(trim($data['data']['email']));
        }

        return $data;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    /** True once the first Clinic Staff (admin) account exists — used by first-time setup. */
    public function hasAdmin(): bool
    {
        return $this->where('role', 'admin')->countAllResults() > 0;
    }

    public function vets(bool $activeOnly = true): array
    {
        $builder = $this->where('role', 'vet')->orderBy('full_name');
        if ($activeOnly) {
            $builder->where('is_active', 1);
        }

        return $builder->findAll();
    }

    public function owners(): array
    {
        return $this->where('role', 'owner')->orderBy('full_name')->findAll();
    }
}
