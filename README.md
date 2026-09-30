# Veterinary-Appointment-Pet-Medical-Record-System-CodeIgniter-4-WAMP64-
Veterinary Clinic Appointment &amp; Pet Medical Record System with AI-powered features — Built with CodeIgniter 4

## Features

| Area | What it does |
|---|---|
| **Accounts & roles** | Login, pet-owner self-registration, roles: `admin`, `vet`, `staff`, `owner`. Admins create staff/vet accounts and can disable any account. |
| **Pets & owners** | Pet profiles (species, breed, sex, age, weight, allergies). Staff search owners and register walk-in clients. Owners only ever see their own pets. |
| **Appointments** | Online booking with a live list of free time slots, clinic hours / closed days, no double-booking of a vet. Owner requests start as *pending*; staff confirm, reschedule, assign a vet, complete or cancel. |
| **Medical records** | Vets record vitals, symptoms, diagnosis, treatment, prescription and follow-up date. Printable record view. Writing a record from an appointment marks it completed and updates the pet's weight. |
| **Vaccinations** | Vaccination log per pet with next-due dates; overdue shots are highlighted. |
| **Dashboards** | Clinic: today's schedule and pending requests ordered by urgency, vaccinations due, follow-ups this week. Owner: upcoming visits, vaccinations due, pets. |
| **AI features** (Claude) | • **Triage** of every appointment request (low / medium / high / emergency)<br>• **AI Symptom Checker** for owners<br>• **Plain-language summary** of a medical record for the owner<br>Without an API key the app uses built-in keyword triage and template summaries, so everything still works offline. |

Security: CSRF protection on all forms, password hashing, role-based route filters, output escaping, and accounts that are disabled get signed out on their next request.

---

## Step-by-step setup on XAMPP (Windows)

> **Internet café / school PC?** Use the **portable** XAMPP (a `.zip`, no installation or admin rights needed) and keep it on a **USB flash drive**. Many café PCs erase everything on restart, so only files on your USB drive are safe.

### Step 1 — Get XAMPP
Download XAMPP with **PHP 8.2 or newer** from https://www.apachefriends.org/download.html
- **Own PC:** run the installer and use the default folder `C:\xampp`.
- **Café PC / no admin rights:** open *"More Downloads"* (SourceForge) → pick the **portable `.zip`**, extract it to your USB drive (for example `E:\xampp`), then run **`setup_xampp.bat`** once inside that folder.

Open **`xampp-control.exe`** and click **Start** for **Apache** and **MySQL**. Both turn green.

### Step 2 — Enable the required PHP extensions
In the XAMPP Control Panel, click **Config** on the Apache row → **PHP (php.ini)**. Find each line below and remove the `;` in front of it, then save:
```ini
extension=intl
extension=mbstring
extension=mysqli
extension=curl
extension=openssl
```
Click **Stop** and then **Start** on Apache. (`mod_rewrite` is already on in XAMPP.)

### Step 3 — Get the project and its libraries
**With Git and Composer installed:**
```bat
cd C:\xampp\htdocs
git clone https://github.com/Kizen1102/Veterinary-Appointment-Pet-Medical-Record-System-CodeIgniter-4-WAMP64-.git vetclinic
cd vetclinic
composer install
```
**Without installing anything (café PC):**
1. On the GitHub page click **Code → Download ZIP** and extract it to `C:\xampp\htdocs\vetclinic` (or `E:\xampp\htdocs\vetclinic` on your USB drive).
2. Download **`composer.phar`** from https://getcomposer.org/download/ into that folder.
3. In the XAMPP Control Panel click **Shell**, then run:
   ```bat
   cd htdocs\vetclinic
   php composer.phar install
   ```

### Step 4 — Create the database
1. Open **http://localhost/phpmyadmin** (user `root`, empty password by default in XAMPP).
2. Create a new database named **`vetclinic_db`** with collation `utf8mb4_general_ci`.

