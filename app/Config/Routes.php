<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', static fn () => redirect()->to('/dashboard'));

// Authentication
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::attemptRegister');
$routes->post('logout', 'Auth::logout', ['filter' => 'auth']);

// Logged-in area
$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'Dashboard::index');

    // Pets (owners see only their own)
    $routes->get('pets', 'Pets::index');
    $routes->get('pets/new', 'Pets::create');
    $routes->post('pets', 'Pets::store');
    $routes->get('pets/(:num)', 'Pets::show/$1');
    $routes->get('pets/(:num)/edit', 'Pets::edit/$1');
    $routes->post('pets/(:num)', 'Pets::update/$1');
    $routes->post('pets/(:num)/delete', 'Pets::delete/$1');

    // Appointments
    $routes->get('appointments', 'Appointments::index');
    $routes->get('appointments/new', 'Appointments::create');
    $routes->get('appointments/slots', 'Appointments::slots');
    $routes->post('appointments', 'Appointments::store');
    $routes->get('appointments/(:num)', 'Appointments::show/$1');
    $routes->post('appointments/(:num)/cancel', 'Appointments::cancel/$1');

    // Medical records (owners can read their own pets' records)
    $routes->get('records/(:num)', 'MedicalRecords::show/$1');
});

// Clinic staff
$routes->group('', ['filter' => 'role:admin,staff,vet'], static function (RouteCollection $routes): void {
    $routes->get('owners', 'Owners::index');
    $routes->get('owners/(:num)', 'Owners::show/$1');
    $routes->post('appointments/(:num)', 'Appointments::update/$1');
    $routes->get('pets/(:num)/vaccinations/new', 'Vaccinations::create/$1');
    $routes->post('pets/(:num)/vaccinations', 'Vaccinations::store/$1');
});

// Veterinarians write clinical records
$routes->group('', ['filter' => 'role:admin,vet'], static function (RouteCollection $routes): void {
    $routes->get('records/new', 'MedicalRecords::create');
    $routes->post('records', 'MedicalRecords::store');
    $routes->get('records/(:num)/edit', 'MedicalRecords::edit/$1');
    $routes->post('records/(:num)', 'MedicalRecords::update/$1');
    $routes->post('records/(:num)/delete', 'MedicalRecords::delete/$1');
    $routes->post('vaccinations/(:num)/delete', 'Vaccinations::delete/$1');
});
$routes->group('', ['filter' => 'role:admin,staff'], static function (RouteCollection $routes): void {
    $routes->get('owners/new', 'Owners::create');
    $routes->post('owners', 'Owners::store');
});

// Administration
$routes->group('users', ['filter' => 'role:admin'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Users::index');
    $routes->get('new', 'Users::create');
    $routes->post('/', 'Users::store');
    $routes->post('(:num)/toggle', 'Users::toggle/$1');
});
