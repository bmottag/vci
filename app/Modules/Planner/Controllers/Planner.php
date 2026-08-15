<?php
namespace App\Modules\Planner\Controllers;

use App\Controllers\BaseController;
use App\Modules\Planner\Models\PlannerModel;
use App\Modules\Programming\Models\ProgrammingModel;
use App\Models\GeneralModel;

class Planner extends BaseController
{
    protected $plannerModel;
    protected $programmingModel;
    protected $generalModel;

    public function __construct()
    {
        $this->plannerModel      = new PlannerModel();
        $this->programmingModel  = new ProgrammingModel();
        $this->generalModel      = new GeneralModel();
    }

    public function planner()
    {
        return $this->renderTopOnly('App\Modules\Planner\Views\planner_board', []);
    }

    public function get_daily_plan()
    {
        $date         = $this->request->getPost('date');
        $programmings = $this->plannerModel->get_programmings_by_date($date);
        $allWorkers   = $this->generalModel->get_user(['state' => 1]) ?: [];
        $dayoffList   = $this->plannerModel->get_dayoff_workers_by_date($date);
        $dayoffIds    = array_map('intval', array_column($dayoffList, 'id_user'));
        $vacationList = $this->plannerModel->get_vacation_workers_by_date($date);
        $vacationIds  = array_map('intval', array_column($vacationList, 'id_user'));
        $horas        = $this->generalModel->get_horas();

        $allVehicles = $this->plannerModel->get_vehicles_inspection([]);
        $vehicleMap  = array_column($allVehicles, 'unit_number', 'id_truck');

        $weeklyHoursSeconds = $this->plannerModel->get_weekly_worked_hours($date);

        $assignedUserIds  = [];
        $assignedEquipIds = [];
        $projects         = [];

        foreach ($programmings as $prog) {
            $workers       = $this->generalModel->get_programming_workers(['idProgramming' => $prog['id_programming']]);
            $workerDetails = [];

            foreach ($workers as $w) {
                $assignedUserIds[] = (int) $w['fk_id_programming_user'];
                $machineIds   = [];
                $machineItems = [];

                if (!empty($w['fk_id_machine'])) {
                    $decoded = json_decode($w['fk_id_machine'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $eid) {
                            $eid                = (int) $eid;
                            $machineIds[]       = $eid;
                            $assignedEquipIds[] = $eid;
                            $machineItems[]     = ['id' => $eid, 'label' => $vehicleMap[$eid] ?? 'Unit #' . $eid];
                        }
                    }
                }

                $workerDetails[] = [
                    'id_programming_worker' => (int) $w['id_programming_worker'],
                    'id_user'               => (int) $w['fk_id_programming_user'],
                    'name'                  => $w['name'],
                    'fk_id_hour'            => (int) $w['fk_id_hour'],
                    'hora'                  => $w['hora'] ?? '',
                    'site'                  => (int) ($w['site'] ?? 1),
                    'safety'                => $w['safety'] ?? '',
                    'description'           => $w['description'] ?? '',
                    'machine_ids'           => $machineIds,
                    'machine_items'         => $machineItems,
                    'creat_wo'              => $w['creat_wo'] ?? '',
                    'fk_id_employee_type'   => (int) ($w['fk_id_employee_type'] ?? 1),
                ];
            }

            $rawMaterials = $this->plannerModel->get_programming_materials(['idProgramming' => $prog['id_programming']]);
            $materialList = [];
            if ($rawMaterials) {
                foreach ($rawMaterials as $m) {
                    $materialList[] = [
                        'id_programming_material' => (int) $m['id_programming_material'],
                        'fk_id_material'          => (int) $m['fk_id_material'],
                        'material'                => $m['material'],
                        'quantity'                => $m['quantity'],
                        'unit'                    => $m['unit'],
                        'description'             => $m['description'] ?? '',
                    ];
                }
            }

            $rawOccasional  = $this->plannerModel->get_programming_occasional(['idProgramming' => $prog['id_programming']]);
            $occasionalList = [];
            if ($rawOccasional) {
                foreach ($rawOccasional as $o) {
                    $occasionalList[] = [
                        'id_programming_ocasional' => (int) $o['id_programming_ocasional'],
                        'fk_id_company'            => (int) $o['fk_id_company'],
                        'company_name'             => $o['company_name'],
                        'equipment'                => $o['equipment'] ?? '',
                        'quantity'                 => $o['quantity'] ?? '',
                        'unit'                     => $o['unit'] ?? '',
                        'hours'                    => $o['hours'] ?? '',
                        'contact'                  => $o['contact'] ?? '',
                        'description'              => $o['description'] ?? '',
                    ];
                }
            }

            $projects[] = [
                'id_programming'  => (int) $prog['id_programming'],
                'job_description' => $prog['job_description'],
                'observation'     => $prog['observation'],
                'fk_id_job'       => (int) $prog['fk_id_job'],
                'fk_id_workorder' => $prog['fk_id_workorder'] ? (int) $prog['fk_id_workorder'] : null,
                'workers'         => $workerDetails,
                'materials'       => $materialList,
                'occasional'      => $occasionalList,
            ];
        }

        $availableWorkers = [];
        foreach ($allWorkers as $w) {
            $uid = (int) $w['id_user'];
            if (!in_array($uid, $assignedUserIds) && !in_array($uid, $dayoffIds) && !in_array($uid, $vacationIds)) {
                $seconds = $weeklyHoursSeconds[$uid] ?? 0;
                $availableWorkers[] = [
                    'id_user'      => $uid,
                    'name'         => $w['first_name'] . ' ' . $w['last_name'],
                    'weekly_hours' => sprintf('%02d:%02d', floor($seconds / 3600), floor(($seconds / 60) % 60)),
                ];
            }
        }

        $equipPool = [];
        foreach ($allVehicles as $v) {
            if (!in_array((int) $v['id_truck'], $assignedEquipIds)) {
                $equipPool[] = ['id' => (int) $v['id_truck'], 'label' => $v['unit_number']];
            }
        }

        $projectPool = $this->plannerModel->get_available_jobs_for_date($date);

        $employeeTypes = $this->generalModel->get_basic_search([
            'table' => 'param_employee_type',
            'order' => 'employee_type',
        ]);

        $materialCatalog = $this->generalModel->get_basic_search([
            'table' => 'param_material_type',
            'order' => 'material',
            'id'    => 'x',
        ]);

        $companyCatalog = $this->generalModel->get_basic_search([
            'table'  => 'param_company',
            'order'  => 'company_name',
            'column' => 'company_type',
            'id'     => 2,
        ]);

        return $this->response->setJSON([
            'projects'          => $projects,
            'available_workers' => $availableWorkers,
            'dayoff_workers'    => $dayoffList,
            'vacation_workers'  => $vacationList,
            'equipment_pool'    => $equipPool,
            'hours'             => $horas,
            'project_pool'      => $projectPool,
            'employee_types'    => $employeeTypes ?: [],
            'material_catalog'  => $materialCatalog ?: [],
            'company_catalog'   => $companyCatalog ?: [],
        ]);
    }

