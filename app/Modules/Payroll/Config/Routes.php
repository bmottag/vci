<?php
/**
 * @var \CodeIgniter\Router\RouteCollection $routes
 */

$routes->group('payroll', ['namespace' => 'App\Modules\Payroll\Controllers'], function ($routes) {
    $routes->get('/',                                      'Payroll::add_payroll');
    $routes->get('add_payroll',                            'Payroll::add_payroll');
    $routes->get('add_payroll/(:any)',                     'Payroll::add_payroll/$1');
    $routes->post('savePayroll',                           'Payroll::savePayroll');
    $routes->post('updatePayroll',                         'Payroll::updatePayroll');
    $routes->match(['get', 'post'], 'add_signature/(:num)', 'Payroll::add_signature/$1');
    $routes->get('view_location',                          'Payroll::view_location');
    $routes->post('cargarModalHours',                      'Payroll::cargarModalHours');
    $routes->post('savePayrollHour',                       'Payroll::savePayrollHour');
    $routes->get('generate_period',                        'Payroll::generate_period');
    $routes->get('payrollSearchForm',                      'Payroll::payrollSearchForm');
    $routes->post('payrollSearchForm',                     'Payroll::payrollSearchForm');
    $routes->get('payrollSearchForm/(:any)/(:any)/(:any)', 'Payroll::payrollSearchForm/$1/$2/$3');
    $routes->get('payrollSearchForm/(:any)/(:any)',        'Payroll::payrollSearchForm/$1/$2');
    $routes->get('payrollSearchForm/(:any)',               'Payroll::payrollSearchForm/$1');
    $routes->post('save_paystub',                          'Payroll::save_paystub');
    $routes->match(['get', 'post'], 'reviewPaystubs',      'Payroll::reviewPaystubs');
    $routes->match(['get', 'post'], 'reviewYearly',        'Payroll::reviewYearly');
    $routes->match(['get', 'post'], 'payrollSearchTimeSheet',                      'Payroll::payrollSearchTimeSheet');
    $routes->match(['get', 'post'], 'payrollSearchTimeSheet/(:any)/(:any)',        'Payroll::payrollSearchTimeSheet/$1/$2');
    $routes->get('payroll_check',                          'Payroll::payroll_check');
    $routes->get('generaPaystubPDF/(:num)',                'Payroll::generaPaystubPDF/$1');
    $routes->post('employeeList',                          'Payroll::employeeList');
    $routes->post('periodList',                            'Payroll::periodList');
    $routes->get('hours_payroll_check',                    'Payroll::hours_payroll_check');
    $routes->post('cargarModalJobCode',                    'Payroll::cargarModalJobCode');
    $routes->post('updateTaskWithWO',                      'Payroll::updateTaskWithWO');
});
