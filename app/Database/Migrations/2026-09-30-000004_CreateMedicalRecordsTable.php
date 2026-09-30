<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMedicalRecordsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pet_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'vet_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'appointment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'visit_date'     => ['type' => 'DATE'],
            'weight_kg'      => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'temperature_c'  => ['type' => 'DECIMAL', 'constraint' => '4,1', 'null' => true],
            'symptoms'       => ['type' => 'TEXT', 'null' => true],
            'diagnosis'      => ['type' => 'TEXT', 'null' => true],
            'treatment'      => ['type' => 'TEXT', 'null' => true],
            'prescription'   => ['type' => 'TEXT', 'null' => true],
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'follow_up_date' => ['type' => 'DATE', 'null' => true],
            'ai_summary'     => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('pet_id');
        $this->forge->addForeignKey('pet_id', 'pets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('vet_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('appointment_id', 'appointments', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('medical_records');
    }

    public function down(): void
    {
        $this->forge->dropTable('medical_records', true);
    }
}
