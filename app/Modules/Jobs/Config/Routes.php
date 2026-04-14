<?php
/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('jobs', ['namespace' => 'App\Modules\Jobs\Controllers'], function($routes){
    $routes->get('', 'Jobs::index');
    $routes->get('safety/(:num)', 'Jobs::safety/$1');
    $routes->get('hazards/(:num)', 'Jobs::hazards/$1');
    $routes->get('add_hazards/(:num)', 'Jobs::add_hazards/$1');
    $routes->get('deleteJobHazard/(:num)/(:num)', 'Jobs::deleteJobHazard/$1/$2');
    $routes->get('hazards_logs/(:num)', 'Jobs::hazards_logs/$1');
    $routes->get('generaJHAPDF/(:num)', 'Jobs::generaJHAPDF/$1');

    $routes->post('save_safety_hazards', 'Jobs::save_safety_hazards');

});
