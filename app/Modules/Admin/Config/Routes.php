<?php
/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('admin', ['namespace' => 'App\Modules\Admin\Controllers'], function($routes){
    $routes->get('employee/(:any)', 'Admin::employee/$1');
    $routes->get('userCertificates/(:any)', 'Admin::userCertificates/$1');

    
    $routes->post('cargar-modal-employee', 'Admin::cargarModalEmployee');
    $routes->post('save_employee', 'Admin::save_employee');
    $routes->post('cargar-modal-user-certificate', 'Admin::cargarModalUserCertificate');
    $routes->post('save_employee_certificate', 'Admin::save_employee_certificate');
    $routes->post('update_user_certificate', 'Admin::update_user_certificate');
    $routes->post('delete_user_certificate', 'Admin::delete_user_certificate');
});
