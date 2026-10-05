<?php

namespace App\Controllers;

use App\Libraries\VetAssistant;
use App\Models\AppointmentModel;
use App\Models\PetModel;
use App\Models\UserModel;

/**
 * Pet Owners book, view and cancel clinic appointments.
 */
class Appointments extends BaseController
{
    // List of the owner's appointments (GET /appointments)
    public function index()
    {
        $now          = date('Y-m-d H:i:s');
        $upcoming     = [];
        $past         = [];
        $appointments = (new AppointmentModel())->forOwner(session('user')['id']);

        // Upcoming = still active and in the future. Everything else is history.
        foreach ($appointments as $appointment) {
            $isActive = in_array($appointment['status'], ['pending', 'confirmed'], true);

            if ($isActive && $appointment['scheduled_at'] >= $now) {
                $upcoming[] = $appointment;
            } else {
                $past[] = $appointment;
            }
        }

        return view('appointments/index', [
            'title'    => 'Appointments',
            'upcoming' => array_reverse($upcoming), // soonest first
            'past'     => $past,                    // newest first
        ]);
    }

    // "Book Appointment" form (GET /appointments/new, optionally ?pet=ID)
    public function create()
    {
        $pets = (new PetModel())->forOwner(session('user')['id']);

        if ($pets === []) {
            return redirect()->to('/pets/new')->with('error', 'Add your pet first, then book an appointment.');
        }

        return view('appointments/form', [
            'title'       => 'Book Appointment',
            'pets'        => $pets,
            'vets'        => (new UserModel())->vets(),
            'selectedPet' => (int) $this->request->getGet('pet'),
        ]);
    }

    // Saves the booking (POST /appointments)
    public function store()
    {
        $ownerId = session('user')['id'];

        // 1. Check the form input
        $rules = [
            'pet_id'           => ['label' => 'Pet', 'rules' => 'required|is_natural_no_zero'],
            'vet_id'           => ['label' => 'Veterinarian', 'rules' => 'permit_empty|is_natural_no_zero'],
            'appointment_type' => [
                'label'  => 'Type of visit',
                'rules'  => 'required|in_list[' . implode(',', AppointmentModel::OWNER_TYPES) . ']',
                'errors' => ['in_list' => 'Please choose a type of visit from the list.'],
            ],
            'date'             => ['label' => 'Date', 'rules' => 'required|valid_date[Y-m-d]'],
            'time'             => [
                'label'  => 'Time',
                'rules'  => 'required|in_list[' . implode(',', array_keys(AppointmentModel::timeSlots())) . ']',
                'errors' => ['in_list' => 'Please choose a time between 8:00 AM and 4:30 PM.'],
            ],
            'reason'           => ['label' => 'Reason', 'rules' => 'permit_empty|max_length[500]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // 2. The pet must belong to the logged-in owner
        $pet = (new PetModel())->where('owner_id', $ownerId)->find($this->request->getPost('pet_id'));
        if (! $pet) {
            return redirect()->back()->withInput()->with('error', 'Please choose one of your pets.');
        }

        // 3. The vet (optional) must be an active veterinarian
        $vetId = (int) $this->request->getPost('vet_id') ?: null;
        if ($vetId !== null) {
            $vet = (new UserModel())->where('role', 'vet')->where('is_active', 1)->find($vetId);
            if (! $vet) {
                return redirect()->back()->withInput()->with('error', 'Please choose a veterinarian from the list.');
            }
        }

        // 4. Date and time rules
        $scheduledAt = $this->request->getPost('date') . ' ' . $this->request->getPost('time') . ':00';

        if ($scheduledAt <= date('Y-m-d H:i:s')) {
            return redirect()->back()->withInput()->with('error', 'Please choose a date and time in the future.');
        }

        if (date('w', strtotime($scheduledAt)) === '0') {
            return redirect()->back()->withInput()->with('error', 'The clinic is closed on Sundays. Please choose another day.');
        }

        // 5. No double booking: the vet and the pet must both be free at that time
        $appointments = new AppointmentModel();

        if ($vetId !== null && $appointments->hasConflict($vetId, $scheduledAt, AppointmentModel::SLOT_MINUTES)) {
            return redirect()->back()->withInput()->with('error', 'That veterinarian is already booked at this time. Please choose another time.');
        }

        $petIsBusy = $appointments->where('pet_id', $pet['id'])
            ->where('scheduled_at', $scheduledAt)
            ->whereIn('status', ['pending', 'confirmed'])
            ->countAllResults() > 0;

        if ($petIsBusy) {
            return redirect()->back()->withInput()->with('error', $pet['name'] . ' already has an appointment at this time.');
        }

        // 6. Step 15: AI urgency check of the reason (offline, keyword rules are used)
        $reason = trim((string) $this->request->getPost('reason')) ?: null;
        $triage = $reason !== null ? (new VetAssistant())->triage($reason, $pet) : null;

        // 7. Save. The clinic confirms it later, so it starts as "pending".
        $type = $this->request->getPost('appointment_type');

        $appointments->insert([
            'pet_id'           => $pet['id'],
            'owner_id'         => $ownerId,
            'vet_id'           => $vetId,
            'appointment_type' => $type,
            'title'            => AppointmentModel::TYPE_LABELS[$type],
            'scheduled_at'     => $scheduledAt,
            'duration_minutes' => AppointmentModel::SLOT_MINUTES,
            'reason'           => $reason,
            'triage_level'     => $triage['level'] ?? null,
            'triage_summary'   => $triage !== null ? mb_substr($triage['summary'], 0, 255) : null,
            'status'           => 'pending',
            'created_by'       => $ownerId,
        ]);

        return redirect()->to('/appointments')
            ->with('success', 'Appointment requested for ' . $pet['name'] . '! The clinic will confirm it.')
            ->with('triage', $triage); // shown once on the Appointments page
    }

    // Cancels one of the owner's upcoming appointments (POST /appointments/<id>/cancel)
    public function cancel(int $id)
    {
        $appointments = new AppointmentModel();
        $appointment  = $appointments->where('owner_id', session('user')['id'])->find($id); // only your own

        $canCancel = $appointment
            && in_array($appointment['status'], ['pending', 'confirmed'], true)
            && $appointment['scheduled_at'] > date('Y-m-d H:i:s');

        if (! $canCancel) {
            return redirect()->to('/appointments')->with('error', 'This appointment can no longer be cancelled.');
        }

        $appointments->update($id, [
            'status'              => 'cancelled',
            'cancellation_reason' => 'Cancelled by the owner',
        ]);

        return redirect()->to('/appointments')->with('success', 'Appointment cancelled.');
    }
}
