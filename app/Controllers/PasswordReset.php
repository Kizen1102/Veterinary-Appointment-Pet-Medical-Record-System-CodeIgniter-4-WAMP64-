<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Forgot password → reset link → new password.
 *
 * XAMPP cannot send emails, so in development the reset link is shown on the
 * screen. In a real system the link would be emailed to the user instead.
 */
class PasswordReset extends BaseController
{
    // Step 1: "Forgot password?" form (GET /forgot-password)
    public function request()
    {
        return view('auth/forgot_password', ['title' => 'Forgot Password']);
    }

    // Step 2: create a reset link (POST /forgot-password)
    public function sendLink()
    {
        if (! $this->validate(['email' => ['label' => 'Email address', 'rules' => 'required|valid_email']])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $user  = $users->findByEmail($this->request->getPost('email'));
        $link  = null;

        if ($user && $user['is_active']) {
            // A long random secret. Only its hash is saved, like a password.
            $token = bin2hex(random_bytes(32));

            $users->update($user['id'], [
                'reset_token_hash' => hash('sha256', $token),
                'reset_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            ]);

            $link = site_url('reset-password/' . $token);
        }

        // Same message whether or not the email exists, so nobody can check which emails are registered.
        $message = 'If that email is registered, a password reset link has been sent. The link works for 1 hour.';

        return redirect()->to('/forgot-password')
            ->with('success', $message)
            // Development only: show the link because XAMPP cannot send emails.
            ->with('devLink', ENVIRONMENT === 'development' ? $link : null);
    }

    // Step 3: "Choose a new password" form (GET /reset-password/<token>)
    public function resetForm(string $token)
    {
        if (! $this->findUserByToken($token)) {
            return redirect()->to('/forgot-password')->with('error', 'This reset link is invalid or has expired. Please request a new one.');
        }

        return view('auth/reset_password', ['title' => 'Reset Password', 'token' => $token]);
    }

    // Step 4: save the new password (POST /reset-password/<token>)
    public function reset(string $token)
    {
        $user = $this->findUserByToken($token);

        if (! $user) {
            return redirect()->to('/forgot-password')->with('error', 'This reset link is invalid or has expired. Please request a new one.');
        }

        $rules = [
            'password'         => ['label' => 'New password', 'rules' => 'required|min_length[' . UserModel::MIN_PASSWORD_LENGTH . ']'],
            'password_confirm' => ['label' => 'Confirm password', 'rules' => 'required|matches[password]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        // Save the new password and delete the token, so the link cannot be used again.
        (new UserModel())->update($user['id'], [
            'password_hash'    => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'reset_token_hash' => null,
            'reset_expires_at' => null,
        ]);

        return redirect()->to('/login')->with('success', 'Your password has been changed. You can now sign in.');
    }

    // Finds the account for a reset link that exists and has not expired yet.
    private function findUserByToken(string $token): ?array
    {
        return (new UserModel())
            ->where('reset_token_hash', hash('sha256', $token))
            ->where('reset_expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }
}
