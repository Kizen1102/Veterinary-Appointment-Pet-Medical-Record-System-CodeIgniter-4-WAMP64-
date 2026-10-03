<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * notifications — bell icon and "Today's Alerts"
 * (dose due, missed dose, appointment reminder, journal reminder, vaccine due …).
 */
class CreateNotificationsTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'user_id'       => $this->fk(),
            'pet_id'        => $this->fk(true),
            'type'          => ['type' => 'ENUM', 'constraint' => [
                'medication_due', 'missed_dose', 'appointment_reminder', 'appointment_update',
                'journal_reminder', 'vaccine_due', 'follow_up_due', 'journal_summary', 'general',
            ], 'default' => 'general'],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'message'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'link_url'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'related_table' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'related_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'read_at'       => ['type' => 'DATETIME', 'null' => true],
        ] + $this->timestamps(false));

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'read_at', 'created_at'], false, false, 'idx_notif_user_unread');
        $this->forge->addKey('pet_id', false, false, 'idx_notif_pet');
        $this->forge->addKey(['related_table', 'related_id'], false, false, 'idx_notif_related');
        $this->foreignKey('user_id', 'users', 'CASCADE', 'fk_notif_user');
        $this->foreignKey('pet_id', 'pets', 'CASCADE', 'fk_notif_pet');
        $this->createTableWithEngine('notifications');
    }

    public function down(): void
    {
        $this->forge->dropTable('notifications', true);
    }
}
