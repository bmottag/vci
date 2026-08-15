<?php
/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('vacation', ['namespace' => 'App\Modules\Vacation\Controllers'], function($routes){
    $routes->get('', 'Vacation::index');
    $routes->get('newVacationList', 'Vacation::newVacationList');
    $routes->get('approvedVacationList', 'Vacation::approvedVacationList');
    $routes->get('deniedVacationList', 'Vacation::deniedVacationList');

    $routes->post('cargar-modal', 'Vacation::cargarModal');
    $routes->post('save_vacation', 'Vacation::save_vacation');
    $routes->post('cargar-modal-approved', 'Vacation::cargarModalApproved');
    $routes->post('save_approved', 'Vacation::save_approved');
});
