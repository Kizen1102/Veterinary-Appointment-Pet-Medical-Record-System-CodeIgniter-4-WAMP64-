<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * medication_schedules — daily dose times of a medication (e.g. 08:00 and 20:00).
 */
class CreateMedicationSchedulesTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'medication_id' => $this->fk(),
            'dose_time'     => ['type' => 'TIME'],
            'days_of_week'  => ['type' => 'SET', 'constraint' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
                'default' => 'mon,tue,wed,thu,fri,sat,sun'],
        ] + $this->timestamps(false));

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['medication_id', 'dose_time'], 'uq_sched_med_time');
        $this->forge->addKey('dose_time', false, false, 'idx_sched_time');
        $this->foreignKey('medication_id', 'medications', 'CASCADE', 'fk_sched_med');
        $this->createTableWithEngine('medication_schedules');
    }

    public function down(): void
    {
        $this->forge->dropTable('medication_schedules', true);
    }
}