    public function planner_add_project()
    {
        $idJob = (int) $this->request->getPost('id_job');
        $date  = $this->request->getPost('date');

        if (!$idJob || !$date) {
            return $this->response->setJSON(['status' => 'error']);
        }

        if ($this->plannerModel->verifyProject(['idJob' => $idJob, 'date' => $date])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'already_exists']);
        }

        $idProgramming = $this->plannerModel->planner_create_programming($idJob, $date);
        if (!$idProgramming) {
            return $this->response->setJSON(['status' => 'error']);
        }

        $jobInfo = $this->generalModel->get_job(['idJob' => $idJob]);
        return $this->response->setJSON([
            'status'  => 'success',
            'project' => [
                'id_programming'  => $idProgramming,
                'job_description' => $jobInfo[0]['job_description'] ?? '',
                'observation'     => '',
                'fk_id_job'       => $idJob,
                'workers'         => [],
            ],
        ]);
    }

    public function planner_remove_project()
    {
        $idProgramming = (int) $this->request->getPost('id_programming');
        if (!$idProgramming) {
            return $this->response->setJSON(['status' => 'error']);
        }
        if ($this->plannerModel->planner_delete_project($idProgramming)) {
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error']);
    }

    public function planner_save_assignment()
    {
        $idProgramming = (int) $this->request->getPost('id_programming');
        $idUser        = (int) $this->request->getPost('id_user');

        $idPW = $this->plannerModel->planner_add_worker($idProgramming, $idUser);
        if ($idPW) {
            $this->update_state($idProgramming);
            return $this->response->setJSON(['status' => 'success', 'id_programming_worker' => $idPW]);
        }
        return $this->response->setJSON(['status' => 'error']);
    }

    public function planner_remove_assignment()
    {
        $idPW          = (int) $this->request->getPost('id_programming_worker');
        $idProgramming = (int) $this->request->getPost('id_programming');

        if ($this->generalModel->deleteRecord([
            'table'      => 'programming_worker',
            'primaryKey' => 'id_programming_worker',
            'id'         => $idPW,
        ])) {
            $this->update_state($idProgramming);
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error']);
    }

    public function planner_save_worker_detail()
    {
        $idPW  = (int) $this->request->getPost('id_programming_worker');
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');

        $allowed = ['fk_id_hour', 'site', 'safety', 'description', 'creat_wo', 'fk_id_machine', 'fk_id_employee_type'];
        if (!in_array($field, $allowed)) {
            return $this->response->setJSON(['status' => 'error']);
        }

        if ($field === 'fk_id_machine') {
            if (!empty($value)) {
                $ids   = array_filter(explode(',', $value), fn($x) => is_numeric(trim($x)));
                $value = !empty($ids) ? '[' . implode(',', array_map('intval', $ids)) . ']' : null;
            } else {
                $value = null;
            }
        }

        if ($this->plannerModel->planner_save_worker_detail($idPW, $field, $value)) {
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error']);
    }

    public function planner_save_material()
    {
        $idProgramming = (int) $this->request->getPost('id_programming');
        $fkMaterial    = (int) $this->request->getPost('fk_id_material');
        $quantity      = $this->request->getPost('quantity');
        $unit          = $this->request->getPost('unit');
        $description   = $this->request->getPost('description') ?? '';

        if (!$idProgramming || !$fkMaterial || !$quantity || !$unit) {
            return $this->response->setJSON(['status' => 'error']);
        }

        $id = $this->plannerModel->planner_insert_material([
            'fk_id_programming' => $idProgramming,
            'fk_id_material'    => $fkMaterial,
            'quantity'          => $quantity,
            'unit'              => $unit,
            'description'       => $description,
        ]);

        if ($id) {
            $matInfo = $this->generalModel->get_basic_search([
                'table'  => 'param_material_type',
                'order'  => 'material',
                'column' => 'id_material',
                'id'     => $fkMaterial,
            ]);
            return $this->response->setJSON([
                'status'   => 'success',
                'material' => [
                    'id_programming_material' => $id,
                    'fk_id_material'          => $fkMaterial,
                    'material'                => $matInfo ? $matInfo[0]['material'] : '',
                    'quantity'                => $quantity,
                    'unit'                    => $unit,
                    'description'             => $description,
                ],
            ]);
        }
        return $this->response->setJSON(['status' => 'error']);
    }

    public function planner_delete_material()
    {
        $id = (int) $this->request->getPost('id_programming_material');
        if (!$id) {
            return $this->response->setJSON(['status' => 'error']);
        }

        if ($this->generalModel->deleteRecord([
            'table'      => 'programming_material',
            'primaryKey' => 'id_programming_material',
            'id'         => $id,
        ])) {
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error']);
    }

    public function planner_update_material()
    {
        $id    = (int) $this->request->getPost('id_programming_material');
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');

        $allowed = ['quantity', 'unit', 'description'];
        if (!$id || !in_array($field, $allowed)) {
            return $this->response->setJSON(['status' => 'error']);
        }

        $result = $this->db->table('programming_material')
            ->where('id_programming_material', $id)
            ->update([$field => $value]);

        return $this->response->setJSON(['status' => $result ? 'success' : 'error']);
    }

    public function planner_save_subcontractor()
    {
        $idProgramming = (int) $this->request->getPost('id_programming');
        $fkCompany     = (int) $this->request->getPost('fk_id_company');
        $equipment     = $this->request->getPost('equipment');
        $quantity      = $this->request->getPost('quantity');
        $unit          = $this->request->getPost('unit');
        $hours         = $this->request->getPost('hours') ?? '';
        $contact       = $this->request->getPost('contact') ?? '';
        $description   = $this->request->getPost('description') ?? '';

        if (!$idProgramming || !$fkCompany || !$equipment || !$quantity || !$unit || !$contact) {
            return $this->response->setJSON(['status' => 'error']);
        }

        $companyInfo = $this->generalModel->get_basic_search([
            'table'  => 'param_company',
            'order'  => 'company_name',
            'column' => 'id_company',
            'id'     => $fkCompany,
        ]);

        if (!$companyInfo) {
            return $this->response->setJSON(['status' => 'error']);
        }

        $hauling     = $companyInfo[0]['does_hauling'];
        $companyName = $companyInfo[0]['company_name'];
        $rowBase     = [
            'fk_id_programming' => $idProgramming,
            'fk_id_company'     => $fkCompany,
            'equipment'         => $equipment,
            'unit'              => $unit,
            'hours'             => $hours,
            'contact'           => $contact,
            'description'       => $description,
        ];

        $inserted = [];
        if ($hauling == 1) {
            $qty = max(1, (int) $quantity);
            for ($i = 0; $i < $qty; $i++) {
                $this->db->table('programming_ocasional')->insert(array_merge($rowBase, ['quantity' => 1]));
                $id = $this->db->insertID();
                if ($id) {
                    $inserted[] = ['id_programming_ocasional' => $id, 'quantity' => 1];
                }
            }
        } else {
            $this->db->table('programming_ocasional')->insert(array_merge($rowBase, ['quantity' => $quantity]));
            $id = $this->db->insertID();
            if ($id) {
                $inserted[] = ['id_programming_ocasional' => $id, 'quantity' => $quantity];
            }
        }

        if (!empty($inserted)) {
            $result = [];
            foreach ($inserted as $row) {
                $result[] = [
                    'id_programming_ocasional' => (int) $row['id_programming_ocasional'],
                    'fk_id_company'            => $fkCompany,
                    'company_name'             => $companyName,
                    'equipment'                => $equipment,
                    'quantity'                 => $row['quantity'],
                    'unit'                     => $unit,
                    'hours'                    => $hours,
                    'contact'                  => $contact,
                    'description'              => $description,
                ];
            }
            return $this->response->setJSON(['status' => 'success', 'subcontractors' => $result]);
        }

        return $this->response->setJSON(['status' => 'error']);
    }

    public function planner_delete_subcontractor()
    {
        $id = (int) $this->request->getPost('id_programming_ocasional');
        if (!$id) {
            return $this->response->setJSON(['status' => 'error']);
        }

        if ($this->generalModel->deleteRecord([
            'table'      => 'programming_ocasional',
            'primaryKey' => 'id_programming_ocasional',
            'id'         => $id,
        ])) {
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error']);
    }

    public function planner_update_subcontractor()
    {
        $id    = (int) $this->request->getPost('id_programming_ocasional');
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');

        $allowed = ['equipment', 'quantity', 'unit', 'hours', 'contact', 'description'];
        if (!$id || !in_array($field, $allowed)) {
            return $this->response->setJSON(['status' => 'error']);
        }

        $result = $this->db->table('programming_ocasional')
            ->where('id_programming_ocasional', $id)
            ->update([$field => $value]);

        return $this->response->setJSON(['status' => $result ? 'success' : 'error']);
    }

    public function planner_save_observation()
    {
        $idProgramming = (int) $this->request->getPost('id_programming');
        $observation   = $this->request->getPost('observation');

        if (!$idProgramming) {
            return $this->response->setJSON(['status' => 'error']);
        }

        $result = $this->generalModel->updateRecord([
            'table'      => 'programming',
            'primaryKey' => 'id_programming',
            'id'         => $idProgramming,
            'column'     => 'observation',
            'value'      => $observation,
        ]);

        return $this->response->setJSON(['status' => $result ? 'success' : 'error']);
    }

    public function planner_send()
    {
        $idProgramming = (int) $this->request->getPost('id_programming');
        if (!$idProgramming) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid programming ID']);
        }

        $arrParam    = ['idProgramming' => $idProgramming];
        $information = $this->generalModel->get_programming($arrParam);
        if (!$information) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Programming not found']);
        }

        $idWorkorder       = $information[0]['fk_id_workorder'] ?: $this->create_work_order($information);
        $informationWorker = $this->generalModel->get_programming_workers($arrParam);

        $msgHeader  = date('F j, Y', strtotime($information[0]['date_programming']));
        $msgHeader .= "\n" . $information[0]['job_description'];
        $msgHeader .= "\n" . $information[0]['observation'];
        $msgHeader .= "\n\nPlease confirm by replying '1' to this text message!\n";

        $sent             = 0;
        $excluded_numbers = ['686289126', '5068494482', '5068393681', '5870000000'];

        if ($informationWorker) {
            $smsService = new \App\Libraries\SmsService();
            $siteMap    = [1 => 'At the yard - ', 2 => 'At the site - ', 3 => 'At Terminal - ', 4 => 'On-line training - ', 5 => 'At training facility - ', 6 => "At client's office - "];

            foreach ($informationWorker as $info) {
                $informationEquipments = [];
                if ($info['fk_id_machine'] != null) {
                    $id_values             = implode(',', json_decode($info['fk_id_machine'], true));
                    $informationEquipments = $this->generalModel->get_vehicle_info_for_planning([
                        'idValues'        => $id_values,
                        'forTextMessague' => true,
                    ]);
                }

                $mensaje  = $msgHeader . "\n";
                $mensaje .= $siteMap[$info['site']] ?? 'At the yard - ';
                $mensaje .= $info['hora'];
                $mensaje .= "\n" . $info['name'];
                $mensaje .= $info['description'] ? "\n" . $info['description'] : '';
                $mensaje .= $info['fk_id_machine'] != null ? "\nInspect following unit(s):\n" . ($informationEquipments['unit_description'] ?? '') : '';

                if ($info['safety'] == 1) {
                    $mensaje .= "\nFLHA has being assigned to you.";
                } elseif ($info['safety'] == 2) {
                    $mensaje .= "\nIHSR has being assigned to you.";
                } elseif ($info['safety'] == 3) {
                    $mensaje .= "\nJSO has being assigned to you.";
                }

                if ($info['creat_wo'] == 1) {
                    $mensaje .= "\nYou are in charge of the W.O. #" . $idWorkorder;
                }

                if (!in_array($info['movil'], $excluded_numbers)) {
                    try {
                        $message = $smsService->send('+1' . $info['movil'], $mensaje);
                        $this->programmingModel->updateSMSWorkerStatus($info['id_programming_worker'], $message->status, $message->sid);
                        $sent++;
                    } catch (\Exception $e) {
                        log_message('error', '[Planner] SMS send failed for worker ' . $info['id_programming_worker'] . ': ' . $e->getMessage());
                    }
                }
            }
        }

        return $this->response->setJSON([
            'status'           => 'success',
            'workers_notified' => $sent,
            'wo_number'        => $idWorkorder ?: null,
        ]);
    }

    protected function create_work_order($infoPlanning)
    {
        $idProgramming              = $infoPlanning[0]['id_programming'];
        $arrParam                   = ['idProgramming' => $idProgramming];
        $informationWorker          = $this->generalModel->get_programming_workers($arrParam);
        $informationWorkerWithEquip = $this->generalModel->get_programming_equipment($arrParam);
        $programmingMaterials       = $this->plannerModel->get_programming_materials($arrParam);
        $programmingSubcontractor   = $this->plannerModel->get_programming_occasional($arrParam);

        $informationWorkerWO = $this->generalModel->get_programming_workers(['idProgramming' => $idProgramming, 'createWO' => true]);
        $idUser              = $informationWorkerWO ? $informationWorkerWO[0]['fk_id_programming_user'] : $infoPlanning[0]['fk_id_user'];

        $idJob     = $infoPlanning[0]['fk_id_job'];
        $idCompany = $infoPlanning[0]['id_company'];

        $foremanData = ['foreman_name' => '', 'foreman_movil' => '', 'foreman_email' => ''];
        $infoForeman = $this->getForemanData('param_company_foreman', 'id_company_foreman', 'fk_id_job', $idJob);
        if (!$infoForeman && $idCompany > 0) {
            $infoForeman = $this->getForemanData('param_company_foreman', 'id_company_foreman', 'fk_id_param_company', $idCompany);
        }
        if ($infoForeman) {
            $foremanData = [
                'foreman_name'  => $infoForeman['foreman_name'],
                'foreman_movil' => $infoForeman['foreman_movil_number'],
                'foreman_email' => $infoForeman['foreman_email'],
            ];
        }

        $message  = 'A new Work Order was created from the Planning.';
        $arrParam = [
            'idUser'       => $idUser,
            'idJob'        => $idJob,
            'date'         => $infoPlanning[0]['date_programming'],
            'idCompany'    => $idCompany,
            'foremanName'  => $foremanData['foreman_name'],
            'foremanMovil' => $foremanData['foreman_movil'],
            'foremanEmail' => $foremanData['foreman_email'],
            'observation'  => $infoPlanning[0]['observation'],
            'message'      => $message,
        ];

        if ($idWorkorder = $this->programmingModel->add_workorder($arrParam)) {
            $this->generalModel->updateRecord([
                'table'      => 'programming',
                'primaryKey' => 'id_programming',
                'id'         => $idProgramming,
                'column'     => 'fk_id_workorder',
                'value'      => $idWorkorder,
            ]);

            $this->programmingModel->add_workorder_state([
                'idUser'      => $infoPlanning[0]['fk_id_user'],
                'idWorkorder' => $idWorkorder,
                'observation' => $message,
                'state'       => 0,
            ]);

            if ($informationWorker) {
                $map = [
                    'fk_id_programming_user' => 'fk_id_user',
                    'fk_id_employee_type'    => 'fk_id_employee_type',
                    'description'            => 'description',
                    'id_programming_worker'  => 'fk_id_programming_worker',
                ];
                foreach ($informationWorker as $row) {
                    $item = ['fk_id_workorder' => $idWorkorder, 'hours' => 0];
                    foreach ($row as $col => $val) {
                        if (isset($map[$col])) {
                            $dest        = $map[$col];
                            $item[$dest] = ($dest == 'fk_id_employee_type' && (empty($val) || is_null($val))) ? 1 : $val;
                        }
                    }
                    $this->programmingModel->add_item_workorder('workorder_personal', $item);
                }
            }

            if ($informationWorkerWithEquip) {
                $map = [
                    'type_level_2'           => 'fk_id_type_2',
                    'id_vehicle'             => 'fk_id_vehicle',
                    'fk_id_programming_user' => 'operatedby',
                    'description'            => 'description',
                ];
                foreach ($informationWorkerWithEquip as $row) {
                    $item = ['fk_id_workorder' => $idWorkorder, 'quantity' => 1, 'hours' => 0, 'standby' => 2];
                    foreach ($row as $col => $val) {
                        if (isset($map[$col])) {
                            $item[$map[$col]] = $val;
                        }
                    }
                    $this->programmingModel->add_item_workorder('workorder_equipment', $item);
                }
            }

            if ($programmingMaterials) {
                $map = [
                    'fk_id_material'          => 'fk_id_material',
                    'quantity'                => 'quantity',
                    'unit'                    => 'unit',
                    'description'             => 'description',
                    'id_programming_material' => 'fk_id_programming_materials',
                ];
                foreach ($programmingMaterials as $row) {
                    $item = ['fk_id_workorder' => $idWorkorder];
                    foreach ($row as $col => $val) {
                        if (isset($map[$col])) {
                            $item[$map[$col]] = $val;
                        }
                    }
                    $this->programmingModel->add_item_workorder('workorder_materials', $item);
                }
            }

            if ($programmingSubcontractor) {
                $map = [
                    'fk_id_company' => 'fk_id_company', 'equipment'     => 'equipment',
                    'quantity'      => 'quantity',       'unit'          => 'unit',
                    'hours'         => 'hours',          'rate'          => 'rate',
                    'markup'        => 'markup',         'value'         => 'value',
                    'contact'       => 'contact',        'description'   => 'description',
                    'view_pdf'      => 'view_pdf',       'flag_expenses' => 'flag_expenses',
                ];
                foreach ($programmingSubcontractor as $row) {
                    $item       = ['fk_id_workorder' => $idWorkorder, 'unit' => ' ', 'contact' => ' ', 'description' => ' '];
                    foreach ($row as $col => $val) {
                        if (isset($map[$col])) {
                            $item[$map[$col]] = $val;
                        }
                    }
                    $insertedId = $this->programmingModel->add_item_workorder('workorder_ocasional', $item);

                    if ($row['does_hauling'] == 1) {
                        $this->programmingModel->add_item_workorder('hauling', [
                            'fk_id_user'        => $infoPlanning[0]['fk_id_user'],
                            'fk_id_company'     => $row['fk_id_company'],
                            'fk_id_site_from'   => $infoPlanning[0]['fk_id_job'],
                            'fk_id_site_to'     => $infoPlanning[0]['fk_id_job'],
                            'comments'          => $row['description'],
                            'plate'             => $row['unit'],
                            'date_issue'        => $infoPlanning[0]['date_programming'],
                            'fk_id_workorder'   => $idWorkorder,
                            'fk_id_submodule'   => $insertedId,
                            'fk_id_programming' => $idProgramming,
                        ]);
                    }
                }
            }

            return $idWorkorder;
        }

        return false;
    }

    protected function getForemanData($table, $order, $column, $id)
    {
        $result = $this->generalModel->get_basic_search([
            'table'  => $table,
            'order'  => $order,
            'column' => $column,
            'id'     => $id,
        ]);
        return $result ? $result[0] : null;
    }

    protected function update_state($idProgramming)
    {
        $state = $this->plannerModel->countWorkers($idProgramming) >= 1 ? 2 : 1;
        return $this->generalModel->updateRecord([
            'table'      => 'programming',
            'primaryKey' => 'id_programming',
            'id'         => $idProgramming,
            'column'     => 'state',
            'value'      => $state,
        ]);
    }
}
