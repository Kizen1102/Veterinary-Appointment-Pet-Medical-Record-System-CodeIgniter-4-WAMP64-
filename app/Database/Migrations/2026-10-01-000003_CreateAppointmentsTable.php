<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * appointments — clinic visits ("Next Appointment", "Book Appointment").
 */
class CreateAppointmentsTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'pet_id'              => $this->fk(),
            'owner_id'            => $this->fk(),
            'vet_id'              => $this->fk(true),
            'appointment_type'    => ['type' => 'ENUM', 'constraint' => [
                'consultation', 'wellness_exam', 'vaccination', 'follow_up',
                'surgery', 'dental', 'grooming', 'emergency',
            ], 'default' => 'consultation'],
            'title'               => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'scheduled_at'        => ['type' => 'DATETIME'],
            'duration_minutes'    => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 30],
            'reason'              => ['type' => 'TEXT', 'null' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => [
                'pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show',
            ], 'default' => 'pending'],
            'remind_owner'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'reminder_sent_at'    => ['type' => 'DATETIME', 'null' => true],
            'cancellation_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'staff_notes'         => ['type' => 'TEXT', 'null' => true],
            'created_by'          => $this->fk(true),
        ] + $this->timestamps());

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['pet_id', 'scheduled_at'], false, false, 'idx_appt_pet_date');
        $this->forge->addKey(['owner_id', 'scheduled_at'], false, false, 'idx_appt_owner_date');
        $this->forge->addKey(['vet_id', 'scheduled_at'], false, false, 'idx_appt_vet_date');
        $this->forge->addKey(['status', 'scheduled_at'], false, false, 'idx_appt_status_date');
        $this->forge->addKey('created_by', false, false, 'idx_appt_created_by');
        $this->foreignKey('pet_id', 'pets', 'CASCADE', 'fk_appt_pet');
        $this->foreignKey('owner_id', 'users', 'CASCADE', 'fk_appt_owner');
        $this->foreignKey('vet_id', 'users', 'SET NULL', 'fk_appt_vet');
        $this->foreignKey('created_by', 'users', 'SET NULL', 'fk_appt_created_by');
        $this->createTableWithEngine('appointments');

        $this->addCheck('appointments', 'chk_appt_duration', 'duration_minutes BETWEEN 5 AND 480');
    }

    public function down(): void
    {
        $this->forge->dropTable('appointments', true);
    }
}
