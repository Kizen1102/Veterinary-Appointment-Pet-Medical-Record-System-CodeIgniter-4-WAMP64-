<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * journal_entries — daily Symptom & Behavior Journal (one entry per pet per day).
 * Scores use 1–5 scales so changes can be charted and summarised by AI.
 */
class CreateJournalEntriesTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'pet_id'         => $this->fk(),
            'logged_by'      => $this->fk(true),
            'entry_date'     => ['type' => 'DATE'],
            'appetite_score' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'activity_score' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'mood'           => ['type' => 'ENUM', 'constraint' => [
                'happy', 'calm', 'playful', 'anxious', 'irritable', 'lethargic', 'withdrawn',
            ], 'null' => true],
            'sleep_hours'    => ['type' => 'DECIMAL', 'constraint' => '4,1', 'null' => true],
            'sleep_quality'  => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'water_intake'   => ['type' => 'ENUM', 'constraint' => ['less', 'normal', 'more'], 'null' => true],
            'bowel_movement' => ['type' => 'ENUM', 'constraint' => ['normal', 'diarrhea', 'constipated', 'none', 'bloody'], 'null' => true],
            'vomited'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'weight_kg'      => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'symptoms'       => ['type' => 'TEXT', 'null' => true],
            'behavior_notes' => ['type' => 'TEXT', 'null' => true],
            'photo_path'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ] + $this->timestamps());

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['pet_id', 'entry_date'], 'uq_journal_pet_day');
        $this->forge->addKey('logged_by', false, false, 'idx_journal_logged_by');
        $this->forge->addKey('entry_date', false, false, 'idx_journal_date');
        $this->foreignKey('pet_id', 'pets', 'CASCADE', 'fk_journal_pet');
        $this->foreignKey('logged_by', 'users', 'SET NULL', 'fk_journal_logged_by');
        $this->createTableWithEngine('journal_entries');

        $this->addCheck('journal_entries', 'chk_journal_appetite', 'appetite_score IS NULL OR appetite_score BETWEEN 1 AND 5');
        $this->addCheck('journal_entries', 'chk_journal_activity', 'activity_score IS NULL OR activity_score BETWEEN 1 AND 5');
        $this->addCheck('journal_entries', 'chk_journal_sleep_q', 'sleep_quality IS NULL OR sleep_quality BETWEEN 1 AND 5');
        $this->addCheck('journal_entries', 'chk_journal_sleep_h', 'sleep_hours IS NULL OR sleep_hours BETWEEN 0 AND 24');
    }

    public function down(): void
    {
        $this->forge->dropTable('journal_entries', true);
    }
}
