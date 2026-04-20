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
    $routes->get('tool_box/(:num)', 'Jobs::tool_box/$1');
    $routes->get('add_tool_box/(:num)/(:num)', 'Jobs::add_tool_box/$1/$2');
    $routes->get('add_tool_box/(:num)', 'Jobs::add_tool_box/$1');
    $routes->get('add_workers_tool_box/(:num)/(:num)', 'Jobs::add_workers_tool_box/$1/$2');
    $routes->get('deleteToolBoxWorker/(:num)/(:num)/(:num)', 'Jobs::deleteToolBoxWorker/$1/$2/$3');
    $routes->get('deleteToolBoxSubcontractorWorker/(:num)/(:num)/(:num)', 'Jobs::deleteToolBoxSubcontractorWorker/$1/$2/$3');
    $routes->get('generaTemplatePDF/(:num)', 'Jobs::generaTemplatePDF/$1');

    $routes->post('save_safety_hazards', 'Jobs::save_safety_hazards');
    $routes->post('save_tool_box', 'Jobs::save_tool_box');
    $routes->post('save_signature_tool_box', 'Jobs::save_signature_tool_box');
    $routes->post('cargarModalNewHazard', 'Jobs::cargarModalNewHazard');
    $routes->post('save_modal_new_hazard', 'Jobs::save_modal_new_hazard');
    $routes->post('deleteRecordNewHazard', 'Jobs::deleteRecordNewHazard');
    $routes->post('update_new_hazard', 'Jobs::update_new_hazard');
    $routes->post('save_tool_box_workers', 'Jobs::save_tool_box_workers');
    $routes->post('tool_box_One_Worker', 'Jobs::tool_box_One_Worker');
    $routes->post('tool_box_subcontractor_Worker', 'Jobs::tool_box_subcontractor_Worker');
});
