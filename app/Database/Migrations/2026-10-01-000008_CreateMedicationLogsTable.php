<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * medication_logs — one row per scheduled dose:
 * pending → taken (owner taps "Mark") | missed (time passed) | skipped.
 */
class CreateMedicationLogsTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'medication_id'        => $this->fk(),
            'schedule_id'          => $this->fk(true),
            'scheduled_for'        => ['type' => 'DATETIME'],
            'status'               => ['type' => 'ENUM', 'constraint' => ['pending', 'taken', 'missed', 'skipped'], 'default' => 'pending'],
            'taken_at'             => ['type' => 'DATETIME', 'null' => true],
            'logged_by'            => $this->fk(true),
            'notes'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'missed_alert_sent_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->timestamps());

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['medication_id', 'scheduled_for'], 'uq_log_dose');
        $this->forge->addKey(['status', 'scheduled_for'], false, false, 'idx_log_status_due');
        $this->forge->addKey('schedule_id', false, false, 'idx_log_schedule');
        $this->forge->addKey('logged_by', false, false, 'idx_log_logged_by');
        $this->foreignKey('medication_id', 'medications', 'CASCADE', 'fk_log_med');
        $this->foreignKey('schedule_id', 'medication_schedules', 'SET NULL', 'fk_log_schedule');
        $this->foreignKey('logged_by', 'users', 'SET NULL', 'fk_log_logged_by');
        $this->createTableWithEngine('medication_logs');
    }

    public function down(): void
    {
        $this->forge->dropTable('medication_logs', true);
    }
}
