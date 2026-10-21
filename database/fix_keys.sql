-- =====================================================================
-- PawRecord: repair the "id" columns after a broken phpMyAdmin import
-- Fixes:  #1062 Duplicate entry '0' for key 'PRIMARY'
--         Invalid primary key: '0' is not allowed
-- (the tables lost AUTO_INCREMENT, so new rows were saved with id 0)
--
-- How to use: phpMyAdmin -> click pawrecord_db -> SQL tab -> paste this
-- whole file -> Go. Safe to run many times. Keeps all the data:
-- rows that got id 0 just receive the next free number, and the rows
-- that pointed at them (a pet's owner, an appointment's vet, ...) are
-- pointed at the new number too.
--
-- When ONE table has more than one row with id 0, those rows still get
-- new numbers, but their children cannot be told apart any more, so they
-- are left alone. The report at the bottom of this file lists what is
-- left: anything above 0 there has to be repaired by hand.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------- users ----------
SET @zeros := (SELECT COUNT(*) FROM `users` WHERE id = 0);
SET @n     := (SELECT COALESCE(MAX(id), 0) FROM `users`);
SET @newid := @n + 1;
UPDATE `users` SET id = (@n := @n + 1) WHERE id = 0;

-- the rows that pointed at the old id 0 follow it to its new number
UPDATE `pets`                 SET owner_id       = @newid WHERE @zeros = 1 AND owner_id       = 0;
UPDATE `pets`                 SET primary_vet_id = @newid WHERE @zeros = 1 AND primary_vet_id = 0;
UPDATE `appointments`         SET owner_id       = @newid WHERE @zeros = 1 AND owner_id       = 0;
UPDATE `appointments`         SET vet_id         = @newid WHERE @zeros = 1 AND vet_id         = 0;
UPDATE `medical_records`      SET vet_id         = @newid WHERE @zeros = 1 AND vet_id         = 0;
UPDATE `vaccinations`         SET vet_id         = @newid WHERE @zeros = 1 AND vet_id         = 0;
UPDATE `chat_conversations`   SET user_id        = @newid WHERE @zeros = 1 AND user_id        = 0;
UPDATE `notifications`        SET user_id        = @newid WHERE @zeros = 1 AND user_id        = 0;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `users` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `users` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- pets ----------
SET @zeros := (SELECT COUNT(*) FROM `pets` WHERE id = 0);
SET @n     := (SELECT COALESCE(MAX(id), 0) FROM `pets`);
SET @newid := @n + 1;
UPDATE `pets` SET id = (@n := @n + 1) WHERE id = 0;

UPDATE `appointments`         SET pet_id = @newid WHERE @zeros = 1 AND pet_id = 0;
UPDATE `medical_records`      SET pet_id = @newid WHERE @zeros = 1 AND pet_id = 0;
UPDATE `vaccinations`         SET pet_id = @newid WHERE @zeros = 1 AND pet_id = 0;
UPDATE `medications`          SET pet_id = @newid WHERE @zeros = 1 AND pet_id = 0;
UPDATE `journal_entries`      SET pet_id = @newid WHERE @zeros = 1 AND pet_id = 0;
UPDATE `journal_ai_summaries` SET pet_id = @newid WHERE @zeros = 1 AND pet_id = 0;
UPDATE `chat_conversations`   SET pet_id = @newid WHERE @zeros = 1 AND pet_id = 0;
UPDATE `notifications`        SET pet_id = @newid WHERE @zeros = 1 AND pet_id = 0;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pets' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `pets` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `pets` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- appointments ----------
SET @zeros := (SELECT COUNT(*) FROM `appointments` WHERE id = 0);
SET @n     := (SELECT COALESCE(MAX(id), 0) FROM `appointments`);
SET @newid := @n + 1;
UPDATE `appointments` SET id = (@n := @n + 1) WHERE id = 0;

UPDATE `medical_records`      SET appointment_id = @newid WHERE @zeros = 1 AND appointment_id = 0;
UPDATE `journal_ai_summaries` SET appointment_id = @newid WHERE @zeros = 1 AND appointment_id = 0;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `appointments` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `appointments` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- medical_records ----------
SET @zeros := (SELECT COUNT(*) FROM `medical_records` WHERE id = 0);
SET @n     := (SELECT COALESCE(MAX(id), 0) FROM `medical_records`);
SET @newid := @n + 1;
UPDATE `medical_records` SET id = (@n := @n + 1) WHERE id = 0;

UPDATE `vaccinations`       SET medical_record_id = @newid WHERE @zeros = 1 AND medical_record_id = 0;
UPDATE `medications`        SET medical_record_id = @newid WHERE @zeros = 1 AND medical_record_id = 0;
UPDATE `chat_conversations` SET medical_record_id = @newid WHERE @zeros = 1 AND medical_record_id = 0;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medical_records' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `medical_records` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `medical_records` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- vaccinations ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `vaccinations`);
UPDATE `vaccinations` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vaccinations' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `vaccinations` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `vaccinations` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- medications ----------
SET @zeros := (SELECT COUNT(*) FROM `medications` WHERE id = 0);
SET @n     := (SELECT COALESCE(MAX(id), 0) FROM `medications`);
SET @newid := @n + 1;
UPDATE `medications` SET id = (@n := @n + 1) WHERE id = 0;

