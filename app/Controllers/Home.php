<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Temporary home page after login. Each role gets its real dashboard in later steps.
 */
class Home extends BaseController
{
    public function index()
    {
        $user = session('user');

        // Not logged in? Back to the login page. (Lesson 3.5 moves this check into a filter.)
        if (! $user) {
            return redirect()->to('/login');
        }

        return view('home', [
            'title'     => 'Home',
            'user'      => $user,
            'roleLabel' => UserModel::ROLE_LABELS[$user['role']],
        ]);
    }
}
