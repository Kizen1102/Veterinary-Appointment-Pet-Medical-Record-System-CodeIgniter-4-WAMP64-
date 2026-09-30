<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * One dashboard per role. For now each shows a simple page;
 * the real dashboards are built in the next steps.
 */
class Dashboard extends BaseController
{
    public function owner()
    {
        return $this->show('Pet Owner Dashboard');
    }

    public function vet()
    {
        return $this->show('Veterinarian Dashboard');
    }

    public function admin()
    {
        return $this->show('Clinic Staff Dashboard');
    }

    // Shared by the three methods above
    private function show(string $pageName)
    {
        $user = session('user');

        return view('home', [
            'title'     => $pageName,
            'pageName'  => $pageName,
            'user'      => $user,
            'roleLabel' => UserModel::ROLE_LABELS[$user['role']],
        ]);
    }
}
