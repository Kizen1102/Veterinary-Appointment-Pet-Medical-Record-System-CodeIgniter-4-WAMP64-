<?php

namespace App\Controllers;

use App\Libraries\MedicationTracker;
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

        // Create today's doses and flag late ones as missed (Step 8)
        (new MedicationTracker())->refresh($user['id']);

        // Today's alerts: doses still to give today, doses missed today + journal reminder
        $dosesToday  = (new MedicationLogModel())->todayForPet($petId);
        $dosesDue    = array_filter($dosesToday, static fn ($dose) => $dose['status'] === 'pending');
        $dosesMissed = array_filter($dosesToday, static fn ($dose) => $dose['status'] === 'missed');

        return view('dashboard/owner', [
            'title'           => 'Home',
            'user'            => $user,
            'pets'            => $pets,
            'pet'             => $pet,
            'nextAppointment' => (new AppointmentModel())->nextForPet($petId),
            'vaccineStatus'   => $this->vaccineStatus($petId),
            'dosesDue'        => $dosesDue,
            'dosesMissed'     => $dosesMissed,
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

    // Veterinarian dashboard (GET /vet): today's schedule and new booking requests
    public function vet()
    {
        $user         = session('user');
        $appointments = new AppointmentModel();

        $today    = $appointments->forVetTab($user['id'], 'today');
        $requests = $appointments->forVetTab($user['id'], 'pending');

        return view('dashboard/vet', [
            'title'    => 'Home',
            'user'     => $user,
            'today'    => $today,
            'requests' => array_slice($requests, 0, 5),
            'counts'   => [
                'today'    => count($today),
                'requests' => count($requests),
                'upcoming' => count($appointments->forVetTab($user['id'], 'upcoming')),
                'patients' => (new PetModel())->countAllResults(),
            ],
        ]);
    }

    // Clinic Staff dashboard (GET /admin): clinic numbers and requests that still need a vet
    public function admin()
    {
        $appointments = new AppointmentModel();
        $users        = new UserModel();

        $today      = $appointments->forAdminTab('today');
        $unassigned = $appointments->forAdminTab('unassigned');

        return view('dashboard/admin', [
            'title'      => 'Home',
            'user'       => session('user'),
            'today'      => $today,
            'unassigned' => array_slice($unassigned, 0, 5),
            'vets'       => $users->vets(),
            'counts'     => $users->countByRole() + [
                'pets'       => (new PetModel())->countAllResults(),
                'today'      => count($today),
                'unassigned' => count($unassigned),
            ],
        ]);
    }
}
