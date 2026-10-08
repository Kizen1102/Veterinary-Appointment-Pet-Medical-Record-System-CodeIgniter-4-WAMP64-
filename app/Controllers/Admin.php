<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\NotificationModel;
use App\Models\PetModel;
use App\Models\UserModel;
use App\Validation\NameRules;

/**
 * Clinic Staff (admin) panel: staff accounts, all pets, and all appointments.
 */
class Admin extends BaseController
{
    /** Filter tabs on the Appointments page. */
    public const APPOINTMENT_TABS = [
        'today'      => 'Today',
        'unassigned' => 'No vet yet',
        'upcoming'   => 'Upcoming',
        'past'       => 'Past',
    ];

    // ---------- Users ----------

    // GET /admin/users?role=vet&q=name
    public function users()
    {
        $role   = (string) $this->request->getGet('role');
        $search = trim((string) $this->request->getGet('q'));
        if (! in_array($role, UserModel::ROLES, true)) {
            $role = '';
        }

        $users = new UserModel();

        return view('admin/users', [
            'title'  => 'Users',
            'role'   => $role,
            'search' => $search,
            'counts' => $users->countByRole(),
            'users'  => $users->forAdmin($role, $search),
        ]);
    }

    // "Add staff" form (GET /admin/users/new?role=vet)
    public function newUser()
    {
        return view('admin/user_form', [
            'title' => 'Add Staff',
            'role'  => $this->request->getGet('role') === 'admin' ? 'admin' : 'vet',
        ]);
    }

