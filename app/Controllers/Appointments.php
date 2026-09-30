<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\PetModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Clinic;

class Appointments extends BaseController
{
    public const DURATIONS = [15, 30, 45, 60, 90, 120];

    private AppointmentModel $appointments;
    private Clinic $clinic;

    public function __construct()
    {
        $this->appointments = new AppointmentModel();
        $this->clinic       = config(Clinic::class);
    }

    public function index()
    {
        $status = (string) $this->request->getGet('status');
        $date   = (string) $this->request->getGet('date');
        $scope  = (string) $this->request->getGet('scope');

        $query = $this->appointments->detailed();

        if ($this->isOwner()) {
            $query->where('appointments.owner_id', $this->userId());
        } elseif ($scope === 'mine' && $this->hasRole('vet')) {
            $query->where('appointments.vet_id', $this->userId());
        }
        if (in_array($status, AppointmentModel::STATUSES, true)) {
            $query->where('appointments.status', $status);
        }
        if ($date !== '') {
            $query->where('appointments.appointment_date', $date);
        }

        return view('appointments/index', [
            'title'        => 'Appointments',
            'appointments' => $query->orderBy('appointment_date', 'DESC')->orderBy('appointment_time')->findAll(),
            'filters'      => compact('status', 'date', 'scope'),
        ]);
    }

    public function show(int $id)
    {
        $appt = $this->findOrFail($id);

        return view('appointments/show', [
            'title' => 'Appointment #' . $id,
            'appt'  => $appt,
            'vets'  => (new UserModel())->vets(),
        ]);
    }

    public function create()
    {
        $pets = (new PetModel())->withOwner($this->isOwner() ? $this->userId() : null);

        return view('appointments/form', [
            'title'  => 'Book Appointment',
            'pets'   => $pets,
            'vets'   => (new UserModel())->vets(),
            'clinic' => $this->clinic,
            'petId'  => $this->request->getGet('pet_id'),
        ]);
    }

    public function store()
    {
        $pet = (new PetModel())->find((int) $this->request->getPost('pet_id'));

        if (! $pet || ($this->isOwner() && (int) $pet['owner_id'] !== $this->userId())) {
            return redirect()->back()->withInput()->with('error', 'Please choose one of your pets.');
        }

        $vetId = (int) $this->request->getPost('vet_id') ?: null;
        $data  = [
            'pet_id'           => $pet['id'],
            'owner_id'         => $pet['owner_id'],
            'vet_id'           => $vetId,
            'appointment_date' => (string) $this->request->getPost('appointment_date'),
            'appointment_time' => substr((string) $this->request->getPost('appointment_time'), 0, 5),
            'duration_minutes' => (int) ($this->request->getPost('duration_minutes') ?: $this->clinic->slotMinutes),
            'reason'           => trim((string) $this->request->getPost('reason')),
            'notes'            => trim((string) $this->request->getPost('notes')) ?: null,
            // Online bookings by owners wait for clinic confirmation.
            'status'           => $this->isOwner() ? 'pending' : 'confirmed',
        ];

        if ($error = $this->scheduleError($data)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        if (! $this->appointments->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->appointments->errors());
        }

        return redirect()->to('/appointments/' . $this->appointments->getInsertID())
            ->with('success', $this->isOwner() ? 'Appointment requested. The clinic will confirm it shortly.' : 'Appointment booked.');
    }

