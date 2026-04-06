<?php
/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('prices', ['namespace' => 'App\Modules\Prices\Controllers'], function($routes){
    $routes->get('employeeTypeUnitPrice/(:num)', 'Prices::employeeTypeUnitPrice/$1');

    $routes->post('load_employee_type', 'Prices::load_employee_type');
});
