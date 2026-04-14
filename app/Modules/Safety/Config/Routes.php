<?php
/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('safety', ['namespace' => 'App\Modules\Safety\Controllers'], function($routes){
    $routes->get('add_safety/(:num)/(:any)', 'Safety::add_safety/$1/$2');
    $routes->get('add_safety/(:num)', 'Safety::add_safety/$1');
    $routes->get('upload_info_safety/(:num)', 'Safety::upload_info_safety/$1');

    $routes->post('save_safety', 'Safety::save_safety');
});
