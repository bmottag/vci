<?php
/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('admin', ['namespace' => 'App\Modules\Admin\Controllers'], function($routes){
    $routes->get('employee/(:any)', 'Admin::employee/$1');
    $routes->get('userCertificates/(:any)', 'Admin::userCertificates/$1');
    $routes->get('change_password/(:any)', 'Admin::change_password/$1');
    $routes->get('material', 'Admin::material');
    $routes->get('company', 'Admin::company');
    $routes->get('hazard', 'Admin::hazard');

    $routes->post('cargar-modal-employee', 'Admin::cargarModalEmployee');
    $routes->post('save_employee', 'Admin::save_employee');
    $routes->post('cargar-modal-user-certificate', 'Admin::cargarModalUserCertificate');
    $routes->post('save_employee_certificate', 'Admin::save_employee_certificate');
    $routes->post('update_user_certificate', 'Admin::update_user_certificate');
    $routes->post('delete_user_certificate', 'Admin::delete_user_certificate');
    $routes->post('update_password', 'Admin::update_password');
    $routes->post('cargar-modal-certificate', 'Admin::cargarModalCertificate');
    $routes->post('save_certificate', 'Admin::save_certificate');
    $routes->post('cargar-modal-material', 'Admin::cargarModalMaterial');
    $routes->post('save_material', 'Admin::save_material');
    $routes->post('cargar-modal-company', 'Admin::cargarModalCompany');
    $routes->post('save_company', 'Admin::save_company');
    $routes->post('cargar-modal-hazard', 'Admin::cargarModalHazard');
    $routes->post('save_hazard', 'Admin::save_hazard');

    $routes->match(['get', 'post'], 'certificate', 'Admin::certificate');
});
