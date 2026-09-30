<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    public const ROLES = ['admin', 'vet', 'staff', 'owner'];

    protected $table          = 'users';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['name', 'email', 'password_hash', 'role', 'phone', 'address', 'is_active'];
    protected $validationRules = [
        'name'  => 'required|min_length[2]|max_length[100]',
        'email' => 'required|valid_email|max_length[150]|is_unique[users.email,id,{id}]',
        'role'  => 'required|in_list[admin,vet,staff,owner]',
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    public function vets(): array
    {
        return $this->where('role', 'vet')->where('is_active', 1)->orderBy('name')->findAll();
    }

    public function owners(): array
    {
        return $this->where('role', 'owner')->orderBy('name')->findAll();
    }
}
