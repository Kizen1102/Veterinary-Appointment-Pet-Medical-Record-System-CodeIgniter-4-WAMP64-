-- =====================================================================
-- PawRecord: repair the "id" columns after a broken phpMyAdmin import
-- Fixes:  #1062 Duplicate entry '0' for key 'PRIMARY'
--         Invalid primary key: '0' is not allowed
-- (the tables lost AUTO_INCREMENT, so new rows were saved with id 0)
--
-- How to use: phpMyAdmin -> click pawrecord_db -> SQL tab -> paste this
-- whole file -> Go. Safe to run many times. Keeps all the data:
-- rows that got id 0 just receive the next free number.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------- users ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `users`);
UPDATE `users` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `users` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `users` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- pets ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `pets`);
UPDATE `pets` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pets' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `pets` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `pets` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- appointments ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `appointments`);
UPDATE `appointments` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `appointments` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `appointments` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- medical_records ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `medical_records`);
UPDATE `medical_records` SET id = (@n := @n + 1) WHERE id = 0;
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
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `medications`);
UPDATE `medications` SET id = (@n := @n + 1) WHERE id = 0;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medications' AND CONSTRAINT_TYPE = 'PRIMARY KEY') = 0,
               'ALTER TABLE `medications` ADD PRIMARY KEY (`id`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE `medications` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- ---------- medication_schedules ----------
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `medication_schedules`);
UPDATE `medication_schedules` SET id = (@n := @n + 1) WHERE id = 0;
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
SET @n := (SELECT COALESCE(MAX(id), 0) FROM `chat_conversations`);
UPDATE `chat_conversations` SET id = (@n := @n + 1) WHERE id = 0;
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
