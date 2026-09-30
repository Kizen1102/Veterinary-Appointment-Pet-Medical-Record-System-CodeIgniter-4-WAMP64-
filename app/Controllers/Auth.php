<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if ($this->user()) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login', ['title' => 'Login']);
    }

    public function attemptLogin()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = (new UserModel())->findByEmail((string) $this->request->getPost('email'));

        if (! $user || ! password_verify((string) $this->request->getPost('password'), $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        if (! $user['is_active']) {
            return redirect()->back()->withInput()->with('error', 'Your account is disabled. Please contact the clinic.');
        }

        $this->session->regenerate();
        $this->session->set('user', [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ]);

        return redirect()->to('/dashboard')->with('success', 'Welcome back, ' . $user['name'] . '!');
    }

    public function register()
    {
        if ($this->user()) {
            return redirect()->to('/dashboard');
        }

        return view('auth/register', ['title' => 'Create Account']);
    }

    /**
     * Public sign-up creates pet-owner accounts only; staff accounts are made by an admin.
     */
    public function attemptRegister()
    {
        $rules = [
            'name'             => 'required|min_length[2]|max_length[100]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'phone'            => 'permit_empty|max_length[30]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new UserModel())->insert([
            'name'          => $this->request->getPost('name'),
            'email'         => strtolower(trim((string) $this->request->getPost('email'))),
            'phone'         => $this->request->getPost('phone'),
            'address'       => $this->request->getPost('address'),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role'          => 'owner',
            'is_active'     => 1,
        ]);

        return redirect()->to('/login')->with('success', 'Account created. You can now log in.');
    }

    public function logout()
    {
        $this->session->destroy();

        return redirect()->to('/login')->with('success', 'You have been logged out.');
    }
}
