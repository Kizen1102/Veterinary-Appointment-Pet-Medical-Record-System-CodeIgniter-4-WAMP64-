<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Step 2: installation check. Login, dashboard and the other screens are added in the next steps.
if (ENVIRONMENT !== 'production') {
    $routes->get('system-check', 'SystemCheck::index');
}
$routes->get('preview', static fn () => view('auth/preview', ['title' => 'Preview']));

// Lesson 3.2: First-time setup (create the first admin)
$routes->get('setup', 'Setup::index');
$routes->post('setup', 'Setup::store');

// Lesson 3.3: Login and logout
$routes->get('/', 'Auth::login');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->post('logout', 'Auth::logout');

// Lesson 3.4: Sign Up (Pet Owners)
$routes->get('register', 'Register::index');
$routes->post('register', 'Register::store');

// Lesson 3.5: Pages for logged-in users only ("auth" filter)
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('home', 'Home::index');

    // Each role has its own area ("role" filter)
    $routes->get('owner', 'Dashboard::owner', ['filter' => 'role:owner']);
    $routes->get('vet', 'Dashboard::vet', ['filter' => 'role:vet']);
    $routes->get('admin', 'Dashboard::admin', ['filter' => 'role:admin']);
});
