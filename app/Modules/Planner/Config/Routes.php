<?php

$routes->group('planner', ['namespace' => 'App\Modules\Planner\Controllers'], function ($routes) {
    $routes->get('/',                                'Planner::planner');
    $routes->post('get_daily_plan',                  'Planner::get_daily_plan');
    $routes->post('planner_add_project',             'Planner::planner_add_project');
    $routes->post('planner_remove_project',          'Planner::planner_remove_project');
    $routes->post('planner_save_assignment',         'Planner::planner_save_assignment');
    $routes->post('planner_remove_assignment',       'Planner::planner_remove_assignment');
    $routes->post('planner_save_worker_detail',      'Planner::planner_save_worker_detail');
    $routes->post('planner_save_material',           'Planner::planner_save_material');
    $routes->post('planner_delete_material',         'Planner::planner_delete_material');
    $routes->post('planner_update_material',         'Planner::planner_update_material');
    $routes->post('planner_save_subcontractor',      'Planner::planner_save_subcontractor');
    $routes->post('planner_delete_subcontractor',    'Planner::planner_delete_subcontractor');
    $routes->post('planner_update_subcontractor',    'Planner::planner_update_subcontractor');
    $routes->post('planner_save_observation',        'Planner::planner_save_observation');
    $routes->post('planner_send',                    'Planner::planner_send');
});
