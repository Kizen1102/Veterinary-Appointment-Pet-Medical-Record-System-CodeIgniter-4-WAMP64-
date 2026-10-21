<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * journal_ai_summaries — AI summary of journal changes over a period, for the veterinarian.
 */
class CreateJournalAiSummariesTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'pet_id'          => $this->fk(),
            'requested_by'    => $this->fk(true),
            'appointment_id'  => $this->fk(true),
            'period_start'    => ['type' => 'DATE'],
            'period_end'      => ['type' => 'DATE'],
            'entries_count'   => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'summary'         => ['type' => 'TEXT'],
            'notable_changes' => ['type' => 'JSON', 'null' => true],
            'concern_level'   => ['type' => 'ENUM', 'constraint' => ['none', 'low', 'moderate', 'high'], 'default' => 'none'],
            'ai_model'        => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'reviewed_by'     => $this->fk(true),
            'reviewed_at'     => ['type' => 'DATETIME', 'null' => true],
        ] + $this->timestamps(false));

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['pet_id', 'period_end'], false, false, 'idx_jsum_pet_period');
        $this->forge->addKey('appointment_id', false, false, 'idx_jsum_appointment');
        $this->forge->addKey('requested_by', false, false, 'idx_jsum_requested_by');
        $this->forge->addKey('reviewed_by', false, false, 'idx_jsum_reviewed_by');
        $this->foreignKey('pet_id', 'pets', 'CASCADE', 'fk_jsum_pet');
        $this->foreignKey('requested_by', 'users', 'SET NULL', 'fk_jsum_requested_by');
        $this->foreignKey('appointment_id', 'appointments', 'SET NULL', 'fk_jsum_appointment');
        $this->foreignKey('reviewed_by', 'users', 'SET NULL', 'fk_jsum_reviewed_by');
        $this->createTableWithEngine('journal_ai_summaries');

        $this->addCheck('journal_ai_summaries', 'chk_jsum_period', 'period_end >= period_start');
    }

    public function down(): void
    {
        $this->forge->dropTable('journal_ai_summaries', true);
    }
}
