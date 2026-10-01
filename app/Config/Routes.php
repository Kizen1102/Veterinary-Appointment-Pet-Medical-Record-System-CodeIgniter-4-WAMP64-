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

// Lesson 3.6: Forgot password
$routes->get('forgot-password', 'PasswordReset::request');
$routes->post('forgot-password', 'PasswordReset::sendLink');
$routes->get('reset-password/(:segment)', 'PasswordReset::resetForm/$1');
$routes->post('reset-password/(:segment)', 'PasswordReset::reset/$1');

// Lesson 3.5: Pages for logged-in users only ("auth" filter)
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('home', 'Home::index');

    // Each role has its own area ("role" filter)
    $routes->get('owner', 'Dashboard::owner', ['filter' => 'role:owner']);

    // Step 5: Pet Owners add pets
    $routes->get('pets/new', 'Pets::create', ['filter' => 'role:owner']);
    $routes->post('pets', 'Pets::store', ['filter' => 'role:owner']);
    $routes->post('pets/(:num)/photo', 'Pets::updatePhoto/$1', ['filter' => 'role:owner']);

    // Step 6.5: Pet Owners edit and archive their pets
    $routes->get('pets/(:num)/edit', 'Pets::edit/$1', ['filter' => 'role:owner']);
    $routes->post('pets/(:num)', 'Pets::update/$1', ['filter' => 'role:owner']);
    $routes->post('pets/(:num)/archive', 'Pets::archive/$1', ['filter' => 'role:owner']);

    // Step 6: Pet Owners book appointments
    $routes->get('appointments', 'Appointments::index', ['filter' => 'role:owner']);
    $routes->get('appointments/new', 'Appointments::create', ['filter' => 'role:owner']);
    $routes->post('appointments', 'Appointments::store', ['filter' => 'role:owner']);
    $routes->post('appointments/(:num)/cancel', 'Appointments::cancel/$1', ['filter' => 'role:owner']);

    $routes->get('vet', 'Dashboard::vet', ['filter' => 'role:vet']);
    $routes->get('admin', 'Dashboard::admin', ['filter' => 'role:admin']);
});
