<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Sign Up page for Pet Owners.
 * (Veterinarian accounts are created by the Clinic Staff in the Admin panel.)
 */
class Register extends BaseController
{
    // Shows the sign-up form (GET /register)
    public function index()
    {
        // Already logged in? No need to sign up.
        if (session('user')) {
            return redirect()->to('/home');
        }

        return view('auth/register', ['title' => 'Create Account']);
    }

    // Saves the new Pet Owner account (POST /register)
    public function store()
    {
        // 1. Check the form input
        $rules = [
            'full_name'        => ['label' => 'Full name', 'rules' => 'required|min_length[2]|max_length[120]'],
            'email'            => [
                'label'  => 'Email address',
                'rules'  => 'required|valid_email|is_unique[users.email]',
                'errors' => ['is_unique' => 'This email address is already registered.'],
            ],
            'phone'            => ['label' => 'Mobile number', 'rules' => 'permit_empty|max_length[30]'],
            'password'         => ['label' => 'Password', 'rules' => 'required|min_length[' . UserModel::MIN_PASSWORD_LENGTH . ']'],
            'password_confirm' => ['label' => 'Confirm password', 'rules' => 'required|matches[password]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // 2. Save the account. The role is ALWAYS 'owner' here, so nobody can sign up as admin.
        (new UserModel())->insert([
            'role'          => 'owner',
            'full_name'     => trim($this->request->getPost('full_name')),
            'email'         => trim($this->request->getPost('email')),
            'phone'         => trim((string) $this->request->getPost('phone')) ?: null,
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'is_active'     => 1,
        ]);

        // 3. Send them to the login page with a success message
        return redirect()->to('/login')->with('success', 'Account created! You can now sign in.');
    }
}
