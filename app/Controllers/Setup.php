<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * First-time setup: creates the very first Clinic Staff (admin) account.
 * Once an admin exists, this page is locked.
 */
class Setup extends BaseController
{
    // Shows the form (GET /setup)
    public function index()
    {
        $users = new UserModel();

        // Already set up? Show the "done" message instead of the form.
        if ($users->hasAdmin()) {
            return view('auth/setup_done', ['title' => 'Setup Complete']);
        }

        return view('auth/setup', ['title' => 'First-time Setup']);
    }

    // Saves the form (POST /setup)
    public function store()
    {
        $users = new UserModel();

        // Security: nobody can create a second admin through this page.
        if ($users->hasAdmin()) {
            return redirect()->to('/setup');
        }

        // 1. Check the form input
        $rules = [
            'full_name'        => ['label' => 'Full name', 'rules' => 'required|full_name|max_length[120]'],
            'email'            => ['label' => 'Email address', 'rules' => 'required|valid_email'],
            'password'         => ['label' => 'Password', 'rules' => 'required|min_length[' . UserModel::MIN_PASSWORD_LENGTH . ']'],
            'password_confirm' => ['label' => 'Confirm password', 'rules' => 'required|matches[password]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // 2. Save the admin account (the password is hashed, never stored as plain text)
        $users->insert([
            'role'          => 'admin',
            'full_name'     => preg_replace('/\s+/u', ' ', trim($this->request->getPost('full_name'))), // "Juan   Dela Cruz" → "Juan Dela Cruz"
            'email'         => trim($this->request->getPost('email')),
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'is_active'     => 1,
        ]);

        // 3. Go back to /setup, which now shows the "done" message
        return redirect()->to('/setup')->with('success', 'Admin account created!');
    }
}
