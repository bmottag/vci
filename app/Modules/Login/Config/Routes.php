<?php

/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('login', ['namespace' => 'App\Modules\Login\Controllers'], function($routes){
    $routes->get('', 'Login::index');
    $routes->post('validateUser', 'Login::validateUser');
    $routes->get('logout', 'Login::logout');
});