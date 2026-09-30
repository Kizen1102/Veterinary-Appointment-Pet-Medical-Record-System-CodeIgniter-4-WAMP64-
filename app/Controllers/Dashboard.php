<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\MedicalRecordModel;
use App\Models\PetModel;
use App\Models\UserModel;
use App\Models\VaccinationModel;

class Dashboard extends BaseController
{
    /** SQL CASE used to list the most urgent AI-triaged cases first. */
    private const URGENCY_ORDER = "CASE appointments.triage_level WHEN 'emergency' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END";

    public function index()
    {
        return $this->isOwner() ? $this->ownerDashboard() : $this->clinicDashboard();
    }

    private function ownerDashboard()
    {
        $ownerId = $this->userId();

        return view('dashboard/owner', [
            'title'        => 'Dashboard',
            'pets'         => (new PetModel())->where('owner_id', $ownerId)->orderBy('name')->findAll(),
            'upcoming'     => (new AppointmentModel())->detailed()
                ->where('appointments.owner_id', $ownerId)
                ->where('appointment_date >=', date('Y-m-d'))
                ->whereIn('appointments.status', ['pending', 'confirmed'])
                ->orderBy('appointment_date')->orderBy('appointment_time')
                ->findAll(5),
            'vaccinations' => (new VaccinationModel())->dueSoon(30, $ownerId),
        ]);
    }

    private function clinicDashboard()
    {
        $today        = date('Y-m-d');
        $appointments = new AppointmentModel();

        // Separate model instance: the counts below would otherwise reset this query's builder.
        $todayQuery = (new AppointmentModel())->detailed()
            ->where('appointment_date', $today)
            ->where('appointments.status !=', 'cancelled');
        if ($this->hasRole('vet')) {
            $todayQuery->groupStart()->where('appointments.vet_id', $this->userId())->orWhere('appointments.vet_id IS NULL')->groupEnd();
        }

        return view('dashboard/clinic', [
            'title'   => 'Dashboard',
            'stats'   => [
                'today'   => $appointments->where('appointment_date', $today)->where('status !=', 'cancelled')->countAllResults(),
                'pending' => $appointments->where('status', 'pending')->countAllResults(),
                'pets'    => (new PetModel())->countAllResults(),
                'owners'  => (new UserModel())->where('role', 'owner')->countAllResults(),
            ],
            'today'   => $todayQuery->orderBy(self::URGENCY_ORDER, '', false)->orderBy('appointment_time')->findAll(),
            'pending' => $appointments->detailed()
                ->where('appointments.status', 'pending')
                ->orderBy(self::URGENCY_ORDER, '', false)->orderBy('appointment_date')->orderBy('appointment_time')
                ->findAll(10),
            'vaccinations' => (new VaccinationModel())->dueSoon(14),
            'followUps'    => (new MedicalRecordModel())
                ->select('medical_records.*, pets.name AS pet_name, users.name AS owner_name')
                ->join('pets', 'pets.id = medical_records.pet_id')
                ->join('users', 'users.id = pets.owner_id')
                ->where('follow_up_date >=', $today)
                ->where('follow_up_date <=', date('Y-m-d', strtotime('+7 days')))
                ->orderBy('follow_up_date')
                ->findAll(),
        ]);
    }
}
