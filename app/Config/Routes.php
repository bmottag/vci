<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setAutoRoute(true);
$routes->get('/', function() {
    return redirect()->to('/login');
});


// Cargar rutas del módulo Login
if (file_exists(APPPATH.'Modules/Login/Config/Routes.php')) {
    require APPPATH.'Modules/Login/Config/Routes.php';
}

// Cargar rutas del módulo Admin
if (file_exists(APPPATH.'Modules/Admin/Config/Routes.php')) {
    require APPPATH.'Modules/Admin/Config/Routes.php';
}

// Cargar rutas del módulo Dashboard
if (file_exists(APPPATH.'Modules/Dashboard/Config/Routes.php')) {
    require APPPATH.'Modules/Dashboard/Config/Routes.php';
}
