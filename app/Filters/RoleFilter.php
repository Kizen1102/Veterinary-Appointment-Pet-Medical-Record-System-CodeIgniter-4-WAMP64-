<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Restricts a route to the given roles, e.g. ['filter' => 'role:admin,vet'].
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get('user');

        if (! $user) {
            return redirect()->to('/login')->with('error', 'Please log in to continue.');
        }

        if ($arguments !== null && ! in_array($user['role'], (array) $arguments, true)) {
            return redirect()->to('/dashboard')->with('error', 'You are not allowed to access that page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
