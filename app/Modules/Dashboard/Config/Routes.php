<?php

$routes->group('dashboard', ['namespace' => 'App\Modules\Dashboard\Controllers'], function($routes){
    $routes->get('/', 'Dashboard::admin');
    $routes->get('admin', 'Dashboard::admin');
});