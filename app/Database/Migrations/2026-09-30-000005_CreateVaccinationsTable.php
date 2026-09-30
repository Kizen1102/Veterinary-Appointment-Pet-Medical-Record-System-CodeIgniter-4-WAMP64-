<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVaccinationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pet_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'vet_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'vaccine_name'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'date_given'    => ['type' => 'DATE'],
            'next_due_date' => ['type' => 'DATE', 'null' => true],
            'batch_number'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('pet_id');
        $this->forge->addKey('next_due_date');
        $this->forge->addForeignKey('pet_id', 'pets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('vet_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('vaccinations');
    }

    public function down(): void
    {
        $this->forge->dropTable('vaccinations', true);
    }
}
