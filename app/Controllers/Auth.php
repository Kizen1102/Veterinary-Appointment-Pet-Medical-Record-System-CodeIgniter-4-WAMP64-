<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Login and logout.
 */
class Auth extends BaseController
{
    // Shows the login form (GET /login)
    public function login()
    {
        // Already logged in? Go straight to the home page.
        if (session('user')) {
            return redirect()->to('/home');
        }

        // No admin yet? The system must be set up first.
        if (! (new UserModel())->hasAdmin()) {
            return redirect()->to('/setup');
        }

        return view('auth/login', ['title' => 'Sign In']);
    }

    // Checks the email and password (POST /login)
    public function attemptLogin()
    {
        // 1. Block password guessing: max 5 tries per minute from the same computer
        $throttler = service('throttler');
        if (! $throttler->check(md5($this->request->getIPAddress()), 5, MINUTE)) {
            return redirect()->back()->withInput()
                ->with('error', 'Too many login attempts. Please wait a minute and try again.');
        }

        // 2. Check that both fields are filled in
        $rules = [
            'email'    => ['label' => 'Email address', 'rules' => 'required|valid_email'],
            'password' => ['label' => 'Password', 'rules' => 'required'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // 3. Find the account and compare the password with the saved hash
        $users = new UserModel();
        $user  = $users->findByEmail($this->request->getPost('email'));

        if (! $user || ! password_verify($this->request->getPost('password'), $user['password_hash'])) {
            // Same message for both cases, so nobody can guess which emails exist
            return redirect()->back()->withInput()->with('error', 'Incorrect email or password.');
        }

        if (! $user['is_active']) {
            return redirect()->back()->withInput()->with('error', 'This account is disabled. Please contact the clinic.');
        }

        // 4. Log the user in: new session ID + save who they are
        session()->regenerate();
        session()->set('user', [
            'id'        => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
        ]);

        $users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/home');
    }

    // Logs out (POST /logout)
    public function logout()
    {
        // Forget who was logged in and switch to a brand-new session ID
        session()->remove('user');
        session()->regenerate(true);

        return redirect()->to('/login')->with('success', 'You have been signed out.');
    }
}