    // Creates a vet or clinic staff account (POST /admin/users)
    public function storeUser()
    {
        $rules = [
            'role'             => ['label' => 'Role', 'rules' => 'required|in_list[vet,admin]'],
            'full_name'        => ['label' => 'Full name', 'rules' => 'required|full_name|max_length[120]', 'errors' => ['required' => NameRules::MESSAGE]],
            'email'            => ['label' => 'Email address', 'rules' => 'required|valid_email|max_length[150]|is_unique[users.email]'],
            'phone'            => ['label' => 'Phone', 'rules' => 'permit_empty|max_length[30]'],
            'license_number'   => ['label' => 'License number', 'rules' => 'permit_empty|max_length[50]|is_unique[users.license_number]'],
            'specialization'   => ['label' => 'Specialization', 'rules' => 'permit_empty|max_length[100]'],
            'password'         => ['label' => 'Temporary password', 'rules' => 'required|min_length[' . UserModel::MIN_PASSWORD_LENGTH . ']'],
            'password_confirm' => ['label' => 'Confirm password', 'rules' => 'required|matches[password]'],
        ];
        $messages = [
            'email'          => ['is_unique' => 'This email address is already registered.'],
            'license_number' => ['is_unique' => 'Another vet already uses this license number.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $role = $this->request->getPost('role');

        (new UserModel())->insert([
            'role'           => $role,
            'full_name'      => preg_replace('/\s+/u', ' ', trim($this->request->getPost('full_name'))),
            'email'          => trim($this->request->getPost('email')),
            'phone'          => trim((string) $this->request->getPost('phone')) ?: null,
            // License and specialization are only for vets
            'license_number' => $role === 'vet' ? (trim((string) $this->request->getPost('license_number')) ?: null) : null,
            'specialization' => $role === 'vet' ? (trim((string) $this->request->getPost('specialization')) ?: null) : null,
            'password_hash'  => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'is_active'      => 1,
        ]);

        return redirect()->to('/admin/users?role=' . $role)
            ->with('success', UserModel::ROLE_LABELS[$role] . ' account created. Give them the temporary password; they can change it with "Forgot password".');
    }

    // Turns an account off or back on (POST /admin/users/<id>/toggle)
    public function toggleUser(int $id)
    {
        if ($id === (int) session('user')['id']) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        $users = new UserModel();
        $user  = $users->find($id);
        if (! $user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $active = $user['is_active'] ? 0 : 1;
        $users->update($id, ['is_active' => $active]);

        // A deactivated user is signed out on their next click (AuthFilter checks is_active)
        return redirect()->back()->with('success', $user['full_name'] . ($active ? ' can sign in again.' : ' was deactivated and can no longer sign in.'));
    }

    // ---------- Pets ----------

    // GET /admin/pets?q=name
    public function pets()
    {
        $search = trim((string) $this->request->getGet('q'));

        return view('admin/pets', [
            'title'  => 'Pets',
            'search' => $search,
            'pets'   => (new PetModel())->patients($search),
            'vets'   => (new UserModel())->vets(),
        ]);
    }

    // Sets the pet's primary vet (POST /admin/pets/<id>/vet)
    public function assignPetVet(int $id)
    {
        $pets = new PetModel();
        $pet  = $pets->find($id);
        if (! $pet) {
            return redirect()->back()->with('error', 'Pet not found.');
        }

        $vetId = (int) $this->request->getPost('vet_id');
        if ($vetId !== 0 && ! $this->findActiveVet($vetId)) {
            return redirect()->back()->with('error', 'Please choose an active vet.');
        }

        $pets->update($id, ['primary_vet_id' => $vetId ?: null]);

        return redirect()->back()->with('success', $pet['name'] . '\'s primary vet was updated.');
    }

    // ---------- Appointments ----------

    // GET /admin/appointments?tab=today
    public function appointments()
    {
        $tab = (string) $this->request->getGet('tab');
        if (! array_key_exists($tab, self::APPOINTMENT_TABS)) {
            $tab = 'today';
        }

        return view('admin/appointments', [
            'title'        => 'Appointments',
            'tab'          => $tab,
            'appointments' => (new AppointmentModel())->forAdminTab($tab),
            'vets'         => (new UserModel())->vets(),
        ]);
    }

    // Gives an upcoming appointment to a vet (POST /admin/appointments/<id>/assign)
    public function assignVet(int $id)
    {
        $appointments = new AppointmentModel();
        $appointment  = $appointments->detailed()->where('appointments.id', $id)->first();

        if (! $appointment || ! in_array($appointment['status'], ['pending', 'confirmed'], true)
            || $appointment['scheduled_at'] <= date('Y-m-d H:i:s')) {
            return redirect()->back()->with('error', 'Only upcoming appointments can be assigned.');
        }

        $vet = $this->findActiveVet((int) $this->request->getPost('vet_id'));
        if (! $vet) {
            return redirect()->back()->with('error', 'Please choose an active vet.');
        }

        $when = date('M j, g:i A', strtotime($appointment['scheduled_at']));
        if ($appointments->hasConflict((int) $vet['id'], $appointment['scheduled_at'], (int) $appointment['duration_minutes'], $id)) {
            return redirect()->back()->with('error', $vet['full_name'] . ' already has an appointment at ' . $when . '.');
        }

        $appointments->update($id, ['vet_id' => $vet['id']]);

        // Tell the vet (the request still waits for the vet to confirm it)
        (new NotificationModel())->insert([
            'user_id'       => $vet['id'],
            'pet_id'        => $appointment['pet_id'],
            'type'          => 'appointment_update',
            'title'         => 'Appointment assigned to you',
            'message'       => $appointment['pet_name'] . ' (' . AppointmentModel::TYPE_LABELS[$appointment['appointment_type']] . ') on ' . $when . '.',
            'link_url'      => 'vet/appointments?tab=' . ($appointment['status'] === 'pending' ? 'pending' : 'upcoming'),
            'related_table' => 'appointments',
            'related_id'    => $id,
        ]);

        return redirect()->back()->with('success', 'Appointment assigned to ' . $vet['full_name'] . '.');
    }

    // Cancels an upcoming appointment and tells the owner why (POST /admin/appointments/<id>/cancel)
    public function cancelAppointment(int $id)
    {
        $appointments = new AppointmentModel();
        $appointment  = $appointments->detailed()->where('appointments.id', $id)->first();

        if (! $appointment || ! in_array($appointment['status'], ['pending', 'confirmed'], true)
            || $appointment['scheduled_at'] <= date('Y-m-d H:i:s')) {
            return redirect()->back()->with('error', 'Only upcoming appointments can be cancelled.');
        }

        $reason = trim((string) $this->request->getPost('reason')) ?: 'Cancelled by the clinic';
        $appointments->update($id, ['status' => 'cancelled', 'cancellation_reason' => mb_substr($reason, 0, 255)]);

        (new NotificationModel())->insert([
            'user_id'       => $appointment['owner_id'],
            'pet_id'        => $appointment['pet_id'],
            'type'          => 'appointment_update',
            'title'         => 'Appointment cancelled',
            'message'       => 'Your visit for ' . $appointment['pet_name'] . ' on ' . date('M j, g:i A', strtotime($appointment['scheduled_at'])) . ' was cancelled: ' . $reason . '. Please book another time.',
            'link_url'      => 'appointments',
            'related_table' => 'appointments',
            'related_id'    => $id,
        ]);

        return redirect()->back()->with('success', 'Appointment cancelled. The owner was notified.');
    }

    /** An active vet account, or null. */
    private function findActiveVet(int $id): ?array
    {
        return (new UserModel())->where('role', 'vet')->where('is_active', 1)->find($id);
    }
}
