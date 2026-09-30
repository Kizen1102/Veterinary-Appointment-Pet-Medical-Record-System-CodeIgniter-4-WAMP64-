<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            // admin | vet | staff | owner
            'role'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'owner'],
            'phone'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'address'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('role');
        $this->forge->createTable('users');
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
