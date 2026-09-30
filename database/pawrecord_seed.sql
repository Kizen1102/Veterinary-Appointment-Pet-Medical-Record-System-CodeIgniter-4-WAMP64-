-- =====================================================================
--  PawRecord — demo data (optional). Import AFTER pawrecord_schema.sql.
--  All demo accounts use the password:  password123
--  Dates are relative to today so the dashboard always has "today" data.
-- =====================================================================

USE pawrecord_db;

-- Users (the three demo accounts shown on the login screen + a second vet)
INSERT INTO users (id, role, full_name, email, password_hash, phone, address, license_number, specialization, email_verified_at) VALUES
 (1, 'owner', 'Maria Dela Cruz',   'maria@email.com',        '$2y$12$mTjMRUh8yGBWMH2fzfb24OgBye7UccjBCnoyCgdkIAz0lcOEw0OFC', '09171234567', 'Quezon City', NULL, NULL, NOW()),
 (2, 'vet',   'Dr. Maria Santos',  'dr.santos@pawcare.com',  '$2y$12$mTjMRUh8yGBWMH2fzfb24OgBye7UccjBCnoyCgdkIAz0lcOEw0OFC', '09170000002', NULL, 'PRC-VET-0012345', 'Small Animal Medicine', NOW()),
 (3, 'admin', 'Admin User',        'admin@pawcare.com',      '$2y$12$mTjMRUh8yGBWMH2fzfb24OgBye7UccjBCnoyCgdkIAz0lcOEw0OFC', '09170000001', NULL, NULL, NULL, NOW()),
 (4, 'vet',   'Dr. Jose Reyes',    'dr.reyes@pawcare.com',   '$2y$12$mTjMRUh8yGBWMH2fzfb24OgBye7UccjBCnoyCgdkIAz0lcOEw0OFC', '09170000003', NULL, 'PRC-VET-0023456', 'Surgery', NOW());

-- Pets
INSERT INTO pets (id, owner_id, primary_vet_id, name, species, breed, sex, is_neutered, birth_date, weight_kg, color_markings, allergies) VALUES
 (1, 1, 2, 'Luna',   'Cat', 'Persian', 'female', 1, DATE_SUB(CURDATE(), INTERVAL 5 YEAR),  4.20, 'White and grey', 'Chicken protein'),
 (2, 1, 2, 'Bantay', 'Dog', 'Aspin',   'male',   0, DATE_SUB(CURDATE(), INTERVAL 3 YEAR), 12.50, 'Brown', NULL);

-- Appointments: a past completed visit and the upcoming wellness exam
INSERT INTO appointments (id, pet_id, owner_id, vet_id, appointment_type, title, scheduled_at, duration_minutes, reason, status, created_by) VALUES
 (1, 1, 1, 2, 'consultation',  'Skin itching check',    TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 20 DAY), '09:30:00'), 30, 'Scratching and red skin on the neck', 'completed', 1),
 (2, 1, 1, 2, 'wellness_exam', 'Annual Wellness Exam',  TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 14 DAY), '10:00:00'), 45, 'Yearly check-up',                    'confirmed', 1);

-- Medical records (timeline)
INSERT INTO medical_records (id, pet_id, vet_id, appointment_id, record_type, visit_date, title, chief_complaint, weight_kg, temperature_c, heart_rate_bpm,
                             findings, diagnosis, treatment, follow_up_date, follow_up_notes) VALUES
 (1, 1, 2, 1, 'consultation', DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Skin itching check', 'Scratching and red skin on the neck', 4.20, 38.6, 180,
    'Erythematous papules on dorsal neck, no parasites seen', 'Atopic dermatitis with secondary superficial pyoderma',
    'Chlorhexidine bath, oral antibiotic and omega-3 supplementation', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Recheck skin'),
 (2, 2, 2, NULL, 'lab_test', DATE_SUB(CURDATE(), INTERVAL 60 DAY), 'Routine blood work', NULL, 12.30, 38.7, 100,
    'CBC within normal limits', 'Healthy', NULL, NULL, NULL);

-- Vaccinations ("All vaccines current" for Luna)
INSERT INTO vaccinations (pet_id, vet_id, vaccine_name, dose_number, date_given, next_due_date) VALUES
 (1, 2, 'FVRCP',       3, DATE_SUB(CURDATE(), INTERVAL 5 MONTH), DATE_ADD(CURDATE(), INTERVAL 7 MONTH)),
 (1, 2, 'Anti-Rabies', 5, DATE_SUB(CURDATE(), INTERVAL 4 MONTH), DATE_ADD(CURDATE(), INTERVAL 8 MONTH)),
 (2, 2, 'DHPP',        4, DATE_SUB(CURDATE(), INTERVAL 11 MONTH), DATE_ADD(CURDATE(), INTERVAL 1 MONTH));

-- Medications (3 active for Luna)
INSERT INTO medications (id, pet_id, medical_record_id, prescribed_by, name, dosage, form, route, instructions, purpose, start_date, end_date, total_doses, status) VALUES
 (1, 1, 1, 2, 'Omega-3 Supplement', '500mg',      'capsule', 'oral',    'with morning meal',      'Skin and coat health', DATE_SUB(CURDATE(), INTERVAL 20 DAY), NULL, NULL, 'active'),
 (2, 1, 1, 2, 'Cephalexin',         '75mg',       'capsule', 'oral',    'every 12 hours with food', 'Skin infection',    DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 11 DAY), 28, 'active'),
 (3, 1, 1, 2, 'Chlorhexidine Wipes','1 wipe',     'topical', 'skin',    'wipe affected area',     'Skin infection',       DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 7, 'active');

