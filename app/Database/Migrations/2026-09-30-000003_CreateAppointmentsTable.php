<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppointmentsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pet_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'owner_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'vet_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'appointment_date' => ['type' => 'DATE'],
            'appointment_time' => ['type' => 'TIME'],
            'duration_minutes' => ['type' => 'INT', 'constraint' => 4, 'default' => 30],
            'reason'           => ['type' => 'TEXT'],
            // pending | confirmed | completed | cancelled
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            // AI triage: low | medium | high | emergency
            'triage_level'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'triage_notes'     => ['type' => 'TEXT', 'null' => true],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['vet_id', 'appointment_date']);
        $this->forge->addKey('status');
        $this->forge->addForeignKey('pet_id', 'pets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('owner_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('vet_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('appointments');
    }

    public function down(): void
    {
        $this->forge->dropTable('appointments', true);
    }
}
