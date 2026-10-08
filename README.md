# PawRecord — Veterinary Clinic Appointment & Pet Medical Record System

Built with **CodeIgniter 4.7 · PHP 8.2+ · MySQL 8 / MariaDB · Bootstrap 5**
Runs on **XAMPP, WAMP64 or Laragon** (Windows).

**User roles:** Pet Owner · Veterinarian · Clinic Staff (Admin)

| Problem | Feature |
|---|---|
| Owners don't understand vet terms | **AI Medical Information Chatbot**: explains veterinary terms in plain language |
| Health records are scattered across visits | **Digital Pet Health Timeline**: consultations, treatments, vaccinations and follow-ups for each pet, in date order |
| Owners forget medications | **Medication Adherence Tracker**: dose schedules, daily dose confirmation, missed-dose alerts, treatment completion |
| Owners can't recall how a condition changed | **Pet Symptom & Behavior Journal (AI)**: daily log of appetite, activity, mood, sleep and symptoms, summarised for the vet |
| Owners can't tell if a problem is urgent | **AI Urgency Check (triage)**: the reason typed when booking is checked; owners get advice (emergency → go now), vets see urgent requests first with a badge |
| Visit records are full of medical words | **Explain this visit (AI)**: each record on the Health Timeline can be rewritten in plain language for the owner (the vet's private notes are never included) |

📘 **[User Guide](docs/USER_GUIDE.md)** — how each role uses the system
✅ **[Test Checklist](docs/TEST_CHECKLIST.md)** — manual tests for the demo / defense
🗄️ **[Database notes](database/README.md)** — tables, views, backups

---

## What each role can do

| Pet Owner | Veterinarian | Clinic Staff (Admin) |
|---|---|---|
| Sign up, add / edit / archive pets with photos | Dashboard: today's schedule, new requests | Dashboard: clinic numbers, requests with no vet, today's visits |
| Book, edit (while pending), view and cancel appointments (the clinic staff get a 🔔) | Confirm (and claim), decline, complete, no-show | Add vet and staff accounts, activate / deactivate users |
| Health Timeline of every visit, vaccine and medicine | Patients list with search, full patient page | Set each pet's primary vet |
| Medication tracker: dose reminders, missed-dose alerts, adherence % | Write medical records (vitals, diagnosis, follow-up, private notes) | Assign / change the vet of any upcoming appointment (no double-booking) |
| Daily Symptom & Behavior Journal + AI summary for the vet | Record vaccines (next due date), prescribe medicines with dose times | Cancel appointments with a reason for the owner; 🔔 for every new or cancelled request |
| AI chatbot for vet terms | Read and review owners' journal summaries | |
| 🔔 Notifications for every change | 🔔 Notifications for assigned appointments | |

---

## Setup (Windows)

### 1. Server and PHP
Any of these works. Start **Apache** (or use `php spark serve`) and **MySQL**.

| | XAMPP | WAMP64 | Laragon |
|---|---|---|---|
| Project folder | `C:\xampp\htdocs\pawrecord` | `C:\wamp64\www\pawrecord` | `C:\laragon\www\pawrecord` |
| Terminal | XAMPP **Shell** | CMD (add PHP to PATH) | Laragon **Terminal** (Cmder) |
| Database tool | phpMyAdmin | phpMyAdmin | HeidiSQL / phpMyAdmin |

PHP must be **8.2 or newer**, with the extensions **intl**, **mbstring**, **zip** and **openssl** turned on
(XAMPP: Apache → Config → `php.ini`, remove the `;` before `extension=intl` and `extension=zip`;
Laragon: right-click → PHP → Extensions).

### 2. Project and libraries
1. Download this branch (**Code → Download ZIP**) and extract it as the `pawrecord` folder shown above.
2. In the terminal, inside the folder:
   ```bat
   php -r "copy('https://getcomposer.org/download/latest-stable/composer.phar', 'composer.phar');"
   php composer.phar install
   ```
   (Laragon already has Composer: `composer install`.)

### 3. Settings file
Copy `.env.example` to **`.env`** (`copy .env.example .env`) and check:
```ini
app.baseURL = 'http://localhost/pawrecord/public/'   ; or 'http://localhost:8080/' with php spark serve
database.default.database = pawrecord_db
database.default.username = root
database.default.password =
```
`.env` is never uploaded to GitHub (it is in `.gitignore`). Keep secrets only there.

### 4. Database
1. Create an **empty** database named `pawrecord_db`, collation **`utf8mb4_unicode_ci`**.
2. In the terminal:
   ```bat
   php spark migrate
   ```
   This creates all tables and views (14 migrations). They start empty.

### 5. First run
1. Open http://localhost/pawrecord/public/ (or http://localhost:8080 after `php spark serve`).
2. The first visit to **/setup** creates the **Clinic Staff (admin)** account. This page locks itself after that.
3. Log in as admin → **+ Add vet** to create the veterinarians.
4. Pet owners create their own accounts with **Sign up**.

**Demo data (optional, for the defense):** `php spark db:seed DemoSeeder` adds 2 vets, a clinic staff account and
3 owners with 4 pets, visits, vaccines, medicines, journal and notifications (dates counted from today).
Every demo account uses the password `demo1234`: `dr.santos@pawrecord.test`, `dr.reyes@pawrecord.test`, `staff@pawrecord.test`,
`ana@pawrecord.test`, `carlo@pawrecord.test`, `bea@pawrecord.test`. It runs only once and does not touch your own data.
Do not run it on a real clinic database.

The development-only page **/system-check** shows the PHP version, extensions, database connection, tables and migrations.

### 6. AI features (optional)
Without an API key, the chatbot uses the built-in vet glossary and the journal summary uses the built-in rule checker, so everything still works offline.
To use Claude, put your key in **`.env`** only (never in `env`, never in chat or screenshots):
```ini
ANTHROPIC_API_KEY = sk-ant-...
```
If a key is ever shared by mistake, revoke it at console.anthropic.com and make a new one.

### 7. E-mail for "Forgot password" (optional)
Without e-mail settings, the reset link is shown on the screen (development mode only).
To really send it, use a Gmail account of the clinic:
1. Turn on **2-Step Verification** in the Google account.
2. Open **myaccount.google.com/apppasswords**, make an App Password named `PawRecord` and copy its 16 letters.
3. Add to **`.env`** (never `env`; the App Password is a secret like the API key):
   ```ini
   email.protocol   = smtp
   email.SMTPHost   = smtp.gmail.com
   email.SMTPPort   = 587
   email.SMTPCrypto = tls
   email.SMTPUser   = yourclinic@gmail.com
   email.SMTPPass   = abcdefghijklmnop
   email.fromEmail  = yourclinic@gmail.com
   email.fromName   = 'PawRecord Clinic'
   ```
   Values with spaces need quotes (`'PawRecord Clinic'`), or every page shows an error.
4. The link in the e-mail uses `app.baseURL`, so it opens only where that address works
   (`localhost` = the same PC; `http://192.168.x.x/...` = any device on the same Wi-Fi).

If sending fails, the reason is saved in `writable/logs/` and (in development) the link is shown on the screen.

---

## Useful commands (inside the project folder)
| Command | What it does |
|---|---|
| `php spark migrate` | Create or update the tables and views |
| `php spark migrate:status` | Show which migrations have run |
| `php spark serve` | Built-in web server at http://localhost:8080 (set `app.baseURL` to match) |
| `php spark db:seed DemoSeeder` | Add the demo accounts and sample data (once) |
| `php spark db:backup` | Save the whole database (tables, data and views) to `writable/backups/` |
| `vendor\bin\phpunit` | Run the automated tests |

**Moving the database to another computer:** use `php spark db:backup`, not phpMyAdmin's Export.
phpMyAdmin writes "stand-in" tables and `DEFINER`s for the views, which fail to import on another computer.

---

## Project structure
```
app/
  Config/Routes.php          every URL, grouped by role (filters "auth" and "role:...")
  Controllers/               Auth, Register, PasswordReset, Setup, Landing, Home, Dashboard,
                             Pets, Appointments, Timeline, Meds, Journal, Chat, Notifications   (owner)
                             VetAppointments, VetPatients                                      (vet)
                             Admin                                                             (admin)
  Filters/                   AuthFilter (signed in + active), RoleFilter (role:owner/vet/admin)
  Models/                    one model per table + 2 read-only models for the views
  Libraries/                 MedicationTracker, VetAssistant (Claude API), offline fallbacks:
                             RuleBasedTriage, RuleBasedJournalSummary, VetGlossary
  Commands/DbBackup.php      php spark db:backup
  Database/Migrations/       the PawRecord schema
  Views/                     pages; layouts/app.php (signed in), layouts/public.php (landing)
database/                    schema SQL and database notes
docs/                        user guide and test checklist
public/                      web root (index.php, assets/css, uploads)
tests/                       automated tests (unit, database, feature)
```

---

## Security in short
- Passwords are hashed (`password_hash`), at least **8 characters**. Reset tokens are stored only as hashes, expire after 1 hour and are e-mailed only to the account's address.
- Every form has a **CSRF token**; login is limited to 5 tries per minute.
- Every page checks the role on the server (`role:` filters), and every record is looked up together with its owner or vet, so changing an ID in the URL does not show someone else's data.
- All output is escaped (`esc()`), so typed HTML is shown as text.
- Deactivated accounts are signed out on their next click.
- Vets' **private notes** are never shown to owners.

---

## Troubleshooting
| Problem | Fix |
|---|---|
| `PHP version must be 8.2` | Use a PHP 8.2+ build (XAMPP 8.2, or switch the version in WAMP / Laragon) |
| `Class "Locale" not found` / intl error | Turn on `extension=intl` in php.ini and restart Apache |
| System Check: **Cannot connect** | Start MySQL; check `database.default.*` in `.env` |
| **404 Not Found** (Apache page) | Check that `app.baseURL` matches the folder and ends with `/public/`; that `public/.htaccess` exists; or use `php spark serve` |
| **403 "The action you requested is not allowed"** | The form's CSRF token expired (page left open too long, or cookies blocked). Reload the page and submit again |
| Page keeps redirecting (login ↔ home) | Delete the browser cookies for `localhost` and the files in `writable/session/` |
| `#1271 Illegal mix of collations for operation 'UNION'` | Run `php spark migrate` (the `FixTimelineCollation` migration recreates the timeline view) |
| `#1064` / `#1222` on importing a phpMyAdmin export | The tables were imported; run `database/fix_views.sql` in the SQL tab to make the 2 views. Next time back up with `php spark db:backup` |
| `#1062 Duplicate entry '0' for key 'PRIMARY'` / `Invalid primary key: '0'` | The ids lost AUTO_INCREMENT in an import: run `database/fix_keys.sql` in the SQL tab |
| `Cannot redeclare ...Model::something()` | A method was pasted twice in that model: delete the second copy |
| `.env values containing spaces must be surrounded by quotes` | Put quotes around the value in `.env`, e.g. `email.fromName = 'PawRecord Clinic'` |
| Reset e-mail never arrives | Check Spam; check the App Password (16 letters, no spaces) and `email.SMTPUser`; read `writable/logs/` |
| Chatbot answers end with "(Offline glossary answer...)" | No API key, or the key is invalid (see `writable/logs/`); the offline answers still work |
| `php is not recognized` | Use the XAMPP Shell / Laragon Terminal, or add PHP to the Windows PATH |
| MySQL won't start | Port 3306 is used by another program (another XAMPP/WAMP/Laragon); stop it |

---

## Running the tests
```bat
vendor\bin\phpunit
```
46 tests: the AI features and their offline fallbacks, the models (double-booking, doses, journal, timeline, cascades), and the role panels
(who may open each page, vet records and prescriptions, admin accounts and appointment assignment) the demo seeder and the password-reset e-mail.
They use a temporary in-memory SQLite database and never touch `pawrecord_db`.

## Credits
- Dog and cat picture on the sign-in pages (`public/assets/img/auth-pets.png`): from [Noto Color Emoji](https://github.com/googlefonts/noto-emoji) by Google, Apache License 2.0.
