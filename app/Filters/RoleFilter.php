<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * "role" filter: only the listed roles may open the page.
 * Example in Routes.php: ['filter' => 'role:admin'] or ['filter' => 'role:vet,admin']
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session('user');

        if (! $user) {
            return redirect()->to('/login')->with('error', 'Please sign in to continue.');
        }

        // $arguments = the roles written after "role:" in Routes.php, e.g. ['admin']
        if (! in_array($user['role'], (array) $arguments, true)) {
            return redirect()->to('/home')->with('error', 'Sorry, you are not allowed to open that page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