    /**
     * Staff update: change status, assign a vet or reschedule.
     */
    public function update(int $id)
    {
        $appt = $this->findOrFail($id);

        $data = [
            'status'           => (string) $this->request->getPost('status'),
            'vet_id'           => (int) $this->request->getPost('vet_id') ?: null,
            'appointment_date' => (string) ($this->request->getPost('appointment_date') ?: $appt['appointment_date']),
            'appointment_time' => substr((string) ($this->request->getPost('appointment_time') ?: $appt['appointment_time']), 0, 5),
            'duration_minutes' => (int) ($this->request->getPost('duration_minutes') ?: $appt['duration_minutes']),
            'notes'            => trim((string) $this->request->getPost('notes')) ?: null,
        ];

        if (! in_array($data['status'], AppointmentModel::STATUSES, true)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }

        $rescheduled = $data['appointment_date'] !== $appt['appointment_date']
            || $data['appointment_time'] !== substr($appt['appointment_time'], 0, 5)
            || $data['vet_id'] !== ($appt['vet_id'] === null ? null : (int) $appt['vet_id'])
            || $data['duration_minutes'] !== (int) $appt['duration_minutes'];

        if ($data['status'] !== 'cancelled' && $rescheduled && ($error = $this->scheduleError($data, $id))) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $this->appointments->skipValidation(true)->update($id, $data);

        return redirect()->to('/appointments/' . $id)->with('success', 'Appointment updated.');
    }

    public function cancel(int $id)
    {
        $appt = $this->findOrFail($id);

        if (in_array($appt['status'], ['completed', 'cancelled'], true)) {
            return redirect()->back()->with('error', 'This appointment can no longer be cancelled.');
        }

        $this->appointments->skipValidation(true)->update($id, ['status' => 'cancelled']);

        return redirect()->to('/appointments/' . $id)->with('success', 'Appointment cancelled.');
    }

    /**
     * JSON list of free start times for a vet on a date: GET /appointments/slots?date=&vet_id=&duration=
     */
    public function slots()
    {
        $date     = (string) $this->request->getGet('date');
        $vetId    = (int) $this->request->getGet('vet_id');
        $duration = (int) ($this->request->getGet('duration') ?: $this->clinic->slotMinutes);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid date']);
        }

        $free = [];
        $end  = strtotime($date . ' ' . $this->clinic->closeTime);

        for ($t = strtotime($date . ' ' . $this->clinic->openTime); $t + $duration * 60 <= $end; $t += $this->clinic->slotMinutes * 60) {
            $slot = ['appointment_date' => $date, 'appointment_time' => date('H:i', $t), 'duration_minutes' => $duration, 'vet_id' => $vetId ?: null];
            if ($this->scheduleError($slot) === null) {
                $free[] = date('H:i', $t);
            }
        }

        return $this->response->setJSON(['date' => $date, 'slots' => $free]);
    }

    /**
     * Returns a human-readable reason the slot cannot be booked, or null when it is fine.
     */
    private function scheduleError(array $data, ?int $ignoreId = null): ?string
    {
        $date = $data['appointment_date'] ?? '';
        $time = $data['appointment_time'] ?? '';

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! preg_match('/^\d{2}:\d{2}$/', $time)) {
            return 'Please pick a valid date and time.';
        }

        if (! in_array((int) $data['duration_minutes'], self::DURATIONS, true)) {
            return 'Please choose a valid duration.';
        }

        $start = strtotime("{$date} {$time}");
        $end   = $start + $data['duration_minutes'] * 60;

        if ($start < time()) {
            return 'Appointments cannot be booked in the past.';
        }
        if (in_array((int) date('N', $start), $this->clinic->closedDays, true)) {
            return 'The clinic is closed on ' . date('l', $start) . 's.';
        }
        if ($start < strtotime("{$date} {$this->clinic->openTime}") || $end > strtotime("{$date} {$this->clinic->closeTime}")) {
            return "Please choose a time between {$this->clinic->openTime} and {$this->clinic->closeTime}.";
        }
        if (! empty($data['vet_id']) && $this->appointments->hasConflict((int) $data['vet_id'], $date, $time, $data['duration_minutes'], $ignoreId)) {
            return 'The selected veterinarian already has an appointment at that time.';
        }

        return null;
    }

    private function findOrFail(int $id): array
    {
        $appt = $this->appointments->detailed()->where('appointments.id', $id)->first();

        if (! $appt || ($this->isOwner() && (int) $appt['owner_id'] !== $this->userId())) {
            throw PageNotFoundException::forPageNotFound('Appointment not found.');
        }

        return $appt;
    }
}
