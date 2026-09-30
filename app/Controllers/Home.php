<?php

namespace App\Controllers;

/**
 * /home sends each user to the dashboard of their role.
 * (The "auth" filter in Routes.php already made sure someone is logged in.)
 */
class Home extends BaseController
{
    public function index()
    {
        $dashboards = [
            'owner' => '/owner',
            'vet'   => '/vet',
            'admin' => '/admin',
        ];

        return redirect()->to($dashboards[session('user')['role']])
            ->with('error', session('error')); // keep any "not allowed" message
    }
}