UPDATE `medication_schedules` SET medication_id = @newid WHERE @zeros = 1 AND medication_id = 0;
UPDATE `medication_logs`      SET medication_id = @newid WHERE @zeros = 1 AND medication_id = 0;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medications' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `medications` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `medications` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- medication_schedules ----------
SET @zeros := (SELECT COUNT(*) FROM `medication_schedules` WHERE id = 0);
SET @n     := (SELECT COALESCE(MAX(id), 0) FROM `medication_schedules`);
SET @newid := @n + 1;
UPDATE `medication_schedules` SET id = (@n := @n + 1) WHERE id = 0;

UPDATE `medication_logs` SET schedule_id = @newid WHERE @zeros = 1 AND schedule_id = 0;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medication_schedules' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `medication_schedules` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `medication_schedules` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- medication_logs ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `medication_logs`);
UPDATE `medication_logs` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medication_logs' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `medication_logs` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `medication_logs` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- journal_entries ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `journal_entries`);
UPDATE `journal_entries` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'journal_entries' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `journal_entries` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `journal_entries` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- journal_ai_summaries ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `journal_ai_summaries`);
UPDATE `journal_ai_summaries` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'journal_ai_summaries' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `journal_ai_summaries` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `journal_ai_summaries` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- chat_conversations ----------
SET @zeros := (SELECT COUNT(*) FROM `chat_conversations` WHERE id = 0);
SET @n     := (SELECT COALESCE(MAX(id), 0) FROM `chat_conversations`);
SET @newid := @n + 1;
UPDATE `chat_conversations` SET id = (@n := @n + 1) WHERE id = 0;

UPDATE `chat_messages` SET conversation_id = @newid WHERE @zeros = 1 AND conversation_id = 0;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat_conversations' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `chat_conversations` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `chat_conversations` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- chat_messages ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `chat_messages`);
UPDATE `chat_messages` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat_messages' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `chat_messages` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `chat_messages` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- notifications ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `notifications`);
UPDATE `notifications` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `notifications` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `notifications` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- migrations ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `migrations`);
UPDATE `migrations` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `migrations` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `migrations` MODIFY `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Report: every number below must be 0. Anything else is a row that
-- still points at the old id 0 and has to be linked again by hand
-- (open the table in phpMyAdmin and choose the right owner, pet or vet).
-- =====================================================================
SELECT 'pets.owner_id'                   AS points_at_id_0, COUNT(*) AS rows_left FROM `pets`                 WHERE owner_id          = 0
UNION ALL SELECT 'pets.primary_vet_id',            COUNT(*) FROM `pets`                 WHERE primary_vet_id    = 0
UNION ALL SELECT 'appointments.pet_id',            COUNT(*) FROM `appointments`         WHERE pet_id            = 0
UNION ALL SELECT 'appointments.owner_id',          COUNT(*) FROM `appointments`         WHERE owner_id          = 0
UNION ALL SELECT 'appointments.vet_id',            COUNT(*) FROM `appointments`         WHERE vet_id            = 0
UNION ALL SELECT 'medical_records.pet_id',         COUNT(*) FROM `medical_records`      WHERE pet_id            = 0
UNION ALL SELECT 'medical_records.vet_id',         COUNT(*) FROM `medical_records`      WHERE vet_id            = 0
UNION ALL SELECT 'medical_records.appointment_id', COUNT(*) FROM `medical_records`      WHERE appointment_id    = 0
UNION ALL SELECT 'vaccinations.pet_id',            COUNT(*) FROM `vaccinations`         WHERE pet_id            = 0
UNION ALL SELECT 'medications.pet_id',             COUNT(*) FROM `medications`          WHERE pet_id            = 0
UNION ALL SELECT 'medication_schedules.med_id',    COUNT(*) FROM `medication_schedules` WHERE medication_id     = 0
UNION ALL SELECT 'medication_logs.medication_id',  COUNT(*) FROM `medication_logs`      WHERE medication_id     = 0
UNION ALL SELECT 'journal_entries.pet_id',         COUNT(*) FROM `journal_entries`      WHERE pet_id            = 0
UNION ALL SELECT 'journal_ai_summaries.pet_id',    COUNT(*) FROM `journal_ai_summaries` WHERE pet_id            = 0
UNION ALL SELECT 'chat_conversations.user_id',     COUNT(*) FROM `chat_conversations`   WHERE user_id           = 0
UNION ALL SELECT 'chat_messages.conversation_id',  COUNT(*) FROM `chat_messages`        WHERE conversation_id   = 0
UNION ALL SELECT 'notifications.user_id',          COUNT(*) FROM `notifications`        WHERE user_id           = 0;
