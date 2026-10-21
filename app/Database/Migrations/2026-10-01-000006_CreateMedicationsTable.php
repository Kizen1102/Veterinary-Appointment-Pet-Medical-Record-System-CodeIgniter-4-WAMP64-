<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * medications — prescriptions and supplements (Medication Adherence Tracker).
 */
class CreateMedicationsTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'pet_id'              => $this->fk(),
            'medical_record_id'   => $this->fk(true),
            'prescribed_by'       => $this->fk(true), // NULL = added by the owner (e.g. a supplement)
            'name'                => ['type' => 'VARCHAR', 'constraint' => 120],
            'dosage'              => ['type' => 'VARCHAR', 'constraint' => 60],
            'form'                => ['type' => 'ENUM', 'constraint' => [
                'tablet', 'capsule', 'liquid', 'injection', 'topical', 'drops', 'powder', 'other',
            ], 'default' => 'tablet'],
            'route'               => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'instructions'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'purpose'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'start_date'          => ['type' => 'DATE'],
            'end_date'            => ['type' => 'DATE', 'null' => true],
            'total_doses'         => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['active', 'completed', 'discontinued', 'paused'], 'default' => 'active'],
            'discontinued_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ] + $this->timestamps());

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['pet_id', 'status'], false, false, 'idx_med_pet_status');
        $this->forge->addKey('medical_record_id', false, false, 'idx_med_record');
        $this->forge->addKey('prescribed_by', false, false, 'idx_med_prescriber');
        $this->forge->addKey(['start_date', 'end_date'], false, false, 'idx_med_dates');
        $this->foreignKey('pet_id', 'pets', 'CASCADE', 'fk_med_pet');
        $this->foreignKey('medical_record_id', 'medical_records', 'SET NULL', 'fk_med_record');
        $this->foreignKey('prescribed_by', 'users', 'SET NULL', 'fk_med_prescriber');
        $this->createTableWithEngine('medications');

        $this->addCheck('medications', 'chk_med_dates', 'end_date IS NULL OR end_date >= start_date');
    }

    public function down(): void
    {
        $this->forge->dropTable('medications', true);
    }
}
