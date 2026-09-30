<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Admin-only management of clinic accounts.
 */
class Users extends BaseController
{
    public function index()
    {
        return view('users/index', [
            'title' => 'User Accounts',
            'users' => (new UserModel())->orderBy('role')->orderBy('name')->findAll(),
        ]);
    }

    public function create()
    {
        return view('users/form', ['title' => 'New User']);
    }

    public function store()
    {
        $model = new UserModel();
        $rules = ['password' => 'required|min_length[8]'];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'          => trim((string) $this->request->getPost('name')),
            'email'         => strtolower(trim((string) $this->request->getPost('email'))),
            'phone'         => $this->request->getPost('phone'),
            'role'          => $this->request->getPost('role'),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'is_active'     => 1,
        ];

        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('/users')->with('success', 'User created.');
    }

    public function toggle(int $id)
    {
        if ($id === $this->userId()) {
            return redirect()->to('/users')->with('error', 'You cannot disable your own account.');
        }

        $model = new UserModel();
        $user  = $model->find($id);

        if ($user) {
            $model->skipValidation(true)->update($id, ['is_active' => $user['is_active'] ? 0 : 1]);
        }

        return redirect()->to('/users')->with('success', 'Account status updated.');
    }
}
