<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Redirects guests (and disabled accounts) to the login page.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get('user');

        if (! $user) {
            return redirect()->to('/login')->with('error', 'Please log in to continue.');
        }

        // Log out accounts that were disabled after they signed in.
        $active = db_connect()->table('users')->select('is_active')->where('id', $user['id'])->get()->getRow();
        if (! $active || ! $active->is_active) {
            session()->destroy();

            return redirect()->to('/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
