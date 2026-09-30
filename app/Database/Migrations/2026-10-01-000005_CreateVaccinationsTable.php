<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * vaccinations — vaccine history and due dates ("All vaccines current").
 */
class CreateVaccinationsTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'pet_id'            => $this->fk(),
            'vet_id'            => $this->fk(true),
            'medical_record_id' => $this->fk(true),
            'vaccine_name'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'dose_number'       => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'batch_number'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'date_given'        => ['type' => 'DATE'],
            'next_due_date'     => ['type' => 'DATE', 'null' => true],
            'notes'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ] + $this->timestamps());

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['pet_id', 'date_given'], false, false, 'idx_vacc_pet_date');
        $this->forge->addKey('next_due_date', false, false, 'idx_vacc_due');
        $this->forge->addKey('vet_id', false, false, 'idx_vacc_vet');
        $this->forge->addKey('medical_record_id', false, false, 'idx_vacc_record');
        $this->foreignKey('pet_id', 'pets', 'CASCADE', 'fk_vacc_pet');
        $this->foreignKey('vet_id', 'users', 'SET NULL', 'fk_vacc_vet');
        $this->foreignKey('medical_record_id', 'medical_records', 'SET NULL', 'fk_vacc_record');
        $this->createTableWithEngine('vaccinations');

        $this->addCheck('vaccinations', 'chk_vacc_dates', 'next_due_date IS NULL OR next_due_date >= date_given');
    }

    public function down(): void
    {
        $this->forge->dropTable('vaccinations', true);
    }
}
