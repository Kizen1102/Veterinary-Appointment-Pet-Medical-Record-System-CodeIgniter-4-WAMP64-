<?php
/**
 * PawRecord checker: shows which files and methods from Steps 11A–13 and Redesigns D2–D3 are missing.
 * Put this file in the project folder (next to "spark") and run:  php check_pawrecord.php
 */
$checks = [
    'Step 11A — Vet appointments' => [
        'app/Controllers/VetAppointments.php'   => ['function updateStatus', 'function notifyOwner'],
        'app/Models/AppointmentModel.php'       => ['function visibleToVet', 'function forVetTab'],
        'app/Views/vet/_appointment.php'        => ['Add record'],
        'app/Views/vet/appointments.php'        => [],
        'app/Views/dashboard/vet.php'           => ['stat-tile'],
        'app/Helpers/app_helper.php'            => ["'no_show'"],
    ],
    'Step 11B — Vet patients' => [
        'app/Controllers/VetPatients.php'       => ['function storeRecord', 'function storeVaccine', 'function storePrescription', 'function reviewSummary'],
        'app/Models/PetModel.php'               => ['function findWithPeople', 'function patients('],
        'app/Models/JournalAiSummaryModel.php'  => ['function forVets'],
        'app/Views/vet/patients.php'            => [],
        'app/Views/vet/patient.php'             => [],
        'app/Views/vet/record_form.php'         => [],
        'app/Views/vet/vaccine_form.php'        => [],
        'app/Views/vet/prescription_form.php'   => [],
        'app/Views/vet/journals.php'            => [],
    ],
    'Step 12 — Admin panel' => [
        'app/Controllers/Admin.php'             => ['function users', 'function storeUser', 'function assignVet', 'function cancelAppointment'],
        'app/Controllers/Dashboard.php'         => ["view('dashboard/admin'"],
        'app/Models/UserModel.php'              => ['function forAdmin', 'function countByRole'],
        'app/Models/AppointmentModel.php'       => ['function forAdminTab'],
        'app/Views/dashboard/admin.php'         => [],
        'app/Views/admin/_appointment.php'      => [],
        'app/Views/admin/users.php'             => [],
        'app/Views/admin/user_form.php'         => [],
        'app/Views/admin/pets.php'              => [],
        'app/Views/admin/appointments.php'      => [],
    ],
    'Routes (Steps 11A–12)' => [
        'app/Config/Routes.php'                 => ['VetAppointments::index', 'VetPatients::index', 'VetPatients::journals', 'Admin::users', 'Admin::appointments'],
    ],
    'Step 13 — Tests and docs' => [
        'app/Models/UserModel.php'              => ['MIN_PASSWORD_LENGTH = 8'],
        'tests/feature/RolePanelsTest.php'      => [],
        'docs/USER_GUIDE.md'                    => [],
        'docs/TEST_CHECKLIST.md'                => [],
        'app/Database/Migrations/2026-10-04-000014_FixTimelineCollation.php' => [],
    ],
    'Redesign D2 — Sign-in pages' => [
        'app/Views/layouts/auth.php'            => ['auth-shell', 'auth-pets'],
        'public/assets/css/auth.css'            => ['.auth-side', 'has-photo'],
        'public/assets/img/auth-pets.png'       => [],
    ],
    'Redesign D3 — Sidebar' => [
        'app/Helpers/app_helper.php'            => ['function nav_menu'],
        'app/Views/partials/sidebar.php'        => [],
        'app/Views/partials/bottom_nav.php'     => ['nav_menu()'],
        'app/Views/layouts/app.php'             => ["partials/sidebar"],
        'public/assets/css/pawrecord.css'       => ['--paw-soft', 'Redesign D3', '.side-link', '.user-row'],
        'app/Views/dashboard/owner.php'         => ['col-lg-7'],
        'app/Views/dashboard/vet.php'           => ['col-lg-7'],
        'app/Views/dashboard/admin.php'         => ['col-lg-6'],
    ],
];

// Methods pasted twice cause "Cannot redeclare" errors
$noDuplicates = [
    'app/Models/PetModel.php', 'app/Models/UserModel.php', 'app/Models/AppointmentModel.php',
    'app/Models/JournalAiSummaryModel.php', 'app/Helpers/app_helper.php',
];

$missing = 0;
foreach ($checks as $section => $files) {
    echo "\n== {$section}\n";
    foreach ($files as $file => $needles) {
        if (! is_file($file)) {
            echo "  [MISSING FILE]  {$file}\n";
            $missing++;
            continue;
        }
        $code = file_get_contents($file);
        $lack = array_filter($needles, static fn ($n) => strpos($code, $n) === false);
        if ($lack === []) {
            echo "  ok              {$file}\n";
        } else {
            foreach ($lack as $n) {
                echo "  [MISSING CODE]  {$file}  ->  {$n}\n";
                $missing++;
            }
        }
    }
}

echo "\n== Methods pasted twice\n";
$dupes = 0;
foreach ($noDuplicates as $file) {
    if (! is_file($file)) {
        continue;
    }
    preg_match_all('/function\s+(\w+)\s*\(/', file_get_contents($file), $m);
    foreach (array_count_values($m[1]) as $name => $count) {
        if ($count > 1) {
            echo "  [TWICE]  {$file}  ->  {$name}() appears {$count} times\n";
            $dupes++;
        }
    }
}
if ($dupes === 0) {
    echo "  ok              none\n";
}

echo "\n" . ($missing + $dupes === 0 ? "All good! Nothing is missing.\n" : ($missing + $dupes) . " thing(s) to fix (see above).\n");
