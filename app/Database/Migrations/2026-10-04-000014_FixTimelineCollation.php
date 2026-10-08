<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * Recreates the Health Timeline view with an explicit collation on every text column.
 * Fixes MySQL 8 error "#1271 Illegal mix of collations for operation 'UNION'",
 * which appears when the tables' text columns do not all use the same collation
 * (for example after importing a database exported from another computer).
 */
class FixTimelineCollation extends PawMigration
{
    public function up(): void
    {
        if (! $this->isMySQL()) {
            return;
        }

        $this->db->query(<<<'SQL'
            CREATE OR REPLACE VIEW pet_health_timeline AS
                SELECT mr.pet_id, mr.visit_date AS event_date,
                       mr.record_type COLLATE utf8mb4_unicode_ci AS event_type,
                       mr.title COLLATE utf8mb4_unicode_ci AS title,
                       mr.diagnosis COLLATE utf8mb4_unicode_ci AS details,
                       mr.vet_id, 'medical_records' COLLATE utf8mb4_unicode_ci AS source_table, mr.id AS source_id
                  FROM medical_records mr
                UNION ALL
                SELECT v.pet_id, v.date_given,
                       'vaccination' COLLATE utf8mb4_unicode_ci,
                       v.vaccine_name COLLATE utf8mb4_unicode_ci,
                       CONCAT('Next due: ', COALESCE(DATE_FORMAT(v.next_due_date, '%Y-%m-%d'), 'n/a')) COLLATE utf8mb4_unicode_ci,
                       v.vet_id, 'vaccinations' COLLATE utf8mb4_unicode_ci, v.id
                  FROM vaccinations v
                UNION ALL
                SELECT m.pet_id, m.start_date,
                       'medication_started' COLLATE utf8mb4_unicode_ci,
                       m.name COLLATE utf8mb4_unicode_ci,
                       CONCAT(m.dosage, COALESCE(CONCAT(' · ', m.instructions), '')) COLLATE utf8mb4_unicode_ci,
                       m.prescribed_by, 'medications' COLLATE utf8mb4_unicode_ci, m.id
                  FROM medications m
                UNION ALL
                SELECT mr.pet_id, mr.follow_up_date,
                       'follow_up_due' COLLATE utf8mb4_unicode_ci,
                       CONCAT('Follow-up: ', mr.title) COLLATE utf8mb4_unicode_ci,
                       mr.follow_up_notes COLLATE utf8mb4_unicode_ci,
                       mr.vet_id, 'medical_records' COLLATE utf8mb4_unicode_ci, mr.id
                  FROM medical_records mr
                 WHERE mr.follow_up_date IS NOT NULL
            SQL);
    }

    public function down(): void
    {
        // Nothing to undo: the view keeps working with the explicit collations.
    }
}
