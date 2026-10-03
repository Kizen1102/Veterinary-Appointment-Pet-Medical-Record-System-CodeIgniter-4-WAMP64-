<?php

namespace App\Controllers;

use Config\Clinic;

/**
 * Public landing page (GET /): what PawRecord is, with links to Sign Up and Login.
 */
class Landing extends BaseController
{
    public function index()
    {
        return view('landing', [
            'title'  => 'Your pet\'s health, all in one place',
            'clinic' => config(Clinic::class)->name,
            'user'   => session('user'), // logged in? then show "Go to dashboard" instead of Login
        ]);
    }
}