INSERT INTO medication_schedules (id, medication_id, dose_time) VALUES
 (1, 1, '08:00:00'),
 (2, 2, '08:00:00'),
 (3, 2, '20:00:00'),
 (4, 3, '19:00:00');

-- Dose history for the last 3 days + today's pending doses
INSERT INTO medication_logs (medication_id, schedule_id, scheduled_for, status, taken_at, logged_by) VALUES
 (2, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00:00'), 'taken',  TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:10:00'), 1),
 (2, 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '20:00:00'), 'taken',  TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '20:05:00'), 1),
 (2, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00'), 'taken',  TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:20:00'), 1),
 (2, 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '20:00:00'), 'missed', NULL, NULL),
 (2, 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00'), 'taken',  TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:05:00'), 1),
 (2, 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '20:00:00'), 'taken',  TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '20:30:00'), 1),
 (1, 1, TIMESTAMP(CURDATE(), '08:00:00'), 'pending', NULL, NULL),
 (2, 2, TIMESTAMP(CURDATE(), '08:00:00'), 'pending', NULL, NULL),
 (2, 3, TIMESTAMP(CURDATE(), '20:00:00'), 'pending', NULL, NULL),
 (3, 4, TIMESTAMP(CURDATE(), '19:00:00'), 'pending', NULL, NULL);

-- Journal: last 4 days logged, today NOT yet logged ("Daily health journal not yet logged")
INSERT INTO journal_entries (pet_id, logged_by, entry_date, appetite_score, activity_score, mood, sleep_hours, sleep_quality, water_intake, bowel_movement, vomited, symptoms, behavior_notes) VALUES
 (1, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 3, 3, 'calm',      14.0, 4, 'normal', 'normal', 0, 'Some scratching on neck', NULL),
 (1, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 3, 3, 'calm',      13.5, 4, 'normal', 'normal', 0, 'Less scratching',         'Started antibiotic'),
 (1, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 2, 2, 'lethargic', 16.0, 3, 'less',   'normal', 1, 'Vomited once after dinner', 'Hid under the bed'),
 (1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 2, 3, 'calm',      15.0, 3, 'normal', 'normal', 0, 'Ate half her breakfast',  NULL);

-- Chatbot example conversation
INSERT INTO chat_conversations (id, user_id, pet_id, medical_record_id, title) VALUES
 (1, 1, 1, 1, 'What does atopy mean?');
INSERT INTO chat_messages (conversation_id, sender, content, ai_model) VALUES
 (1, 'user',      'What does atopy mean?', NULL),
 (1, 'assistant', 'Atopy (atopic dermatitis) is a skin allergy to things in the environment, like dust mites or pollen. It makes the skin itchy and red. It can be managed, but usually not cured, so your vet may suggest long-term care such as special baths or supplements.', 'rules');

-- Notifications for today's alerts
INSERT INTO notifications (user_id, pet_id, type, title, message, related_table, related_id) VALUES
 (1, 1, 'medication_due',   'Omega-3 Supplement — 8:00 AM dose due', '500mg · with morning meal', 'medications', 1),
 (1, 1, 'journal_reminder', 'Daily health journal not yet logged',   'Log Luna''s appetite, mood, and activity', 'pets', 1),
 (1, 1, 'missed_dose',      'Missed dose: Cephalexin 8:00 PM',        'A dose was missed 2 days ago.', 'medications', 2);