### Step 5 — Configure the environment
```bat
copy .env.example .env
```
Open `.env` and check:
```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost/vetclinic/public/'

database.default.hostname = localhost
database.default.database = vetclinic_db
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

### Step 6 — Create the tables and demo data
```bat
php spark migrate
php spark db:seed DatabaseSeeder
```

### Step 7 — Open the app
Go to **http://localhost/vetclinic/public/** and log in with a demo account (password for all: **`password123`**):

| Role | Email |
|---|---|
| Admin | `admin@vetclinic.test` |
| Veterinarian | `vet@vetclinic.test` / `vet2@vetclinic.test` |
| Front desk staff | `staff@vetclinic.test` |
| Pet owner | `owner@vetclinic.test` |

> Tip: instead of XAMPP's Apache you can also run `php spark serve` and open http://localhost:8080 (set `app.baseURL = 'http://localhost:8080/'`).

### Step 8 — (Optional) Turn on the AI features
1. Create an API key at https://console.anthropic.com.
2. Add it to `.env`:
   ```ini
   ANTHROPIC_API_KEY = sk-ant-...
   ANTHROPIC_MODEL = claude-opus-5-5
   ```
3. Reload the page. The Symptom Checker note *"AI is not configured…"* disappears and triage results are marked *Generated by AI*.

If the key is missing or the API can't be reached, the app automatically falls back to the rule-based triage — nothing breaks. Errors are written to `writable/logs/`.

### Step 9 — Before going live
- Set `CI_ENVIRONMENT = production` in `.env`.
- Change or disable the demo accounts (Users page, admin only).
- Point your web server's document root to the `public/` folder.

---

## Using the system

1. **Owner** registers → adds pets → books an appointment (reason is AI-triaged) → sees status on the dashboard.
2. **Staff** see pending requests on the dashboard, most urgent first → open one → assign a vet and confirm.
3. **Vet** opens the appointment → *Write medical record* → saves (appointment is completed) → *Generate summary* for the owner.
4. **Owner** opens the pet → reads the medical record and the plain-language summary, checks vaccinations due.

Clinic hours, slot size and closed days are in `app/Config/Clinic.php`.

---

## Project structure (built step by step)

| Step | What was added | Main files |
|---|---|---|
| 1 | CodeIgniter 4 scaffold | `app/`, `public/`, `spark` |
| 2 | Database schema + demo data | `app/Database/Migrations/*`, `app/Database/Seeds/DatabaseSeeder.php`, `.env.example` |
| 3 | Models with validation | `app/Models/*` |
| 4 | Authentication, role filters, layout | `app/Controllers/Auth.php`, `app/Filters/*`, `app/Views/layouts/main.php` |
| 5 | Pets, owners, user accounts | `app/Controllers/{Pets,Owners,Users}.php` |
| 6 | Appointment booking | `app/Controllers/Appointments.php`, `app/Config/Clinic.php` |
| 7 | Medical records & vaccinations | `app/Controllers/{MedicalRecords,Vaccinations}.php` |
| 8 | AI triage, symptom checker, summaries | `app/Libraries/{VetAssistant,RuleBasedTriage}.php`, `app/Config/AI.php` |
| 9 | Dashboards | `app/Controllers/Dashboard.php`, `app/Views/dashboard/*` |
| 10 | Tests + this guide | `tests/*`, `README.md` |

### Database tables
`users` · `pets` · `appointments` (incl. `triage_level`, `triage_notes`) · `medical_records` (incl. `ai_summary`) · `vaccinations`

---

## Running the tests
The tests use an in-memory SQLite database (PHP `sqlite3` extension required) and never call the real AI API.
```bat
vendor\bin\phpunit
```

## Troubleshooting
| Problem | Fix |
|---|---|
| 404 on every page except the home page | Make sure `app.baseURL` ends with `/public/` and `AllowOverride All` is set for `htdocs` in `httpd.conf` (XAMPP default). |
| `Unable to connect to the database` | Check the database name / user in `.env`, and that MySQL is running (green in the XAMPP Control Panel). |
| `Class "Locale" not found` / intl error | Remove the `;` before `extension=intl` in `C:\xampp\php\php.ini` (Step 2) and restart Apache. |
| MySQL won't start in XAMPP | Port 3306 is in use (another MySQL/WAMP). Close it, or change the port under **Config → my.ini** and in `.env`. |
| "The action you requested is not allowed" | CSRF token expired; reload the form and submit again. |
| AI results always say *keyword screening* | `ANTHROPIC_API_KEY` is empty or invalid — check `writable/logs/` for `VetAssistant` errors. |

> AI output supports clinic staff and owners but is **not** a diagnosis. Always follow the veterinarian's advice.
