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
    public const MIN_PASSWORD_LENGTH = 8;

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

    /** The admin's Users page: one role ('' = everyone), searchable by name or email. */
    public function forAdmin(string $role = '', string $search = ''): array
    {
        $builder = $this->orderBy('is_active', 'DESC')->orderBy('full_name');

        if ($role !== '') {
            $builder->where('role', $role);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('full_name', $search)
                ->orLike('email', $search)
            ->groupEnd();
        }

        return $builder->findAll(200);
    }

    /** Number of accounts per role: ['owner' => 12, 'vet' => 3, 'admin' => 1]. */
    public function countByRole(): array
    {
        $rows = $this->select('role, COUNT(*) AS total')->groupBy('role')->findAll();

        return array_column($rows, 'total', 'role') + ['owner' => 0, 'vet' => 0, 'admin' => 0];
    }

    /** Ids of the active Clinic Staff (admin) accounts — they are told about new and cancelled requests. */
    public function staffIds(): array
    {
        return array_map('intval', array_column(
            $this->select('id')->where('role', 'admin')->where('is_active', 1)->findAll(),
            'id',
        ));
    }

    public function owners(): array
    {
        return $this->where('role', 'owner')->orderBy('full_name')->findAll();
    }
}
