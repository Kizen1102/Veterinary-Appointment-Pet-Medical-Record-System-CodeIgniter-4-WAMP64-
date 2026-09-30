<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * "auth" filter: only logged-in users may open the page.
 * Runs BEFORE the controller, so no controller needs its own login check.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session('user');

        // 1. Not logged in → go to the login page
        if (! $user) {
            return redirect()->to('/login')->with('error', 'Please sign in to continue.');
        }

        // 2. Account disabled (or deleted) after signing in → sign them out
        $account = (new UserModel())->find($user['id']);

        if (! $account || ! $account['is_active']) {
            session()->remove('user');

            return redirect()->to('/login')->with('error', 'Your account is no longer active. Please contact the clinic.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after the page is made.
    }
}
