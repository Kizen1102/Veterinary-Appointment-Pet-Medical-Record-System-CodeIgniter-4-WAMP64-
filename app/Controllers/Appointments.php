<?php

namespace App\Controllers;

use App\Libraries\VetAssistant;
use App\Models\AppointmentModel;
use App\Models\NotificationModel;
use App\Models\PetModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Pet Owners book, view, change (while still pending) and cancel clinic appointments.
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
        $checked = $this->checkBooking();
        if (! is_array($checked)) {
            return $checked; // back to the form with the error
        }

        ['data' => $data, 'pet' => $pet, 'triage' => $triage] = $checked;
        $ownerId = session('user')['id'];

        // Save. The clinic confirms it later, so it starts as "pending".
        $appointmentId = (new AppointmentModel())->insert($data + [
            'owner_id'   => $ownerId,
            'status'     => 'pending',
            'created_by' => $ownerId,
        ]);

        // Tell the clinic staff (and the chosen vet) right away; urgent requests say so in the title
        $this->notifyClinic(
            ['id' => $appointmentId, 'pet_id' => $pet['id'], 'vet_id' => $data['vet_id']],
            $this->urgencyPrefix($triage, 'New appointment request: ') . $pet['name'],
            session('user')['full_name'] . ' booked ' . $this->describe($data),
        );

        return redirect()->to('/appointments')
            ->with('success', 'Appointment requested for ' . $pet['name'] . '! The clinic will confirm it.')
            ->with('triage', $triage); // shown once on the Appointments page
    }

    // "Edit appointment" form (GET /appointments/<id>/edit)
    public function edit(int $id)
    {
        $appointment = $this->findEditable($id);
        if (! $appointment) {
            return redirect()->to('/appointments')->with('error', self::NOT_EDITABLE);
        }

        return view('appointments/form', [
            'title'       => 'Edit Appointment',
            'pets'        => (new PetModel())->forOwner(session('user')['id']),
            'vets'        => (new UserModel())->vets(),
            'selectedPet' => (int) $appointment['pet_id'],
            'appointment' => $appointment,
        ]);
    }

    // Saves the changes (POST /appointments/<id>)
    public function update(int $id)
    {
        $appointment = $this->findEditable($id);
        if (! $appointment) {
            return redirect()->to('/appointments')->with('error', self::NOT_EDITABLE);
        }

        $checked = $this->checkBooking($appointment['id']);
        if (! is_array($checked)) {
            return $checked;
        }

        ['data' => $data, 'pet' => $pet, 'triage' => $triage] = $checked;

        (new AppointmentModel())->update($appointment['id'], $data);

        $this->notifyClinic(
            ['id' => $appointment['id'], 'pet_id' => $pet['id'], 'vet_id' => $data['vet_id']],
            $this->urgencyPrefix($triage, 'Request changed by the owner: ') . $pet['name'],
            session('user')['full_name'] . ' changed the request to ' . $this->describe($data),
            true,
            $appointment['vet_id'] !== null ? (int) $appointment['vet_id'] : null, // the old vet hears about it too
        );

        return redirect()->to('/appointments')
            ->with('success', 'Appointment updated. The clinic will confirm it.')
            ->with('triage', $triage);
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

        $pet = (new PetModel())->find($appointment['pet_id']);
        $this->notifyClinic(
            $appointment,
            'Cancelled by the owner: ' . ($pet['name'] ?? 'pet'),
            session('user')['full_name'] . ' cancelled the visit on ' . date('M j, g:i A', strtotime($appointment['scheduled_at'])) . '.',
            false,
        );

        return redirect()->to('/appointments')->with('success', 'Appointment cancelled.');
    }

    private const NOT_EDITABLE = 'This appointment can no longer be changed. Once the clinic confirms it, cancel it and book again.';

    /** The owner's own appointment, only while it is still pending and in the future. */
    private function findEditable(int $id): ?array
    {
        $appointment = (new AppointmentModel())->where('owner_id', session('user')['id'])->find($id); // only your own

        $editable = $appointment
            && $appointment['status'] === 'pending'
            && $appointment['scheduled_at'] > date('Y-m-d H:i:s');

        return $editable ? $appointment : null;
    }

    /**
     * Checks the booking form (new or edited). Returns the values to save, the pet and the
     * urgency check, or a redirect back to the form with the error.
     * $ignoreId = the appointment being edited, so it does not "conflict" with itself.
     *
     * @return array{data: array, pet: array, triage: ?array}|RedirectResponse
     */
    private function checkBooking(?int $ignoreId = null)
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

        if ($vetId !== null && $appointments->hasConflict($vetId, $scheduledAt, AppointmentModel::SLOT_MINUTES, $ignoreId)) {
            return redirect()->back()->withInput()->with('error', 'That veterinarian is already booked at this time. Please choose another time.');
        }

        $appointments->where('pet_id', $pet['id'])
            ->where('scheduled_at', $scheduledAt)
            ->whereIn('status', ['pending', 'confirmed']);
        if ($ignoreId !== null) {
            $appointments->where('id !=', $ignoreId);
        }

        if ($appointments->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', $pet['name'] . ' already has an appointment at this time.');
        }

        // 6. Step 15: AI urgency check of the reason (offline, keyword rules are used)
        $reason = trim((string) $this->request->getPost('reason')) ?: null;
        $triage = $reason !== null ? (new VetAssistant())->triage($reason, $pet) : null;
        $type   = $this->request->getPost('appointment_type');

        return [
            'pet'    => $pet,
            'triage' => $triage,
            'data'   => [
                'pet_id'           => $pet['id'],
                'vet_id'           => $vetId,
                'appointment_type' => $type,
                'title'            => AppointmentModel::TYPE_LABELS[$type],
                'scheduled_at'     => $scheduledAt,
                'duration_minutes' => AppointmentModel::SLOT_MINUTES,
                'reason'           => $reason,
                'triage_level'     => $triage['level'] ?? null,
                'triage_summary'   => $triage !== null ? mb_substr($triage['summary'], 0, 255) : null,
            ],
        ];
    }

    /** "🚨 Emergency request: " / "⚠️ Urgent request: " for urgent requests, otherwise $normal. */
    private function urgencyPrefix(?array $triage, string $normal): string
    {
        $titles = ['emergency' => '🚨 Emergency request: ', 'high' => '⚠️ Urgent request: '];

        return $titles[$triage['level'] ?? ''] ?? $normal;
    }

    /** "Consultation on Oct 9, 9:30 AM. Reason: ..." */
    private function describe(array $data): string
    {
        return $data['title'] . ' on ' . date('M j, g:i A', strtotime($data['scheduled_at']))
            . ($data['reason'] !== null ? '. Reason: ' . $data['reason'] : '.');
    }

    /**
     * Puts a message in the bell of every active Clinic Staff account and of the appointment's vet.
     * The link opens the tab where they can act on it (a new request) or see it (a cancelled one).
     * $previousVetId = the vet before an edit, who is told as well when the owner chose another vet.
     */
    private function notifyClinic(array $appointment, string $title, string $message, bool $isNew = true, ?int $previousVetId = null): void
    {
        $vetId    = $appointment['vet_id'] !== null ? (int) $appointment['vet_id'] : null;
        $staffTab = $isNew ? ($vetId === null ? 'unassigned' : 'upcoming') : 'past';
        $links    = array_fill_keys((new UserModel())->staffIds(), 'admin/appointments?tab=' . $staffTab);

        foreach (array_unique(array_filter([$previousVetId, $vetId])) as $id) {
            $links[$id] = 'vet/appointments?tab=' . ($isNew ? 'pending' : 'past');
        }

        $rows = [];
        foreach ($links as $userId => $link) {
            $rows[] = [
                'user_id'       => $userId,
                'pet_id'        => $appointment['pet_id'],
                'type'          => 'appointment_update',
                'title'         => mb_substr($title, 0, 150),
                'message'       => mb_substr($message, 0, 500),
                'link_url'      => $link,
                'related_table' => 'appointments',
                'related_id'    => $appointment['id'],
            ];
        }

        if ($rows !== []) {
            (new NotificationModel())->insertBatch($rows);
        }
    }
}
