<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * medical_records — consultations, treatments, surgeries, lab tests, follow-ups.
 * Core of the Digital Pet Health Timeline.
 */
class CreateMedicalRecordsTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'pet_id'           => $this->fk(),
            'vet_id'           => $this->fk(true),
            'appointment_id'   => $this->fk(true),
            'record_type'      => ['type' => 'ENUM', 'constraint' => [
                'consultation', 'treatment', 'surgery', 'lab_test', 'follow_up', 'emergency', 'other',
            ], 'default' => 'consultation'],
            'visit_date'       => ['type' => 'DATE'],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'chief_complaint'  => ['type' => 'TEXT', 'null' => true],
            // vital signs
            'weight_kg'        => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'temperature_c'    => ['type' => 'DECIMAL', 'constraint' => '4,1', 'null' => true],
            'heart_rate_bpm'   => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'respiratory_rate' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            // clinical findings (vet language)
            'findings'         => ['type' => 'TEXT', 'null' => true],
            'diagnosis'        => ['type' => 'TEXT', 'null' => true],
            'treatment'        => ['type' => 'TEXT', 'null' => true],
            'lab_results'      => ['type' => 'TEXT', 'null' => true],
            'vet_notes'        => ['type' => 'TEXT', 'null' => true],
            // follow-up
            'follow_up_date'   => ['type' => 'DATE', 'null' => true],
            'follow_up_notes'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            // AI plain-language explanation for the owner
            'owner_summary'    => ['type' => 'TEXT', 'null' => true],
            'owner_summary_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->timestamps());

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('appointment_id', 'uq_record_appointment');
        $this->forge->addKey(['pet_id', 'visit_date'], false, false, 'idx_record_pet_date');
        $this->forge->addKey(['vet_id', 'visit_date'], false, false, 'idx_record_vet_date');
        $this->forge->addKey('record_type', false, false, 'idx_record_type');
        $this->forge->addKey('follow_up_date', false, false, 'idx_record_follow_up');
        $this->foreignKey('pet_id', 'pets', 'CASCADE', 'fk_record_pet');
        $this->foreignKey('vet_id', 'users', 'SET NULL', 'fk_record_vet');
        $this->foreignKey('appointment_id', 'appointments', 'SET NULL', 'fk_record_appointment');
        $this->createTableWithEngine('medical_records');

        $this->addCheck('medical_records', 'chk_record_temp', 'temperature_c IS NULL OR temperature_c BETWEEN 25.0 AND 45.0');
    }

    public function down(): void
    {
        $this->forge->dropTable('medical_records', true);
    }
}
