<?php
namespace App\Modules\Planner\Controllers;

use App\Controllers\BaseController;
use App\Modules\Planner\Models\PlannerModel;
use App\Models\GeneralModel;

class Planner extends BaseController
{
    protected $plannerModel;
    protected $generalModel;

    public function __construct()
    {
        $this->plannerModel = new PlannerModel();
        $this->generalModel = new GeneralModel();
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
        $horas        = $this->generalModel->get_horas();

        $allVehicles = $this->plannerModel->get_vehicles_inspection([]);
        $vehicleMap  = array_column($allVehicles, 'unit_number', 'id_truck');

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
                'workers'         => $workerDetails,
                'materials'       => $materialList,
                'occasional'      => $occasionalList,
            ];
        }

        $availableWorkers = [];
        foreach ($allWorkers as $w) {
            $uid = (int) $w['id_user'];
            if (!in_array($uid, $assignedUserIds) && !in_array($uid, $dayoffIds)) {
                $availableWorkers[] = ['id_user' => $uid, 'name' => $w['first_name'] . ' ' . $w['last_name']];
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
