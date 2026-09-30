<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\JournalEntryModel;
use App\Models\MedicationLogModel;
use App\Models\MedicationModel;
use App\Models\PetModel;
use App\Models\UserModel;
use App\Models\VaccinationModel;

/**
 * One dashboard per role.
 */
class Dashboard extends BaseController
{
    // Pet Owner dashboard (GET /owner, optionally ?pet=ID to switch pets)
    public function owner()
    {
        $user = session('user');
        $pets = (new PetModel())->withPeople($user['id']);

        // No pets yet → show the "Add your first pet" screen
        if ($pets === []) {
            return view('dashboard/owner_empty', ['title' => 'Home', 'user' => $user]);
        }

        // Which pet is selected? (?pet=ID, but only one of THIS owner's pets)
        $pet = $pets[0];
        foreach ($pets as $candidate) {
            if ((int) $candidate['id'] === (int) $this->request->getGet('pet')) {
                $pet = $candidate;
            }
        }
        $petId = (int) $pet['id'];

        // Today's alerts: doses still to give today + journal reminder
        $dosesDue = array_filter(
            (new MedicationLogModel())->todayForPet($petId),
            static fn ($dose) => $dose['status'] === 'pending'
        );

        return view('dashboard/owner', [
            'title'           => 'Home',
            'user'            => $user,
            'pets'            => $pets,
            'pet'             => $pet,
            'nextAppointment' => (new AppointmentModel())->nextForPet($petId),
            'vaccineStatus'   => $this->vaccineStatus($petId),
            'dosesDue'        => $dosesDue,
            'journalLogged'   => (new JournalEntryModel())->hasEntryToday($petId),
            'activeMeds'      => count((new MedicationModel())->activeForPet($petId)),
        ]);
    }

    // 'none' (no vaccines yet), 'current' (nothing overdue) or 'due' (at least one overdue)
    private function vaccineStatus(int $petId): string
    {
        $vaccinations = new VaccinationModel();

        if ($vaccinations->forPet($petId) === []) {
            return 'none';
        }

        return $vaccinations->allCurrent($petId) ? 'current' : 'due';
    }

    public function vet()
    {
        return $this->show('Veterinarian Dashboard');
    }

    public function admin()
    {
        return $this->show('Clinic Staff Dashboard');
    }

    // Placeholder page for the vet and admin dashboards (built in later steps)
    private function show(string $pageName)
    {
        $user = session('user');

        return view('home', [
            'title'     => $pageName,
            'pageName'  => $pageName,
            'user'      => $user,
            'roleLabel' => UserModel::ROLE_LABELS[$user['role']],
        ]);
    }
}
