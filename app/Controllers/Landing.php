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
        $clinic = config(Clinic::class);

        // Is the clinic open right now? (today is not a closed day, and the time is within the hours)
        $now    = date('H:i');
        $isOpen = ! in_array((int) date('N'), $clinic->closedDays, true)
            && $now >= $clinic->openTime
            && $now < $clinic->closeTime;

        $hours = $clinic->daysLabel . ', '
            . date('g:i A', strtotime($clinic->openTime)) . '–' . date('g:i A', strtotime($clinic->closeTime));

        return view('landing', [
            'title'      => 'Your pet\'s health, all in one place',
            'clinicName' => $clinic->name,
            'isOpen'     => $isOpen,
            'hours'      => $hours,
            'closesAt'   => date('g:i A', strtotime($clinic->closeTime)),
            'user'       => session('user'), // logged in? then show "Go to dashboard" instead of Log in
        ]);
    }
}
