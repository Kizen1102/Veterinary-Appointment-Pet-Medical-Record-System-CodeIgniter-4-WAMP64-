-- =====================================================================
-- PawRecord: recreate the 2 views after a failed phpMyAdmin import
-- (#1222 "different number of columns", #1271 "Illegal mix of collations",
--  #1064 on the "stand-in" tables).
--
-- How to use: phpMyAdmin -> click pawrecord_db -> SQL tab -> paste this
-- whole file (or Import tab -> choose this file) -> Go.
-- Safe to run many times. It does not touch the tables or their data.
-- =====================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- phpMyAdmin may have left "stand-in" TABLES with the views' names
DROP TABLE IF EXISTS pet_health_timeline;
DROP TABLE IF EXISTS medication_adherence;
DROP VIEW IF EXISTS pet_health_timeline;
DROP VIEW IF EXISTS medication_adherence;

-- Health Timeline: visits, vaccines, medicines and follow-ups of each pet
-- (8 columns in every SELECT; COLLATE on every text column)
CREATE VIEW pet_health_timeline AS
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
     WHERE mr.follow_up_date IS NOT NULL;

-- Medication adherence: doses given / missed / skipped per medicine
CREATE VIEW medication_adherence AS
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
     GROUP BY m.id, m.pet_id, m.name, m.status, m.total_doses;
