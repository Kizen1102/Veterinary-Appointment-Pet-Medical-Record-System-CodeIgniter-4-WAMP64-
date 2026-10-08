<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * users — every account: Pet Owner (owner), Veterinarian (vet), Clinic Staff (admin).
 */
class CreateUsersTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'role'                => ['type' => 'ENUM', 'constraint' => ['owner', 'vet', 'admin'], 'default' => 'owner'],
            'full_name'           => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'               => ['type' => 'VARCHAR', 'constraint' => 150],
            'password_hash'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'phone'               => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'address'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'avatar_path'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            // veterinarian-only details
            'license_number'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'specialization'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            // account state
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'email_verified_at'   => ['type' => 'DATETIME', 'null' => true],
            'last_login_at'       => ['type' => 'DATETIME', 'null' => true],
            // "Remember me" and "Forgot password?" — only token hashes are stored
            'remember_token_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'remember_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'reset_token_hash'    => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'reset_expires_at'    => ['type' => 'DATETIME', 'null' => true],
        ] + $this->timestamps(true, true));

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email', 'uq_users_email');
        $this->forge->addUniqueKey('license_number', 'uq_users_license');
        $this->forge->addKey(['role', 'is_active'], false, false, 'idx_users_role_active');
        $this->forge->addKey('remember_token_hash', false, false, 'idx_users_remember');
        $this->forge->addKey('reset_token_hash', false, false, 'idx_users_reset');
        $this->createTableWithEngine('users');
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
