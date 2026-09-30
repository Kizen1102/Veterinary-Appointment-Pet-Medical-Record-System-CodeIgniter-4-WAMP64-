-- =====================================================================
--  PawRecord — Veterinary Clinic Appointment & Pet Medical Record System
--  STEP 1: Complete MySQL database schema
--
--  Target : XAMPP — MariaDB 10.4+ (bundled with XAMPP) or MySQL 8.0+
--  Import : phpMyAdmin → Import → choose this file → Go
--           or:  mysql -u root -p < database/pawrecord_schema.sql
--
--  Roles  : owner (Pet Owner) · vet (Veterinarian) · admin (Clinic Staff)
--
--  Feature → tables
--   1. AI Medical Information Chatbot ...... chat_conversations, chat_messages
--   2. Digital Pet Health Timeline ......... medical_records, vaccinations,
--                                            appointments  (+ view pet_health_timeline)
--   3. Medication Adherence Tracker ........ medications, medication_schedules,
--                                            medication_logs (+ view medication_adherence)
--   4. Symptom & Behavior Journal (AI) ..... journal_entries, journal_ai_summaries
--   Shared ................................. users, pets, notifications
-- =====================================================================

CREATE DATABASE IF NOT EXISTS pawrecord_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pawrecord_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop in reverse dependency order so the script can be re-run safely.
DROP VIEW  IF EXISTS medication_adherence;
DROP VIEW  IF EXISTS pet_health_timeline;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS chat_conversations;
DROP TABLE IF EXISTS journal_ai_summaries;
DROP TABLE IF EXISTS journal_entries;
DROP TABLE IF EXISTS medication_logs;
DROP TABLE IF EXISTS medication_schedules;
DROP TABLE IF EXISTS medications;
DROP TABLE IF EXISTS vaccinations;
DROP TABLE IF EXISTS medical_records;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS pets;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. users — every account (pet owners, veterinarians, clinic staff)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    role                 ENUM('owner','vet','admin') NOT NULL DEFAULT 'owner',
    full_name            VARCHAR(120)     NOT NULL,
    email                VARCHAR(150)     NOT NULL,
    password_hash        VARCHAR(255)     NOT NULL,               -- password_hash() / bcrypt
    phone                VARCHAR(30)      NULL,
    address              VARCHAR(255)     NULL,
    avatar_path          VARCHAR(255)     NULL,
    -- veterinarian-only details
    license_number       VARCHAR(50)      NULL,
    specialization       VARCHAR(100)     NULL,
    -- account state
    is_active            TINYINT(1)       NOT NULL DEFAULT 1,
    email_verified_at    DATETIME         NULL,
    last_login_at        DATETIME         NULL,
    -- "Remember me" and "Forgot password?" (store only hashes of tokens)
    remember_token_hash  CHAR(64)         NULL,
    remember_expires_at  DATETIME         NULL,
    reset_token_hash     CHAR(64)         NULL,
    reset_expires_at     DATETIME         NULL,
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at           DATETIME         NULL,                   -- soft delete
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_license (license_number),
    KEY idx_users_role_active (role, is_active),
    KEY idx_users_remember (remember_token_hash),
    KEY idx_users_reset (reset_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. pets — each animal belongs to one owner, optionally has a primary vet
-- ---------------------------------------------------------------------
CREATE TABLE pets (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    owner_id             INT UNSIGNED     NOT NULL,
    primary_vet_id       INT UNSIGNED     NULL,                   -- "Vet: Dr. Maria Santos"
    name                 VARCHAR(80)      NOT NULL,
    species              VARCHAR(40)      NOT NULL,               -- Dog, Cat, Rabbit ...
    breed                VARCHAR(80)      NULL,                   -- Persian, Aspin ...
    sex                  ENUM('male','female','unknown') NOT NULL DEFAULT 'unknown',
    is_neutered          TINYINT(1)       NOT NULL DEFAULT 0,     -- "(Spayed)" / "(Neutered)"
    birth_date           DATE             NULL,                   -- age is computed from this
    weight_kg            DECIMAL(6,2)     NULL,
    color_markings       VARCHAR(100)     NULL,
    microchip_number     VARCHAR(30)      NULL,
    blood_type           VARCHAR(20)      NULL,
    allergies            TEXT             NULL,
    chronic_conditions   TEXT             NULL,
    photo_path           VARCHAR(255)     NULL,
    notes                TEXT             NULL,
    is_deceased          TINYINT(1)       NOT NULL DEFAULT 0,
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at           DATETIME         NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pets_microchip (microchip_number),
    KEY idx_pets_owner (owner_id),
    KEY idx_pets_primary_vet (primary_vet_id),
    KEY idx_pets_species (species),
    CONSTRAINT fk_pets_owner       FOREIGN KEY (owner_id)       REFERENCES users (id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_pets_primary_vet FOREIGN KEY (primary_vet_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_pets_weight CHECK (weight_kg IS NULL OR weight_kg > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. appointments — clinic visits ("Next Appointment", "Book Appointment")
-- ---------------------------------------------------------------------
CREATE TABLE appointments (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    pet_id               INT UNSIGNED     NOT NULL,
    owner_id             INT UNSIGNED     NOT NULL,               -- who booked (denormalised for fast owner queries)
    vet_id               INT UNSIGNED     NULL,                   -- assigned veterinarian
    appointment_type     ENUM('consultation','wellness_exam','vaccination','follow_up',
                              'surgery','dental','grooming','emergency') NOT NULL DEFAULT 'consultation',
    title                VARCHAR(150)     NULL,                   -- "Annual Wellness Exam"
    scheduled_at         DATETIME         NOT NULL,               -- date + start time
    duration_minutes     SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    reason               TEXT             NULL,                   -- owner's description / symptoms
    status               ENUM('pending','confirmed','checked_in','completed','cancelled','no_show')
                                          NOT NULL DEFAULT 'pending',
    remind_owner         TINYINT(1)       NOT NULL DEFAULT 0,     -- "Remind me" button
    reminder_sent_at     DATETIME         NULL,
    cancellation_reason  VARCHAR(255)     NULL,
    staff_notes          TEXT             NULL,
    created_by           INT UNSIGNED     NULL,                   -- owner or staff who created it
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_appt_pet_date (pet_id, scheduled_at),
    KEY idx_appt_owner_date (owner_id, scheduled_at),
    KEY idx_appt_vet_date (vet_id, scheduled_at),                 -- vet schedule / double-booking checks
    KEY idx_appt_status_date (status, scheduled_at),              -- pending queue, reminders
    KEY idx_appt_created_by (created_by),
    CONSTRAINT fk_appt_pet        FOREIGN KEY (pet_id)     REFERENCES pets  (id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_appt_owner      FOREIGN KEY (owner_id)   REFERENCES users (id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_appt_vet        FOREIGN KEY (vet_id)     REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_appt_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_appt_duration CHECK (duration_minutes BETWEEN 5 AND 480)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. medical_records — consultations, treatments, surgeries, lab tests,
--    follow-ups. Core of the Digital Pet Health Timeline.
-- ---------------------------------------------------------------------
CREATE TABLE medical_records (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    pet_id               INT UNSIGNED     NOT NULL,
    vet_id               INT UNSIGNED     NULL,
    appointment_id       INT UNSIGNED     NULL,                   -- visit this record came from
    record_type          ENUM('consultation','treatment','surgery','lab_test',
                              'follow_up','emergency','other') NOT NULL DEFAULT 'consultation',
    visit_date           DATE             NOT NULL,
    title                VARCHAR(150)     NOT NULL,               -- shown on the timeline card
    chief_complaint      TEXT             NULL,
    -- vital signs
    weight_kg            DECIMAL(6,2)     NULL,
    temperature_c        DECIMAL(4,1)     NULL,
    heart_rate_bpm       SMALLINT UNSIGNED NULL,
    respiratory_rate     SMALLINT UNSIGNED NULL,
    -- clinical findings (vet language)
    findings             TEXT             NULL,
    diagnosis            TEXT             NULL,
    treatment            TEXT             NULL,
    lab_results          TEXT             NULL,
    vet_notes            TEXT             NULL,
    -- follow-up
    follow_up_date       DATE             NULL,
    follow_up_notes      VARCHAR(255)     NULL,
    -- AI plain-language explanation for the owner (feature 1)
    owner_summary        TEXT             NULL,
    owner_summary_at     DATETIME         NULL,
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_record_appointment (appointment_id),            -- one record per visit
    KEY idx_record_pet_date (pet_id, visit_date),                 -- timeline ordering
    KEY idx_record_vet_date (vet_id, visit_date),
    KEY idx_record_type (record_type),
    KEY idx_record_follow_up (follow_up_date),
    CONSTRAINT fk_record_pet         FOREIGN KEY (pet_id)         REFERENCES pets (id)         ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_record_vet         FOREIGN KEY (vet_id)         REFERENCES users (id)        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_record_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_record_temp CHECK (temperature_c IS NULL OR temperature_c BETWEEN 25.0 AND 45.0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. vaccinations — vaccine history + due dates ("All vaccines current")
-- ---------------------------------------------------------------------
CREATE TABLE vaccinations (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    pet_id               INT UNSIGNED     NOT NULL,
    vet_id               INT UNSIGNED     NULL,
    medical_record_id    INT UNSIGNED     NULL,                   -- visit where it was given
    vaccine_name         VARCHAR(100)     NOT NULL,               -- Anti-Rabies, FVRCP, DHPP ...
    dose_number          TINYINT UNSIGNED NULL,                   -- 1st, 2nd, booster ...
    batch_number         VARCHAR(50)      NULL,
    date_given           DATE             NOT NULL,
    next_due_date        DATE             NULL,
    notes                VARCHAR(255)     NULL,
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_vacc_pet_date (pet_id, date_given),
    KEY idx_vacc_due (next_due_date),                             -- "vaccines due" reminders
    KEY idx_vacc_vet (vet_id),
    KEY idx_vacc_record (medical_record_id),
    CONSTRAINT fk_vacc_pet    FOREIGN KEY (pet_id)            REFERENCES pets (id)            ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_vacc_vet    FOREIGN KEY (vet_id)            REFERENCES users (id)           ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_vacc_record FOREIGN KEY (medical_record_id) REFERENCES medical_records (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_vacc_dates CHECK (next_due_date IS NULL OR next_due_date >= date_given)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. medications — prescriptions / supplements (Medication Adherence Tracker)
-- ---------------------------------------------------------------------
CREATE TABLE medications (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    pet_id               INT UNSIGNED     NOT NULL,
    medical_record_id    INT UNSIGNED     NULL,                   -- prescribing visit
    prescribed_by        INT UNSIGNED     NULL,                   -- vet (NULL = added by owner, e.g. supplement)
    name                 VARCHAR(120)     NOT NULL,               -- "Omega-3 Supplement"
    dosage               VARCHAR(60)      NOT NULL,               -- "500mg", "1 tablet", "0.5 ml"
    form                 ENUM('tablet','capsule','liquid','injection','topical',
                              'drops','powder','other') NOT NULL DEFAULT 'tablet',
    route                VARCHAR(40)      NULL,                   -- oral, ear, eye, skin ...
    instructions         VARCHAR(255)     NULL,                   -- "with morning meal"
    purpose              VARCHAR(255)     NULL,                   -- why it was prescribed
    start_date           DATE             NOT NULL,
    end_date             DATE             NULL,                   -- NULL = ongoing
    total_doses          SMALLINT UNSIGNED NULL,                  -- for "treatment completion" progress
    status               ENUM('active','completed','discontinued','paused') NOT NULL DEFAULT 'active',
    discontinued_reason  VARCHAR(255)     NULL,
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_med_pet_status (pet_id, status),                      -- "3 active medications"
    KEY idx_med_record (medical_record_id),
    KEY idx_med_prescriber (prescribed_by),
    KEY idx_med_dates (start_date, end_date),
    CONSTRAINT fk_med_pet        FOREIGN KEY (pet_id)            REFERENCES pets (id)            ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_med_record     FOREIGN KEY (medical_record_id) REFERENCES medical_records (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_med_prescriber FOREIGN KEY (prescribed_by)     REFERENCES users (id)           ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_med_dates CHECK (end_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. medication_schedules — the daily dose times of a medication
--    (e.g. 08:00 and 20:00). Used to generate medication_logs each day.
-- ---------------------------------------------------------------------
CREATE TABLE medication_schedules (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    medication_id        INT UNSIGNED     NOT NULL,
    dose_time            TIME             NOT NULL,               -- 08:00:00
    days_of_week         SET('mon','tue','wed','thu','fri','sat','sun')
                                          NOT NULL DEFAULT 'mon,tue,wed,thu,fri,sat,sun',
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sched_med_time (medication_id, dose_time),
    KEY idx_sched_time (dose_time),
    CONSTRAINT fk_sched_med FOREIGN KEY (medication_id) REFERENCES medications (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. medication_logs — one row per scheduled dose.
--    pending → taken (owner taps "Mark") | missed (time passed) | skipped
-- ---------------------------------------------------------------------
CREATE TABLE medication_logs (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    medication_id        INT UNSIGNED     NOT NULL,
    schedule_id          INT UNSIGNED     NULL,
    scheduled_for        DATETIME         NOT NULL,               -- when the dose was due
    status               ENUM('pending','taken','missed','skipped') NOT NULL DEFAULT 'pending',
    taken_at             DATETIME         NULL,
    logged_by            INT UNSIGNED     NULL,                   -- owner who confirmed
    notes                VARCHAR(255)     NULL,                   -- "refused, retried later"
    missed_alert_sent_at DATETIME         NULL,                   -- missed-dose alert bookkeeping
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_log_dose (medication_id, scheduled_for),        -- never create the same dose twice
    KEY idx_log_status_due (status, scheduled_for),               -- "8:00 AM dose due" / missed-dose job
    KEY idx_log_schedule (schedule_id),
    KEY idx_log_logged_by (logged_by),
    CONSTRAINT fk_log_med       FOREIGN KEY (medication_id) REFERENCES medications (id)          ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_log_schedule  FOREIGN KEY (schedule_id)   REFERENCES medication_schedules (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_log_logged_by FOREIGN KEY (logged_by)     REFERENCES users (id)                ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. journal_entries — daily Symptom & Behavior Journal (one per pet per day)
--    Scores use 1–5 scales so changes can be charted and summarised by AI.
-- ---------------------------------------------------------------------
CREATE TABLE journal_entries (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    pet_id               INT UNSIGNED     NOT NULL,
    logged_by            INT UNSIGNED     NULL,
    entry_date           DATE             NOT NULL,
    appetite_score       TINYINT UNSIGNED NULL,                   -- 1 none … 3 normal … 5 much more than usual
    activity_score       TINYINT UNSIGNED NULL,                   -- 1 very low … 5 very high
    mood                 ENUM('happy','calm','playful','anxious','irritable','lethargic','withdrawn') NULL,
    sleep_hours          DECIMAL(4,1)     NULL,
    sleep_quality        TINYINT UNSIGNED NULL,                   -- 1 very poor … 5 very good
    water_intake         ENUM('less','normal','more') NULL,
    bowel_movement       ENUM('normal','diarrhea','constipated','none','bloody') NULL,
    vomited              TINYINT(1)       NOT NULL DEFAULT 0,
    weight_kg            DECIMAL(6,2)     NULL,
    symptoms             TEXT             NULL,                   -- free text: "scratching left ear"
    behavior_notes       TEXT             NULL,
    photo_path           VARCHAR(255)     NULL,
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_journal_pet_day (pet_id, entry_date),           -- "Daily health journal not yet logged"
    KEY idx_journal_logged_by (logged_by),
    KEY idx_journal_date (entry_date),
    CONSTRAINT fk_journal_pet       FOREIGN KEY (pet_id)    REFERENCES pets (id)  ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_journal_logged_by FOREIGN KEY (logged_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_journal_appetite CHECK (appetite_score IS NULL OR appetite_score BETWEEN 1 AND 5),
    CONSTRAINT chk_journal_activity CHECK (activity_score IS NULL OR activity_score BETWEEN 1 AND 5),
    CONSTRAINT chk_journal_sleep_q  CHECK (sleep_quality  IS NULL OR sleep_quality  BETWEEN 1 AND 5),
    CONSTRAINT chk_journal_sleep_h  CHECK (sleep_hours    IS NULL OR sleep_hours    BETWEEN 0 AND 24)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. journal_ai_summaries — AI summary of journal changes over a period,
--     prepared for the veterinarian (feature 4)
-- ---------------------------------------------------------------------
CREATE TABLE journal_ai_summaries (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    pet_id               INT UNSIGNED     NOT NULL,
    requested_by         INT UNSIGNED     NULL,
    appointment_id       INT UNSIGNED     NULL,                   -- visit the summary was prepared for
    period_start         DATE             NOT NULL,
    period_end           DATE             NOT NULL,
    entries_count        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    summary              TEXT             NOT NULL,               -- narrative for the vet
    notable_changes      JSON             NULL,                   -- ["appetite dropped 5→2 since Sep 24", ...]
    concern_level        ENUM('none','low','moderate','high') NOT NULL DEFAULT 'none',
    ai_model             VARCHAR(60)      NULL,                   -- model id, or 'rules' for the offline fallback
    reviewed_by          INT UNSIGNED     NULL,                   -- vet who read it
    reviewed_at          DATETIME         NULL,
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_jsum_pet_period (pet_id, period_end),
    KEY idx_jsum_appointment (appointment_id),
    KEY idx_jsum_requested_by (requested_by),
    KEY idx_jsum_reviewed_by (reviewed_by),
    CONSTRAINT fk_jsum_pet          FOREIGN KEY (pet_id)         REFERENCES pets (id)         ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_jsum_requested_by FOREIGN KEY (requested_by)   REFERENCES users (id)        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_jsum_appointment  FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_jsum_reviewed_by  FOREIGN KEY (reviewed_by)    REFERENCES users (id)        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_jsum_period CHECK (period_end >= period_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11. chat_conversations / chat_messages — AI Medical Information Chatbot
--     ("PawDoc AI" translates vet terms into plain language)
-- ---------------------------------------------------------------------
CREATE TABLE chat_conversations (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    user_id              INT UNSIGNED     NOT NULL,
    pet_id               INT UNSIGNED     NULL,                   -- optional context: which pet
    medical_record_id    INT UNSIGNED     NULL,                   -- optional context: "explain this record"
    title                VARCHAR(150)     NULL,                   -- e.g. first question asked
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_conv_user_updated (user_id, updated_at),
    KEY idx_conv_pet (pet_id),
    KEY idx_conv_record (medical_record_id),
    CONSTRAINT fk_conv_user   FOREIGN KEY (user_id)           REFERENCES users (id)           ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_conv_pet    FOREIGN KEY (pet_id)            REFERENCES pets (id)            ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_conv_record FOREIGN KEY (medical_record_id) REFERENCES medical_records (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_messages (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    conversation_id      INT UNSIGNED     NOT NULL,
    sender               ENUM('user','assistant') NOT NULL,
    content              TEXT             NOT NULL,
    ai_model             VARCHAR(60)      NULL,                   -- set on assistant messages
    input_tokens         INT UNSIGNED     NULL,                   -- usage tracking
    output_tokens        INT UNSIGNED     NULL,
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_msg_conv_created (conversation_id, created_at),
    CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES chat_conversations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 12. notifications — bell icon / "Today's Alerts"
--     (dose due, missed dose, appointment reminder, journal reminder, vaccine due)
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    user_id              INT UNSIGNED     NOT NULL,
    pet_id               INT UNSIGNED     NULL,
    type                 ENUM('medication_due','missed_dose','appointment_reminder',
                              'appointment_update','journal_reminder','vaccine_due',
                              'follow_up_due','journal_summary','general') NOT NULL DEFAULT 'general',
    title                VARCHAR(150)     NOT NULL,
    message              VARCHAR(500)     NULL,
    link_url             VARCHAR(255)     NULL,
    related_table        VARCHAR(40)      NULL,                   -- e.g. 'medication_logs'
    related_id           INT UNSIGNED     NULL,
    read_at              DATETIME         NULL,
    created_at           DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notif_user_unread (user_id, read_at, created_at),
    KEY idx_notif_pet (pet_id),
    KEY idx_notif_related (related_table, related_id),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notif_pet  FOREIGN KEY (pet_id)  REFERENCES pets (id)  ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- VIEWS
-- =====================================================================

-- Digital Pet Health Timeline: every health event of a pet in one list.
--   SELECT * FROM pet_health_timeline WHERE pet_id = 1 ORDER BY event_date DESC;
CREATE VIEW pet_health_timeline AS
    SELECT mr.pet_id,
           mr.visit_date            AS event_date,
           mr.record_type           AS event_type,
           mr.title                 AS title,
           mr.diagnosis             AS details,
           mr.vet_id,
           'medical_records'        AS source_table,
           mr.id                    AS source_id
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
     WHERE mr.follow_up_date IS NOT NULL;

-- Medication Adherence Tracker: dose counts and adherence % per medication.
CREATE VIEW medication_adherence AS
    SELECT m.id                                            AS medication_id,
           m.pet_id,
           m.name,
           m.status,
           m.total_doses,
           COUNT(l.id)                                     AS doses_scheduled,
           COALESCE(SUM(l.status = 'taken'), 0)            AS doses_taken,
           COALESCE(SUM(l.status = 'missed'), 0)           AS doses_missed,
           COALESCE(SUM(l.status = 'skipped'), 0)          AS doses_skipped,
           ROUND(100 * SUM(l.status = 'taken')
                 / NULLIF(SUM(l.status IN ('taken','missed','skipped')), 0), 1) AS adherence_pct,
           ROUND(100 * SUM(l.status = 'taken') / NULLIF(m.total_doses, 0), 1)   AS completion_pct
      FROM medications m
      LEFT JOIN medication_logs l ON l.medication_id = m.id
     GROUP BY m.id, m.pet_id, m.name, m.status, m.total_doses;
