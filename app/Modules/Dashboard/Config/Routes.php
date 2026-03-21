<?php

$routes->group('dashboard', ['namespace' => 'App\Modules\Dashboard\Controllers'], function($routes){
    $routes->get('/', 'Dashboard::admin');
    $routes->get('admin', 'Dashboard::admin');
    $routes->get('mechanic', 'Dashboard::mechanic');
    $routes->get('supervisor', 'Dashboard::supervisor');
    $routes->get('work_order', 'Dashboard::work_order');
    $routes->get('safety', 'Dashboard::safety');
    $routes->get('accounting', 'Dashboard::accounting');
    $routes->get('management', 'Dashboard::management');

    $routes->get('calendar', 'Dashboard::calendar');
    $routes->get('hauling', 'Dashboard::hauling');
    $routes->get('pickups_inspection', 'Dashboard::pickups_inspection');
    $routes->get('construction_equipment_inspection', 'Dashboard::construction_equipment_inspection');
    $routes->get('maintenance', 'Dashboard::maintenance');
    $routes->get('info', 'Dashboard::info');
    $routes->get('info_by_day', 'Dashboard::info_by_day');
    $routes->get('settings', 'Dashboard::settings');
    $routes->get('checkin', 'Dashboard::checkin');
    $routes->get('versions', 'Dashboard::versions');
    $routes->get('without_work_order', 'Dashboard::without_work_order');

    $routes->post('consulta', 'Dashboard::consulta');


    
});