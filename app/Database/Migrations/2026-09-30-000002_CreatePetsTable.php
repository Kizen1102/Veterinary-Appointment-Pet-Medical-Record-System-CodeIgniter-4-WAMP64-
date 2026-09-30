<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePetsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'owner_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'species'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'breed'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sex'        => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'birth_date' => ['type' => 'DATE', 'null' => true],
            'weight_kg'  => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'color'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'allergies'  => ['type' => 'TEXT', 'null' => true],
            'notes'      => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('owner_id');
        $this->forge->addForeignKey('owner_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pets');
    }

    public function down(): void
    {
        $this->forge->dropTable('pets', true);
    }
}
