<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Step 2: installation check. Login, dashboard and the other screens are added in the next steps.
if (ENVIRONMENT !== 'production') {
    $routes->get('/', 'SystemCheck::index');
    $routes->get('system-check', 'SystemCheck::index');
}
