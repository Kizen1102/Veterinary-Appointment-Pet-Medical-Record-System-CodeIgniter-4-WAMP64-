# PawRecord — Veterinary Clinic Appointment & Pet Medical Record System

Built with **CodeIgniter 4 · Composer · PHP 8.2+ · MySQL/MariaDB · XAMPP**

**User roles:** Pet Owner · Veterinarian · Clinic Staff (Admin)

| Problem | Feature |
|---|---|
| Owners don't understand vet terms | **AI Medical Information Chatbot**: explains veterinary terms in plain language |
| Health records are scattered across visits | **Digital Pet Health Timeline**: consultations, treatments, vaccinations and follow-ups for each pet, in date order |
| Owners forget medications | **Medication Adherence Tracker**: dose schedules, daily dose confirmation, missed-dose alerts, treatment completion |
| Owners can't recall how a condition changed | **Pet Symptom & Behavior Journal (AI)**: daily log of appetite, activity, mood, sleep and symptoms, summarised by AI for the vet |

---

## Build progress

| Step | What | Status |
|---|---|---|
| 1 | MySQL database schema (`database/pawrecord_schema.sql`) | ✅ Done |
| 2 | CodeIgniter connected to the database: migrations, models, `.env`, System Check page | ✅ Done |
| 3 | First-time Admin setup, Login, Sign Up (Pet Owner), Forgot password, roles | ⏳ Next |
| 4 | Layout and theme (PawRecord header, bottom navigation) | |
| 5 | Pet Owner dashboard | |
| 6 | Pets and appointment booking | |
| 7 | Digital Pet Health Timeline | |
| 8 | Medication Adherence Tracker | |
| 9 | Symptom & Behavior Journal with AI summary | |
| 10 | AI Medical Chatbot | |
| 11 | Veterinarian panel | |
| 12 | Admin panel | |
| 13 | Testing and final documentation | |

---

## Setup on XAMPP (Windows)

> **Internet café PC?** Use the portable XAMPP on a USB drive, and keep the project folder on the USB too. Café PCs often erase files on restart.

### 1. XAMPP and PHP extensions
1. Get XAMPP with **PHP 8.2+** from https://www.apachefriends.org. On a café PC, use the portable `.zip` from SourceForge and run `setup_xampp.bat` once.
2. Open the **XAMPP Control Panel** and click **Start** for **Apache** and **MySQL**.
3. Apache **Config** → **PHP (php.ini)**. Remove the `;` in front of each of these lines, then save:
   ```ini
   extension=intl
   extension=zip
   extension=openssl   ; only once. If it appears twice, keep a ; on one of them
   ```
   Restart Apache.

### 2. Get the project and its libraries
1. Download this branch as a ZIP (**Code → Download ZIP**), extract it into `C:\xampp\htdocs\` and rename the folder to **`pawrecord`**.
2. Download `composer.phar` into that folder. In the XAMPP **Shell**:
   ```bat
   cd htdocs\pawrecord
   php -r "copy('https://getcomposer.org/download/latest-stable/composer.phar', 'composer.phar');"
   php composer.phar install
   ```
   (If you already ran `composer install` in an older copy, you can copy its `vendor` folder instead.)

### 3. Settings file
In the project folder, copy `.env.example` to **`.env`**:
```bat
copy .env.example .env
```
The defaults are already set for XAMPP: database `pawrecord_db`, user `root`, empty password. If your folder isn't named `pawrecord`, change this line in `.env`:
```ini
app.baseURL = 'http://localhost/pawrecord/public/'
```

### 4. Create the database (empty, no demo accounts)
1. Open http://localhost/phpmyadmin and create a database named **`pawrecord_db`** with collation **`utf8mb4_unicode_ci`**.
   If it already exists from Step 1, click it → **Operations** → **Drop the database (DROP)**, then create it again empty.
2. In the XAMPP **Shell**:
   ```bat
   cd htdocs\pawrecord
   php spark migrate
   ```
   This creates all 13 tables and 2 views. They start empty, because accounts are created inside the system.

### 5. Check the installation
Open **http://localhost/pawrecord/public/**. The **System Check** page should show:
- ✔ PHP 8.2+ and the extensions
- ✔ Connected to `pawrecord_db`
- ✔ All 15 tables and views, and "Migrations run: 13 / 13"

---

## Project structure

```
app/
  Config/Database.php      XAMPP database defaults (root / pawrecord_db)
  Config/Routes.php        URLs
  Controllers/             SystemCheck (Step 2); more controllers in the next steps
  Database/Migrations/     13 migrations = the PawRecord schema
  Database/PawMigration.php  shared helpers for the migrations
  Models/                  one model per table + 2 read-only models for the views
  Libraries/               AI helpers (used from Step 9)
  Views/                   pages
database/
  pawrecord_schema.sql     same schema as a single SQL file (for phpMyAdmin import)
  README.md                table diagram and design notes
public/                    web root (index.php, assets)
.env.example               settings template
```

### Models (Step 2)
| Model | Table / view | Useful methods |
|---|---|---|
| `UserModel` | users | `findByEmail()`, `hasAdmin()`, `vets()`, `owners()` |
| `PetModel` | pets | `forOwner()`, `withPeople()`, `ageLabel()` |
| `AppointmentModel` | appointments | `detailed()`, `nextForPet()`, `hasConflict()` (blocks double-booking a vet) |
| `MedicalRecordModel` | medical_records | `forPet()` |
| `VaccinationModel` | vaccinations | `forPet()`, `allCurrent()`, `dueSoon()` |
| `MedicationModel` | medications | `activeForPet()` |
| `MedicationScheduleModel` | medication_schedules | `forMedication()` |
| `MedicationLogModel` | medication_logs | `todayForPet()`, `markTaken()`, `markOverdueAsMissed()` |
| `JournalEntryModel` | journal_entries | `hasEntryToday()`, `recent()` |
| `JournalAiSummaryModel` | journal_ai_summaries | `latestForPet()` |
| `ChatConversationModel` / `ChatMessageModel` | chat_* | `forUser()`, `forConversation()` |
| `NotificationModel` | notifications | `unreadFor()`, `markRead()` |
| `PetHealthTimelineModel` | view pet_health_timeline | `forPet()` |
| `MedicationAdherenceModel` | view medication_adherence | `forPet()` |

## Useful commands (XAMPP Shell, inside the project folder)
| Command | What it does |
|---|---|
| `php spark migrate` | Create or update the tables |
| `php spark migrate:status` | Show which migrations have run |
| `php spark migrate:rollback` | Undo the last batch (removes the tables and their data) |
| `php spark serve` | Alternative web server at http://localhost:8080 (set `app.baseURL` to match) |

## Troubleshooting
| Problem | Fix |
|---|---|
| `The zip extension and unzip/7z commands are both missing` | Remove the `;` before `extension=zip` in `C:\xampp\php\php.ini` and open a new Shell |
| `Module "openssl" is already loaded` | `extension=openssl` is enabled twice in php.ini; put a `;` before one |
| System Check: **Cannot connect** | Start MySQL in XAMPP; check `database.default.*` in `.env` |
| `php spark migrate` says a table already exists | The tables were imported from the SQL file. Drop and recreate `pawrecord_db` empty (setup step 4), then migrate |
| 404 Not Found | Check that `app.baseURL` in `.env` matches your folder name and ends with `/public/` |
| MySQL won't start | Port 3306 is used by another program; close it or change the port in XAMPP **Config → my.ini** and in `.env` |

## Running the tests (optional)
```bat
vendor\bin\phpunit
```
The tests use a temporary in-memory database. They never touch `pawrecord_db`.
