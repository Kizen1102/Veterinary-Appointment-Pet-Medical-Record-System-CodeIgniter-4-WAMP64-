<?php

namespace App\Controllers;

use App\Models\PetModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Pet-owner (client) directory for clinic staff.
 */
class Owners extends BaseController
{
    public function index()
    {
        $q     = trim((string) $this->request->getGet('q'));
        $users = (new UserModel())->select('users.*, (SELECT COUNT(*) FROM pets WHERE pets.owner_id = users.id) AS pet_count')
            ->where('role', 'owner');

        if ($q !== '') {
            $users->groupStart()->like('name', $q)->orLike('email', $q)->orLike('phone', $q)->groupEnd();
        }

        return view('owners/index', [
            'title'  => 'Pet Owners',
            'owners' => $users->orderBy('name')->findAll(),
            'q'      => $q,
        ]);
    }

    public function show(int $id)
    {
        $owner = (new UserModel())->where('role', 'owner')->find($id);

        if (! $owner) {
            throw PageNotFoundException::forPageNotFound('Owner not found.');
        }

        return view('owners/show', [
            'title' => $owner['name'],
            'owner' => $owner,
            'pets'  => (new PetModel())->where('owner_id', $id)->orderBy('name')->findAll(),
        ]);
    }

    public function create()
    {
        return view('owners/form', ['title' => 'Register Walk-in Owner']);
    }

    /**
     * Front-desk registration of a walk-in client. A temporary password is generated.
     */
    public function store()
    {
        $model    = new UserModel();
        $password = bin2hex(random_bytes(4));

        $data = [
            'name'          => trim((string) $this->request->getPost('name')),
            'email'         => strtolower(trim((string) $this->request->getPost('email'))),
            'phone'         => $this->request->getPost('phone'),
            'address'       => $this->request->getPost('address'),
            'role'          => 'owner',
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active'     => 1,
        ];

        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('/owners/' . $model->getInsertID())
            ->with('success', "Owner registered. Temporary password: {$password} (ask them to change it after first login).");
    }
}
