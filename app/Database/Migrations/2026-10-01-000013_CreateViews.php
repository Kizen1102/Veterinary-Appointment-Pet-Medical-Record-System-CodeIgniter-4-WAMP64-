<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * Views used by the Health Timeline and the Medication Adherence Tracker.
 * MySQL / MariaDB only (the SQLite test database reads the tables directly).
 */
class CreateViews extends PawMigration
{
    public function up(): void
    {
        if (! $this->isMySQL()) {
            return;
        }

        // Text typed inside the views (like 'vaccination') must use the same collation as the tables,
        // otherwise MySQL 8 refuses the UNION: "#1271 Illegal mix of collations".
        $this->db->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

        // Digital Pet Health Timeline: every health event of a pet in one list.
        $this->db->query(<<<'SQL'
            CREATE OR REPLACE VIEW pet_health_timeline AS
                SELECT mr.pet_id, mr.visit_date AS event_date, mr.record_type AS event_type, mr.title AS title,
                       mr.diagnosis AS details, mr.vet_id, 'medical_records' AS source_table, mr.id AS source_id
                  FROM medical_records mr
                UNION ALL
                SELECT v.pet_id, v.date_given, 'vaccination', v.vaccine_name,
                       CONCAT('Next due: ', COALESCE(DATE_FORMAT(v.next_due_date, '%Y-%m-%d'), 'n/a')),
                       v.vet_id, 'vaccinations', v.id
                  FROM vaccinations v
                UNION ALL
                SELECT m.pet_id, m.start_date, 'medication_started', m.name,
                       CONCAT(m.dosage, COALESCE(CONCAT(' · ', m.instructions), '')),
                       m.prescribed_by, 'medications', m.id
                  FROM medications m
                UNION ALL
                SELECT mr.pet_id, mr.follow_up_date, 'follow_up_due', CONCAT('Follow-up: ', mr.title),
                       mr.follow_up_notes, mr.vet_id, 'medical_records', mr.id
                  FROM medical_records mr
                 WHERE mr.follow_up_date IS NOT NULL
            SQL);

        // Medication Adherence Tracker: dose counts and adherence % per medication.
        $this->db->query(<<<'SQL'
            CREATE OR REPLACE VIEW medication_adherence AS
                SELECT m.id AS medication_id, m.pet_id, m.name, m.status, m.total_doses,
                       COUNT(l.id)                            AS doses_scheduled,
                       COALESCE(SUM(l.status = 'taken'), 0)   AS doses_taken,
                       COALESCE(SUM(l.status = 'missed'), 0)  AS doses_missed,
                       COALESCE(SUM(l.status = 'skipped'), 0) AS doses_skipped,
                       ROUND(100 * SUM(l.status = 'taken')
                             / NULLIF(SUM(l.status IN ('taken','missed','skipped')), 0), 1) AS adherence_pct,
                       ROUND(100 * SUM(l.status = 'taken') / NULLIF(m.total_doses, 0), 1)   AS completion_pct
                  FROM medications m
                  LEFT JOIN medication_logs l ON l.medication_id = m.id
                 GROUP BY m.id, m.pet_id, m.name, m.status, m.total_doses
            SQL);
    }

    public function down(): void
    {
        if (! $this->isMySQL()) {
            return;
        }

        $this->db->query('DROP VIEW IF EXISTS medication_adherence');
        $this->db->query('DROP VIEW IF EXISTS pet_health_timeline');
    }
}
