<?php
namespace App\Modules\Workorders\Controllers;

use App\Controllers\BaseController;
use App\Modules\Workorders\Models\WorkordersModel;
use App\Models\GeneralModel;
use App\Libraries\PdfBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class Workorders extends BaseController
{
    protected $workordersModel;
    protected $generalModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->workordersModel = new WorkordersModel();
        $this->generalModel    = new GeneralModel();
    }

    /**
     * Form Workorders
     * @since 12/1/2017
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function index()
    {
        $idUser  = $this->session->get('id');
        $userRol = $this->session->get('rol');

        $arrParam = [];
        if ($userRol == ID_ROL_BASIC) {
            $arrParam['idEmployee'] = $idUser;
        }

        $data['workOrderInfo'] = $this->workordersModel->get_workordes_by_idUser($arrParam);

        $this->generalModel->deleteRecord([
            'table'      => 'workorder_go_back',
            'primaryKey' => 'fk_id_user',
            'id'         => $idUser,
        ]);

        return $this->render('App\Modules\Workorders\Views\workorder', $data);
    }

    /**
     * Form Add workorder
     * @since 12/1/2017
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function add_workorder($id = 'x')
    {
        $data['information']       = false;
        $data['workorderPersonal'] = false;
        $data['workorderMaterials'] = false;
        $data['deshabilitar']      = '';

        $data['jobs'] = $this->generalModel->get_job(['state' => 1]);

        if ($id !== 'x') {
            $arrParam = ['idWorkOrder' => $id];

            $data['workorderPersonal']  = $this->workordersModel->get_workorder_personal($arrParam);
            $data['workorderMaterials'] = $this->workordersModel->get_workorder_materials($arrParam);
            $data['workorderReceipt']   = $this->workordersModel->get_workorder_receipt($arrParam);
            $data['workorderEquipment'] = $this->workordersModel->get_workorder_equipment($arrParam);
            $data['workorderOcasional'] = $this->workordersModel->get_workorder_ocasional($arrParam);
            $data['workorderState']     = $this->workordersModel->get_workorder_state($id);
            $data['information']        = $this->workordersModel->get_workordes_by_idUser($arrParam);

            if (!$data['information']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('ERROR!!! - You are in the wrong place.');
            }

            $data['employeeTypeList'] = $this->generalModel->get_basic_search([
                'table' => 'param_employee_type',
                'order' => 'employee_type',
                'id'    => 'x',
            ]);

            $userRol       = $this->session->get('rol');
            $workorderState = $data['information'][0]['state'];

            if ($userRol != ID_ROL_SUPER_ADMIN) {
                if ($workorderState == 4) {
                    $data['deshabilitar'] = 'disabled';
                } elseif ($workorderState != 0 && in_array($userRol, [ID_ROL_SAFETY, ID_ROL_SUPERVISOR, ID_ROL_BASIC])) {
                    $data['deshabilitar'] = 'disabled';
                } elseif ($workorderState == 5 && $userRol != ID_ROL_ACCOUNTING_ASSISTANT) {
                    $data['deshabilitar'] = 'disabled';
                } elseif (in_array($workorderState, [2, 3]) && $userRol == ID_ROL_WORKORDER) {
                    $data['deshabilitar'] = 'disabled';
                } elseif ($workorderState == 3 && $userRol == ID_ROL_ENGINEER) {
                    $data['deshabilitar'] = 'disabled';
                } elseif ($workorderState == 1 && in_array($userRol, [ID_ROL_MANAGER, ID_ROL_ACCOUNTING])) {
                    $data['deshabilitar'] = 'disabled';
                }
            }
        }

        return $this->render('App\Modules\Workorders\Views\form_workorder', $data);
    }

    /**
     * Save workorder
     * @since 13/1/2017
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function save_workorder()
    {
        $post   = $this->request->getPost();
        $idUser = $this->session->get('id');
        $data   = [];

        $idWorkorderInicial = $post['hddIdentificador'] ?? '';
        $msj = $idWorkorderInicial != ''
            ? 'You have updated the Work Order, continue uploading the information.'
            : 'You have added a new Work Order, continue uploading the information.';

        if ($idWorkorder = $this->workordersModel->add_workorder($post, $idUser)) {
            $nameForeman = $post['foreman'] ?? '';

            if ($nameForeman != '') {
                $idJob      = $post['jobName'];
                $infoForeman = $this->generalModel->get_basic_search([
                    'table'  => 'param_company_foreman',
                    'order'  => 'id_company_foreman',
                    'column' => 'fk_id_job',
                    'id'     => $idJob,
                ]);
                $idForeman = $infoForeman ? $infoForeman[0]['id_company_foreman'] : '';
                $this->workordersModel->info_foreman($idForeman, $post);
            }

            if (!$idWorkorderInicial) {
                $this->workordersModel->add_workorder_state([
                    'idWorkorder' => $idWorkorder,
                    'observation' => 'New work order.',
                    'state'       => 0,
                ], $idUser);
            }

            $data["status"] = "success";
            $data['message']    = $msj;
            $data['idWorkorder'] = $idWorkorder;
            session()->setFlashdata('retornoExito', $msj);
        } else {
            $data['status']     = 'error';
            $data['idWorkorder'] = '';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    /**
     * Update workorder
     * @since 25/1/2017
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function update_workorder()
    {
        $data              = [];
        $data['idWorkorder'] = $this->request->getPost('hddIdentificador');

        if ($this->generalModel->updateRecord([
            'table'      => 'workorder',
            'primaryKey' => 'id_workorder',
            'id'         => $data['idWorkorder'],
            'column'     => 'state',
            'value'      => 2,
        ])) {
            $data["status"] = "success";
            $data['message'] = 'You have closed the Work Order.';
            session()->setFlashdata('retornoExito', 'You have closed the Work Order');
        } else {
            $data['status']  = 'error';
            $data['message'] = 'Error!!! Ask for help.';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    /**
     * Cargo modal- formulario de captura personal
     * @since 13/1/2017
     * @review 06/05/2026 - new CI4 version
     */
    public function cargarModalPersonal()
    {
        $data['idWorkorder']     = $this->request->getPost('idWorkorder');
        $data['workersList']     = $this->generalModel->get_user(['state' => 1]);
        $data['employeeTypeList'] = $this->generalModel->get_basic_search([
            'table' => 'param_employee_type',
            'order' => 'employee_type',
            'id'    => 'x',
        ]);

        return $this->response
            ->setContentType('text/html')
            ->setBody(view('App\Modules\Workorders\Views\modal_personal', $data));
    }

    /**
     * Save formularios
     * @param varchar $modalToUse: indica que funcion del modelo se debe usar
     * @since 13/1/2017
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function save($modalToUse)
    {
        $post       = $this->request->getPost();
        $idUser     = $this->session->get('id');
        $idWorkOrder = $post['hddidWorkorder'];
        $data       = ['idRecord' => $idWorkOrder];

        $methodsWithPost = ['savePersonal', 'saveMaterial', 'saveReceipt', 'saveOcasional', 'saveHoldBack', 'saveEquipment', 'saveExpense'];
        $methodsWithPostAndUser = ['savePersonal', 'saveMaterial', 'saveReceipt', 'saveOcasional', 'saveEquipment'];

        if (in_array($modalToUse, $methodsWithPostAndUser)) {
            $result = $this->workordersModel->$modalToUse($post, $idUser);
        } elseif ($modalToUse === 'saveHoldBack' || $modalToUse === 'saveExpense') {
            $result = $this->workordersModel->$modalToUse($post);
        } else {
            $result = false;
        }

        if ($result) {
            if ($modalToUse === 'saveExpense') {
                $idJob      = $post['hddidJob'];
                $this->update_wo_expenses_values($idWorkOrder, $idJob);
                $this->generalModel->updateRecord([
                    'table'      => 'workorder',
                    'primaryKey' => 'id_workorder',
                    'id'         => $idWorkOrder,
                    'order'      => 'id_workorder',
                    'column'     => 'expenses_flag',
                    'value'      => 1,
                ]);
            }

            $data['status'] = "success";
            session()->setFlashdata('retornoExito', 'You have added a new record!!');
        } else {
            $data['status'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        $userRol         = $this->session->get('rol');
        $data['controlador'] = ($userRol == 99) ? 'view_workorder' : 'add_workorder';

        return $this->response->setJSON($data);
    }

    /**
     * Delete workorder record
     * @param varchar $tabla: nombre de la tabla de la cual se va a borrar
     * @param int $idValue: id que se va a borrar
     * @param int $idWorkorder: llave primaria de workorder
     * @review 06/05/2026 - new CI4 version
     */
    public function deleteRecord($tabla, $idValue, $idWorkOrder, $vista)
    {
        if (empty($tabla) || empty($idValue) || empty($idWorkOrder)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ERROR!!! - You are in the wrong place.');
        }

        $table    = 'workorder_' . $tabla;
        $primaryKey = 'id_workorder_' . $tabla;

        $oldRow = $this->generalModel->get_basic_search([
            'table'  => $table,
            'order'  => $primaryKey,
            'column' => $primaryKey,
            'id'     => $idValue,
        ]);

        $log = ['old' => $oldRow, 'new' => null];

        (new \App\Libraries\Logger())
            ->user($this->session->get('id'))
            ->type($table)
            ->id($idWorkOrder)
            ->token('delete')
            ->comment(json_encode($log))
            ->log();

        if ($this->generalModel->deleteRecord(['table' => $table, 'primaryKey' => $primaryKey, 'id' => $idValue])) {
            if ($tabla === 'personal' && !empty($oldRow) && isset($oldRow[0]['fk_id_user'])) {
                foreach (['wo_start_project' => 'wo_start_project', 'wo_end_project' => 'wo_end_project'] as $col => $nullCol) {
                    $task = $this->generalModel->get_task([
                        'idWorkOrder' => $idWorkOrder,
                        'idEmployee'  => $oldRow[0]['fk_id_user'],
                        'column'      => $col,
                    ]);
                    if ($task) {
                        $this->generalModel->updateRecord([
                            'table'      => 'task',
                            'primaryKey' => 'id_task',
                            'id'         => $task[0]['id_task'],
                            'column'     => $nullCol,
                            'value'      => null,
                        ]);
                    }
                }
            }

            $this->workordersModel->deleteExpenses([
                'fk_id_workorder'  => $idWorkOrder,
                'submodule'        => $tabla,
                'fk_id_submodule'  => $idValue,
            ]);

            $infoWO  = $this->workordersModel->get_workorder_by_idJob(['idWorkOrder' => $idWorkOrder]);
            $idJob   = $infoWO[0]['fk_id_job'];
            $this->update_wo_expenses_values($idWorkOrder, $idJob);

            $workorderExpense = $this->workordersModel->get_workorder_expense(['idWorkOrder' => $idWorkOrder]);
            if (!$workorderExpense) {
                $this->generalModel->updateRecord([
                    'table'      => 'workorder',
                    'primaryKey' => 'id_workorder',
                    'id'         => $idWorkOrder,
                    'column'     => 'expenses_flag',
                    'value'      => 0,
                ]);
            }

            session()->setFlashdata('retornoExito', 'You have deleted one record from <strong>' . $tabla . '</strong> table.');
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('workorders/' . $vista . '/' . $idWorkOrder));
    }

    /**
     * Cargo modal- formulario de captura Material
     * @since 13/1/2017
     * @review 06/05/2026 - new CI4 version
     */
    public function cargarModalMaterials()
    {
        $idWorkorder    = $this->request->getPost('idWorkorder');
        $porciones      = explode('-', $idWorkorder);
        $data['idWorkorder'] = $porciones[1];
        $data['materialList'] = $this->generalModel->get_basic_search([
            'table' => 'param_material_type',
            'order' => 'material',
            'id'    => 'x',
        ]);

        return $this->response
            ->setContentType('text/html')
            ->setBody(view('App\Modules\Workorders\Views\modal_material', $data));
    }

    /**
     * Cargo modal- formulario de captura Equipment
     * @since 25/1/2017
     * @review 06/05/2026 - new CI4 version
     */
    public function cargarModalEquipment()
    {
        $idWorkorder    = $this->request->getPost('idWorkorder');
        $porciones      = explode('-', $idWorkorder);
        $data['idWorkorder'] = $porciones[1];

        $data['equipmentType'] = $this->generalModel->get_basic_search([
            'table'  => 'param_vehicle_type_2',
            'order'  => 'type_2',
            'column' => 'show_workorder',
            'id'     => 1,
        ]);
        $data['workersList'] = $this->generalModel->get_user(['state' => 1]);

        return $this->response
            ->setContentType('text/html')
            ->setBody(view('App\Modules\Workorders\Views\modal_equipment', $data));
    }

    /**
     * Trucks list by company and type
     * @since 25/1/2017
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function truckList()
    {
        $company = 1;
        $type    = $this->request->getPost('type');
        $html    = "<option value=''>Select...</option>";

        if ($type == 8) {
            $lista = $this->generalModel->get_basic_search([
                'table' => 'param_miscellaneous',
                'order' => 'miscellaneous',
                'id'    => 'x',
            ]);
            if ($lista) {
                foreach ($lista as $fila) {
                    $html .= "<option value='" . esc($fila['id_miscellaneous']) . "'>" . esc($fila['miscellaneous']) . "</option>";
                }
            }
        } elseif ($type == 9) {
            $lista = $this->workordersModel->get_trucks_by_id1();
            if ($lista) {
                foreach ($lista as $fila) {
                    $html .= "<option value='" . esc($fila['id_truck']) . "'>" . esc($fila['unit_number']) . "</option>";
                }
            }
        } else {
            $lista = $this->generalModel->get_trucks_by_id2($company, $type);
            if ($lista) {
                foreach ($lista as $fila) {
                    $html .= "<option value='" . esc($fila['id_truck']) . "'>" . esc($fila['unit_number']) . "</option>";
                }
            }
        }

        return $this->response->setContentType('text/html')->setBody($html);
    }

    /**
     * Company list
     * @since 4/2/2017
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function companyList()
    {
        $companyType = $this->request->getPost('CompanyType');
        $lista       = $this->generalModel->get_basic_search([
            'table'  => 'param_company',
            'order'  => 'company_name',
            'column' => 'company_type',
            'id'     => $companyType,
        ]);

        $html = "<option value=''>Select...</option>";
        if ($lista) {
            foreach ($lista as $fila) {
                $html .= "<option value='" . esc($fila['id_company']) . "'>" . esc($fila['company_name']) . "</option>";
            }
        }

        return $this->response->setContentType('text/html')->setBody($html);
    }

    /**
     * Cargo modal- formulario de captura Ocasional
     * @since 20/2/2017
     * @review 06/05/2026 - new CI4 version
     */
    public function cargarModalOcasional()
    {
        $idWorkorder = $this->request->getPost('idWorkorder');
        $porciones   = explode('-', $idWorkorder);

        if (count($porciones) > 1) {
            $data['idWorkorder'] = $porciones[1];
            $data['companyList'] = $this->generalModel->get_basic_search([
                'table'  => 'param_company',
                'order'  => 'company_name',
                'column' => 'company_type',
                'id'     => 2,
            ]);

            return $this->response
                ->setContentType('text/html')
                ->setBody(view('App\Modules\Workorders\Views\modal_ocasional', $data));
        }
    }

    /**
     * Cargo modal- formulario de captura HOLD BACK
     * @since 12/11/2018
     * @review 05/05/2026 - new CI4 version
     */
    public function cargarModalHoldBack()
    {
        $idWorkorder = $this->request->getPost('idWorkorder');
        $porciones   = explode('-', $idWorkorder);
        $data['idWorkorder'] = $porciones[1];

        return $this->response
            ->setContentType('text/html')
            ->setBody(view('App\Modules\Workorders\Views\modal_hold_back', $data));
    }

    /**
     * Search by JOB CODE
     * @since 21/02/2017
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function search($goBack = 'x')
    {
        $idUser = $this->session->get('id');

        $data['jobList'] = $this->generalModel->get_job(['state' => 1]);

        foreach ([0 => 'noOnfield', 1 => 'noProgress', 2 => 'noRevised', 3 => 'noSend', 4 => 'noClosed', 5 => 'noAccounting'] as $state => $key) {
            $data[$key] = $this->workordersModel->countWorkorders(['state' => $state]);
        }

        //resume by year and status
        $data['yearsData'] = [];

        for ($x = 1; $x <= 3; $x++) {
            $year = date("Y") - $x;

            $states = [0, 1, 2, 3, 4];
            $counts = [];

            foreach ($states as $state) {
                $counts[$state] = $this->workordersModel->countWorkorders([
                    'state' => $state,
                    'year'  => $year
                ]);
            }

            $data['yearsData'][] = [
                'year'   => $year,
                'counts' => $counts
            ];
        }

        if ($goBack === 'y') {
            $workOrderGoBackInfo = $this->workordersModel->get_workorder_go_back($idUser);

            if (!$workOrderGoBackInfo) {
                return redirect()->to(base_url('workorders'));
            }

            $to = date('Y-m-d', strtotime('+1 day', strtotime($workOrderGoBackInfo['post_to'])));
            $arrParam = [
                'jobId'           => $workOrderGoBackInfo['post_id_job'],
                'idWorkOrder'     => $workOrderGoBackInfo['post_id_work_order'],
                'idWorkOrderFrom' => $workOrderGoBackInfo['post_id_wo_from'],
                'idWorkOrderTo'   => $workOrderGoBackInfo['post_id_wo_to'],
                'from'            => $workOrderGoBackInfo['post_from'],
                'to'              => $to,
                'state'           => $workOrderGoBackInfo['post_state'],
            ];

            $data['workOrderInfo'] = $this->workordersModel->get_workorder_by_idJob($arrParam);
            return $this->renderTopOnly('App\Modules\Workorders\Views\asign_rate_list', $data);

        } elseif ($this->request->getPost('jobName') || $this->request->getPost('workOrderNumber') || $this->request->getPost('workOrderNumberFrom') || $this->request->getPost('from')) {
            $data['jobName']          = $this->request->getPost('jobName');
            $data['workOrderNumber']  = $this->request->getPost('workOrderNumber');
            $data['workOrderNumberFrom'] = $this->request->getPost('workOrderNumberFrom');
            $data['workOrderNumberTo']   = $this->request->getPost('workOrderNumberTo');
            $data['from']             = $this->request->getPost('from');
            $data['to']               = $this->request->getPost('to');

            $from = $data['from'] ? $data['from'] : '';
            $to   = $data['to']   ? $data['to']   : '';

            $arrParam = [
                'jobId'           => $this->request->getPost('jobName'),
                'idWorkOrder'     => $this->request->getPost('workOrderNumber'),
                'idWorkOrderFrom' => $this->request->getPost('workOrderNumberFrom'),
                'idWorkOrderTo'   => $this->request->getPost('workOrderNumberTo'),
                'from'            => $from,
                'to'              => $to,
            ];

            $this->workordersModel->saveInfoGoBack($arrParam, $idUser);
            $data['workOrderInfo'] = $this->workordersModel->get_workorder_by_idJob($arrParam);

            return $this->renderTopOnly('App\Modules\Workorders\Views\asign_rate_list', $data);
        }

        return $this->render('App\Modules\Workorders\Views\asign_rate_form_search', $data);
    }

    /**
     * View info WOrk order to asign rate
     * @since 21/2/2017
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function view_workorder($id)
    {
        $arrParam = ['idWorkOrder' => $id];

        $data['workorderExpense']  = $this->workordersModel->get_workorder_expense($arrParam);
        $data['workorderPersonal'] = $this->workordersModel->get_workorder_personal($arrParam);
        $data['workorderMaterials'] = $this->workordersModel->get_workorder_materials($arrParam);
        $data['workorderReceipt']  = $this->workordersModel->get_workorder_receipt($arrParam);
        $data['workorderEquipment'] = $this->workordersModel->get_workorder_equipment($arrParam);
        $data['workorderOcasional'] = $this->workordersModel->get_workorder_ocasional($arrParam);
        $data['workorderHoldBack'] = false;
        $data['information']       = $this->workordersModel->get_workorder_by_idJob($arrParam);

        $arrParam2           = ['idWorkOrder' => $id, 'idJob' => $data['information'][0]['fk_id_job']];
        $arrParam2['table']  = 'workorder_personal';
        $data['incomePersonal'] = $this->workordersModel->countIncome($arrParam2);

        $arrParam2['table']  = 'workorder_materials';
        $data['incomeMaterial'] = $this->workordersModel->countIncome($arrParam2);

        $arrParam2['table']  = 'workorder_equipment';
        $data['incomeEquipment'] = $this->workordersModel->countIncome($arrParam2);

        $arrParam2['table']  = 'workorder_ocasional';
        $data['incomeSubcontractor'] = $this->workordersModel->countIncome($arrParam2);

        $arrParam2['table']  = 'workorder_receipt';
        $data['incomeReceipt'] = $this->workordersModel->countIncome($arrParam2);

        $data['totalWOIncome'] = $data['incomePersonal'] + $data['incomeMaterial'] + $data['incomeEquipment'] + $data['incomeSubcontractor'] + $data['incomeReceipt'];

        $userRol        = $this->session->get('rol');
        $workorderState = $data['information'][0]['state'];
        $data['deshabilitar'] = '';

        if ($workorderState == 4) {
            $data['deshabilitar'] = 'disabled';
        } elseif ($workorderState != 0 && in_array($userRol, [4, 6, 7])) {
            $data['deshabilitar'] = 'disabled';
        } elseif (in_array($workorderState, [2, 3]) && $userRol == 5) {
            $data['deshabilitar'] = 'disabled';
        } elseif ($workorderState == 1 && in_array($userRol, [2, 3])) {
            $data['deshabilitar'] = 'disabled';
        }

        return $this->render('App\Modules\Workorders\Views\asign_rate_form', $data);
    }

    /**
     * Save rate
     * @since 27/2/2017
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function save_rate()
    {
        $post        = $this->request->getPost();
        $idUser      = $this->session->get('id');
        $idWorkorder = $post['hddIdWorkOrder'];

        if ($this->workordersModel->saveRate($post, $idUser, $this->generalModel)) {
            session()->setFlashdata('retornoExito', 'You have saved the Rate!!');
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('workorders/view_workorder/' . $idWorkorder));
    }

    /**
     * Save hour
     * @since 17/4/2017
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function save_hour()
    {
        $post        = $this->request->getPost();
        $idUser      = $this->session->get('id');
        $idWorkorder = $post['hddIdWorkOrder'];

        if ($this->workordersModel->saveRate($post, $idUser, $this->generalModel)) {
            session()->setFlashdata('retornoExito', 'You have saved the Rate!!');
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('workorders/add_workorder/' . $idWorkorder));
    }

    /**
     * Envio de correo a la empresa
     * @since 2/7/2017
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function email($id)
    {
        // TODO: revisar implementación de email en CI4
        session()->setFlashdata('retornoError', 'Email sending not implemented in CI4 yet.');
        return redirect()->to(base_url('workorders/add_workorder/' . $id));
    }

    /**
     * Signature
     * @since 24/11/2017
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
	public function save_signature()
	{
		$imageData = $this->request->getPost('image'); // o el hiddenName que uses
        $idWorkOrder = $this->request->getPost('id');
		$fileName = 'workorder_' . $idWorkOrder . '.png';
		$filePath = WRITEPATH . '../public/images/signature/workorder/' . $fileName;

		if(!$imageData){
			return redirect()->back()->with('retornoError', 'No signature provided.');
		}

		$imageData = str_replace('data:image/png;base64,', '', $imageData);
		$imageData = str_replace(' ', '+', $imageData);

		if(!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0755, true);

		if(file_put_contents($filePath, base64_decode($imageData))){
			$this->generalModel->updateRecord([
                'table'      => 'workorder',
                'primaryKey' => 'id_workorder',
                'id'         => $idWorkOrder,
                'column'     => 'signature_wo',
				"value" => 'images/signature/workorder/' . $fileName
			]);
			return redirect()->back()->with('retornoExito', 'Signature saved successfully.');
		} else {
			return redirect()->back()->with('retornoError', 'Error saving signature.');
		}
	}

    /**
     * Save workorder and send email
     * @since 29/3/2018
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function save_workorder_and_send_email()
    {
        $post   = $this->request->getPost();
        $idUser = $this->session->get('id');
        $data   = [];

        if ($idWorkorder = $this->workordersModel->add_workorder($post, $idUser)) {
            $data["status"] = "success";
            $data['message']    = 'You have update the Work Order and send an email to the contractor, continue uploading the information.';
            $data['idWorkorder'] = $idWorkorder;

            // TODO: implementar envío de email en CI4
            // $this->email_v2($idWorkorder);

            session()->setFlashdata('retornoExito', 'You have updated the Work Order and send an email to the contractor, continue uploading the information.');
        } else {
            $data['status']     = 'error';
            $data['message']    = 'Error!!! Ask for help.';
            $data['idWorkorder'] = '';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    /**
     * Save workorder state
     * @since 11/1/2020
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function save_workorder_state()
    {
        $post   = $this->request->getPost();
        $idUser = $this->session->get('id');
        $data   = [];

        $data['idWorkorder'] = $post['hddIdWorkOrder'];
        $idACS               = $post['hddIdAcs'];
        $status              = $post['state'];
        $msj                 = 'You have added additional information to the Work Order.';

        $arrParam = [
            'idWorkorder' => $data['idWorkorder'],
            'observation' => $post['information'],
            'state'       => $status,
        ];

        if ($this->workordersModel->add_workorder_state($arrParam, $idUser)) {
            $this->workordersModel->update_workorder([
                'idWorkorder' => $data['idWorkorder'],
                'state'       => $status,
                'lastMessage' => $post['information'],
            ]);

            if ($status == IN_PROGRESS && !$idACS) {
                $arrWO = ['idWorkOrder' => $data['idWorkorder']];
                $info  = [
                    'workorder'          => $this->workordersModel->get_workordes_by_idUser($arrWO),
                    'workorderPersonal'  => $this->workordersModel->get_workorder_personal($arrWO),
                    'workorderMaterials' => $this->workordersModel->get_workorder_materials($arrWO),
                    'workorderReceipt'   => $this->workordersModel->get_workorder_receipt($arrWO),
                    'workorderEquipment' => $this->workordersModel->get_workorder_equipment($arrWO),
                    'workorderOcasional' => $this->workordersModel->get_workorder_ocasional($arrWO),
                ];
                $this->workordersModel->clone_workorder($info);

                $haulingList = $this->generalModel->get_basic_search([
                    'table'  => 'hauling',
                    'order'  => 'id_hauling',
                    'column' => 'fk_id_workorder',
                    'id'     => $data['idWorkorder'],
                ]);
                if ($haulingList) {
                    foreach ($haulingList as $hauling) {
                        if ($hauling['state'] != 3) {
                            $this->generalModel->updateRecord([
                                'table'      => 'hauling',
                                'primaryKey' => 'id_hauling',
                                'id'         => $hauling['id_hauling'],
                                'column'     => 'state',
                                'value'      => 2,
                            ]);
                        }
                    }
                }
            }

            $data["status"] = "success";
            $data['message'] = $msj;
            session()->setFlashdata('retornoExito', $msj);
        } else {
            $data['status']  = 'error';
            $data['message'] = 'Error!!! Ask for help.';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    /**
     * Lista de Workorders filtrado por estado
     * @since 22/2/2020
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function wo_by_state($state, $year = 'x')
    {
        $idUser   = $this->session->get('id');
        $from     = date('Y-m-d', mktime(0, 0, 0, 1, 1, $year));
        $to       = date('Y-m-d', mktime(0, 0, 0, 1, 1, $year + 1));

        $arrParam = ['from' => $from, 'to' => $to, 'state' => $state];
        $this->workordersModel->saveInfoGoBack($arrParam, $idUser);

        $data['workOrderInfo'] = $this->workordersModel->get_workorder_by_idJob($arrParam);

        return $this->renderTopOnly('App\Modules\Workorders\Views\asign_rate_list', $data);
    }

    /**
     * Generate WORK ORDER Report in PDF
     * @param int $idWorkOrder
     * @since 4/11/2018
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function generaWorkOrderPDF($idWorkOrder)
    {
        $arrParam = ['idWorkOrder' => $idWorkOrder];

        $data['info'] = $this->workordersModel->get_workorder_by_idJob($arrParam);
        if (empty($data['info'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('No Work Order information found for ID: ' . $idWorkOrder);
        }

        $arrParam['view_pdf']            = true;
        $data['workorderPersonal']  = $this->workordersModel->get_workorder_personal($arrParam);
        $data['workorderMaterials'] = $this->workordersModel->get_workorder_materials($arrParam);
        $data['workorderReceipt']   = $this->workordersModel->get_workorder_receipt($arrParam);
        $data['workorderEquipment'] = $this->workordersModel->get_workorder_equipment($arrParam);
        $data['workorderOcasional'] = $this->workordersModel->get_workorder_ocasional($arrParam);
        $data['workorderHoldBack']  = false;

        $builder = new PdfBuilder();
        $pdf     = $builder->create('WORK ORDER');

        $html = view('App\Modules\Workorders\Views\reporte_work_order', $data);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();

        if (ob_get_length()) {
            ob_end_clean();
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($pdf->Output('work_order_' . $idWorkOrder . '.pdf', 'I'));
    }

    /**
     * Generate Work Order Report in XLS
     * @param int $jobId
     * @since 10/02/2020
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function generaWorkOrderXLS($jobId, $from = '', $to = '')
    {
        $arrParam = ['jobId' => $jobId, 'from' => $from, 'to' => $to];
        $info     = $this->workordersModel->get_workorder_by_idJob($arrParam);

        if (!$info) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('No data found for the selected parameters.');
        }

        $jobCode     = $info[0]['job_description'];
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Work Order Report');

        $this->_xlsSetHeaders($spreadsheet->getActiveSheet(0));

        $j     = 2;
        $total = 0;
        foreach ($info as $row) {
            $woId        = $row['id_workorder'];
            $woParam     = ['idWorkOrder' => $woId];
            $observation = $row['observation'] ?? '';

            $workorderPersonal  = $this->workordersModel->get_workorder_personal($woParam);
            $workorderMaterials = $this->workordersModel->get_workorder_materials($woParam);
            $workorderReceipts  = $this->workordersModel->get_workorder_receipt($woParam);
            $workorderEquipment = $this->workordersModel->get_workorder_equipment($woParam);
            $workorderOcasional = $this->workordersModel->get_workorder_ocasional($woParam);

            if ($workorderPersonal) {
                foreach ($workorderPersonal as $p) {
                    $total += $p['value'];
                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $woId)
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $p['description'])
                        ->setCellValue('H' . $j, $p['name'])
                        ->setCellValue('I' . $j, $p['employee_type'])
                        ->setCellValue('L' . $j, $p['hours'])
                        ->setCellValue('N' . $j, 'Hours')
                        ->setCellValue('O' . $j, $p['rate'])
                        ->setCellValue('Q' . $j, $p['value']);
                    $j++;
                }
            }

            if ($workorderMaterials) {
                foreach ($workorderMaterials as $m) {
                    $total += $m['value'];
                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $woId)
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $m['description'])
                        ->setCellValue('J' . $j, $m['material'])
                        ->setCellValue('M' . $j, $m['quantity'])
                        ->setCellValue('N' . $j, $m['unit'])
                        ->setCellValue('O' . $j, $m['rate'])
                        ->setCellValue('Q' . $j, $m['value']);
                    $j++;
                }
            }

            if ($workorderReceipts) {
                foreach ($workorderReceipts as $r) {
                    $total += $r['value'];
                    $desc = $r['description'] . ' - ' . $r['place'];
                    if ($r['markup'] > 0) {
                        $desc .= ' - Plus M.U.';
                    }
                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $woId)
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $desc)
                        ->setCellValue('Q' . $j, $r['value']);
                    $j++;
                }
            }

            if ($workorderEquipment) {
                foreach ($workorderEquipment as $e) {
                    $total += $e['value'];
                    $equipment = $e['fk_id_type_2'] == 8
                        ? $e['miscellaneous'] . ' - ' . $e['other']
                        : $e['type_2'] . ' - ' . $e['unit_number'] . ' - ' . $e['v_description'];
                    $quantity = $e['quantity'] == 0 ? 1 : $e['quantity'];

                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $woId)
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $e['description'])
                        ->setCellValue('K' . $j, $equipment)
                        ->setCellValue('L' . $j, $e['hours'])
                        ->setCellValue('M' . $j, $quantity)
                        ->setCellValue('N' . $j, 'Hours')
                        ->setCellValue('O' . $j, $e['rate'])
                        ->setCellValue('P' . $j, $e['operatedby'])
                        ->setCellValue('Q' . $j, $e['value']);
                    $j++;
                }
            }

            if ($workorderOcasional) {
                foreach ($workorderOcasional as $o) {
                    $total += $o['value'];
                    $equipment = $o['company_name'] . '-' . $o['equipment'];
                    $hours     = $o['hours'] == 0 ? 1 : $o['hours'];

                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $woId)
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $o['description'])
                        ->setCellValue('K' . $j, $equipment)
                        ->setCellValue('L' . $j, $hours)
                        ->setCellValue('M' . $j, $o['quantity'])
                        ->setCellValue('N' . $j, $o['unit'])
                        ->setCellValue('O' . $j, $o['rate'])
                        ->setCellValue('Q' . $j, $o['value']);
                    $j++;
                }
            }
        }

        $spreadsheet->getActiveSheet()->setCellValue('P' . $j, 'Total Income');
        $spreadsheet->getActiveSheet()->setCellValue('Q' . $j, '$ ' . number_format($total, 2));
        $spreadsheet->getActiveSheet()->getStyle('P' . $j . ':Q' . $j)->getFont()->setBold(true);
        $this->_xlsApplySheetStyles($spreadsheet);

        // Sheet 1 — Personal Income
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(1);
        $spreadsheet->getActiveSheet()->setTitle('Personal Income');
        $this->_xlsSetHeaders($spreadsheet->getActiveSheet());

        $totalP = 0;
        $j      = 2;
        foreach ($info as $row) {
            $woParam     = ['idWorkOrder' => $row['id_workorder']];
            $observation = $row['observation'] ?? '';
            $personal    = $this->workordersModel->get_workorder_personal($woParam);
            if ($personal) {
                foreach ($personal as $p) {
                    $totalP += $p['value'];
                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $row['id_workorder'])
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $p['description'])
                        ->setCellValue('H' . $j, $p['name'])
                        ->setCellValue('I' . $j, $p['employee_type'])
                        ->setCellValue('L' . $j, $p['hours'])
                        ->setCellValue('N' . $j, 'Hours')
                        ->setCellValue('O' . $j, $p['rate'])
                        ->setCellValue('Q' . $j, $p['value']);
                    $j++;
                }
            }
        }
        $spreadsheet->getActiveSheet()->setCellValue('P' . $j, 'Total Income');
        $spreadsheet->getActiveSheet()->setCellValue('Q' . $j, '$ ' . number_format($totalP, 2));
        $spreadsheet->getActiveSheet()->getStyle('P' . $j . ':Q' . $j)->getFont()->setBold(true);
        $this->_xlsApplySheetStyles($spreadsheet);

        // Sheet 2 — Material Income
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(2);
        $spreadsheet->getActiveSheet()->setTitle('Material Income');
        $this->_xlsSetHeaders($spreadsheet->getActiveSheet());

        $totalM = 0;
        $j      = 2;
        foreach ($info as $row) {
            $woParam     = ['idWorkOrder' => $row['id_workorder']];
            $observation = $row['observation'] ?? '';
            $materials   = $this->workordersModel->get_workorder_materials($woParam);
            if ($materials) {
                foreach ($materials as $m) {
                    $totalM += $m['value'];
                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $row['id_workorder'])
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $m['description'])
                        ->setCellValue('J' . $j, $m['material'])
                        ->setCellValue('M' . $j, $m['quantity'])
                        ->setCellValue('N' . $j, $m['unit'])
                        ->setCellValue('O' . $j, $m['rate'])
                        ->setCellValue('Q' . $j, $m['value']);
                    $j++;
                }
            }
        }
        $spreadsheet->getActiveSheet()->setCellValue('P' . $j, 'Total Income');
        $spreadsheet->getActiveSheet()->setCellValue('Q' . $j, '$ ' . number_format($totalM, 2));
        $spreadsheet->getActiveSheet()->getStyle('P' . $j . ':Q' . $j)->getFont()->setBold(true);
        $this->_xlsApplySheetStyles($spreadsheet);

        // Sheet 3 — Receipt Income
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(3);
        $spreadsheet->getActiveSheet()->setTitle('Receipt Income');
        $this->_xlsSetHeaders($spreadsheet->getActiveSheet());

        $totalR = 0;
        $j      = 2;
        foreach ($info as $row) {
            $woParam     = ['idWorkOrder' => $row['id_workorder']];
            $observation = $row['observation'] ?? '';
            $receipts    = $this->workordersModel->get_workorder_receipt($woParam);
            if ($receipts) {
                foreach ($receipts as $r) {
                    $totalR += $r['value'];
                    $desc = $r['description'] . ' - ' . $r['place'];
                    if ($r['markup'] > 0) {
                        $desc .= ' - Plus M.U.';
                    }
                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $row['id_workorder'])
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $desc)
                        ->setCellValue('Q' . $j, $r['value']);
                    $j++;
                }
            }
        }
        $spreadsheet->getActiveSheet()->setCellValue('P' . $j, 'Total Income');
        $spreadsheet->getActiveSheet()->setCellValue('Q' . $j, '$ ' . number_format($totalR, 2));
        $spreadsheet->getActiveSheet()->getStyle('P' . $j . ':Q' . $j)->getFont()->setBold(true);
        $this->_xlsApplySheetStyles($spreadsheet);

        // Sheet 4 — Equipment Income
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(4);
        $spreadsheet->getActiveSheet()->setTitle('Equipment Income');
        $this->_xlsSetHeaders($spreadsheet->getActiveSheet());

        $totalE = 0;
        $j      = 2;
        foreach ($info as $row) {
            $woParam     = ['idWorkOrder' => $row['id_workorder']];
            $observation = $row['observation'] ?? '';
            $equipment   = $this->workordersModel->get_workorder_equipment($woParam);
            if ($equipment) {
                foreach ($equipment as $e) {
                    $totalE += $e['value'];
                    $eq = $e['fk_id_type_2'] == 8
                        ? $e['miscellaneous'] . ' - ' . $e['other']
                        : $e['type_2'] . ' - ' . $e['unit_number'] . ' - ' . $e['v_description'];
                    $qty = $e['quantity'] == 0 ? 1 : $e['quantity'];

                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $row['id_workorder'])
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $e['description'])
                        ->setCellValue('K' . $j, $eq)
                        ->setCellValue('L' . $j, $e['hours'])
                        ->setCellValue('M' . $j, $qty)
                        ->setCellValue('N' . $j, 'Hours')
                        ->setCellValue('O' . $j, $e['rate'])
                        ->setCellValue('P' . $j, $e['operatedby'])
                        ->setCellValue('Q' . $j, $e['value']);
                    $j++;
                }
            }
        }
        $spreadsheet->getActiveSheet()->setCellValue('P' . $j, 'Total Income');
        $spreadsheet->getActiveSheet()->setCellValue('Q' . $j, '$ ' . number_format($totalE, 2));
        $spreadsheet->getActiveSheet()->getStyle('P' . $j . ':Q' . $j)->getFont()->setBold(true);
        $this->_xlsApplySheetStyles($spreadsheet);

        // Sheet 5 — Subcontractor Income
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(5);
        $spreadsheet->getActiveSheet()->setTitle('Subcontractor Income');
        $this->_xlsSetHeaders($spreadsheet->getActiveSheet());

        $totalS = 0;
        $j      = 2;
        foreach ($info as $row) {
            $woParam     = ['idWorkOrder' => $row['id_workorder']];
            $observation = $row['observation'] ?? '';
            $ocasional   = $this->workordersModel->get_workorder_ocasional($woParam);
            if ($ocasional) {
                foreach ($ocasional as $o) {
                    $totalS    += $o['value'];
                    $eq         = $o['company_name'] . '-' . $o['equipment'];
                    $hours      = $o['hours'] == 0 ? 1 : $o['hours'];

                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A' . $j, $row['id_workorder'])
                        ->setCellValue('B' . $j, $row['name'])
                        ->setCellValue('C' . $j, $row['date_issue'])
                        ->setCellValue('D' . $j, $row['date'])
                        ->setCellValue('E' . $j, $row['job_description'])
                        ->setCellValue('F' . $j, $observation)
                        ->setCellValue('G' . $j, $o['description'])
                        ->setCellValue('K' . $j, $eq)
                        ->setCellValue('L' . $j, $hours)
                        ->setCellValue('M' . $j, $o['quantity'])
                        ->setCellValue('N' . $j, $o['unit'])
                        ->setCellValue('O' . $j, $o['rate'])
                        ->setCellValue('Q' . $j, $o['value']);
                    $j++;
                }
            }
        }
        $spreadsheet->getActiveSheet()->setCellValue('P' . $j, 'Total Income');
        $spreadsheet->getActiveSheet()->setCellValue('Q' . $j, '$ ' . number_format($totalS, 2));
        $spreadsheet->getActiveSheet()->getStyle('P' . $j . ':Q' . $j)->getFont()->setBold(true);
        $this->_xlsApplySheetStyles($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment;filename=workorder_' . $jobCode . '.xlsx')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    /**
     * Search by JOB CODE
     * @since 16/03/2020
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function search_income()
    {
        $data['jobList'] = $this->workordersModel->getJobsIncomeDashboard();

        foreach ([0 => 'noOnfield', 1 => 'noProgress', 2 => 'noRevised', 3 => 'noSend', 4 => 'noClosed', 5 => 'noAccounting'] as $state => $key) {
            $data[$key] = $this->workordersModel->countWorkorders(['state' => $state]);
        }

        if ($this->request->getPost('jobName') && $this->request->getPost('from') && $this->request->getPost('to')) {
            $data['idJob'] = $this->request->getPost('jobName');
            $data['from']  = $this->request->getPost('from');
            $data['to']    = $this->request->getPost('to');

            $from = $data['from'];
            $to   = $data['to'];
            $data['fromFormat'] = $from;
            $data['toFormat']   = $to;

            $arrParam = ['idJob' => $data['idJob'], 'from' => $from, 'to' => $to];

            $data['noWO']            = $this->workordersModel->countWorkorders($arrParam);
            $data['hoursPersonal']   = $this->workordersModel->countHoursPersonal($arrParam);

            $arrParam['table'] = 'workorder_personal';
            $data['incomePersonal'] = $this->workordersModel->countIncome($arrParam);

            $arrParam['table'] = 'workorder_materials';
            $data['incomeMaterial'] = $this->workordersModel->countIncome($arrParam);

            $arrParam['table'] = 'workorder_equipment';
            $data['incomeEquipment'] = $this->workordersModel->countIncome($arrParam);

            $arrParam['table'] = 'workorder_ocasional';
            $data['incomeSubcontractor'] = $this->workordersModel->countIncome($arrParam);

            $arrParam['table'] = 'workorder_receipt';
            $data['incomeReceipt'] = $this->workordersModel->countIncome($arrParam);

            $data['total'] = $data['incomePersonal'] + $data['incomeMaterial'] + $data['incomeEquipment'] + $data['incomeSubcontractor'] + $data['incomeReceipt'];

            $data['jobListSearch'] = $this->generalModel->get_job(['idJob' => $data['idJob']]);
        }

        return $this->render('App\Modules\Workorders\Views\form_search_income', $data);
    }

    /**
     * Foreman workorder view to sign
     * @since 4/6/2020
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function foreman_view($id)
    {
        $arrParam = ['idWorkOrder' => $id];

        $data['workorderPersonal']  = $this->workordersModel->get_workorder_personal($arrParam);
        $data['workorderMaterials'] = $this->workordersModel->get_workorder_materials($arrParam);
        $data['workorderEquipment'] = $this->workordersModel->get_workorder_equipment($arrParam);
        $data['workorderOcasional'] = $this->workordersModel->get_workorder_ocasional($arrParam);
        $data['workorderHoldBack']  = false;
        $data['information']        = $this->workordersModel->get_workorder_by_idJob($arrParam);

        return $this->render('App\Modules\Workorders\Views\foreman_view', $data);
    }

    /**
     * Foreman info
     * @since 25/2/2020
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function foremanInfo()
    {
        $idJob   = $this->request->getPost('idJob');
        $jobInfo = $this->generalModel->get_job(['idJob' => $idJob]);

        $infoForeman = $this->generalModel->get_basic_search([
            'table'  => 'param_company_foreman',
            'order'  => 'id_company_foreman',
            'column' => 'fk_id_job',
            'id'     => $idJob,
        ]);

        $data = [
            'status'       => 'success',
            'company_id'   => $jobInfo[0]['fk_id_company'],
            'company_name' => $jobInfo[0]['company_name'],
            'foreman_name'  => '',
            'foreman_movil' => '',
            'foreman_email' => '',
        ];

        if ($infoForeman) {
            $data['foreman_name']  = $infoForeman[0]['foreman_name'];
            $data['foreman_movil'] = $infoForeman[0]['foreman_movil_number'];
            $data['foreman_email'] = $infoForeman[0]['foreman_email'];
        } elseif ($jobInfo[0]['fk_id_company'] > 0 && $jobInfo[0]['fk_id_company'] != '') {
            $infoForeman = $this->generalModel->get_basic_search([
                'table'  => 'param_company_foreman',
                'order'  => 'id_company_foreman',
                'column' => 'fk_id_param_company',
                'id'     => $jobInfo[0]['fk_id_company'],
            ]);
            if ($infoForeman) {
                $data['foreman_name']  = $infoForeman[0]['foreman_name'];
                $data['foreman_movil'] = $infoForeman[0]['foreman_movil_number'];
                $data['foreman_email'] = $infoForeman[0]['foreman_email'];
            }
        }

        return $this->response->setJSON($data);
    }

    /**
     * Envio de mensaje SMS al foreman
     * @since 4/6/2020
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version — SMS sending pending implementation
     */
    public function sendSMSForeman($idWorkOrder)
    {
        // TODO: implementar envío de SMS en CI4 (Twilio)
        $data['linkBack'] = 'workorders/add_workorder/' . $idWorkOrder;
        $data['titulo']   = "<i class='fa fa-list'></i>WORK ORDER";
        $data['clase']    = 'alert-info';
        $data['msj']      = 'SMS sending is not available in this version.';

        return $this->render('App\Modules\Workorders\Views\template\answer', $data);
    }

    /**
     * Load prices WO
     * @since 7/11/2020
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function load_prices_wo()
    {
        $idWO   = $this->request->getPost('identificador');
        $data   = ['idWO' => $idWO];

        $infoWO = $this->workordersModel->get_workorder_by_idJob(['idWorkOrder' => $idWO]);
        $idJob  = $infoWO[0]['fk_id_job'];

        $workorderPersonalRate  = $this->workordersModel->get_workorder_personal_prices($idWO, $idJob);
        $workorderEquipmentRate = $this->workordersModel->get_workorder_equipment_prices($idWO, $idJob);
        $workorderMaterialRate  = $this->workordersModel->get_workorder_material_prices($idWO);

        $data["status"] = "success";

        if ($workorderPersonalRate && !$this->workordersModel->update_wo_personal_rate($workorderPersonalRate)) {
            $data['status']  = 'error';
            $data['message'] = 'Error!!! Contactarse con el Administrador.';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        if ($workorderEquipmentRate && !$this->workordersModel->update_wo_equipment_rate($workorderEquipmentRate)) {
            $data['status']  = 'error';
            $data['message'] = 'Error!!! Contactarse con el Administrador.';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        if ($workorderMaterialRate && !$this->workordersModel->update_wo_material_rate($workorderMaterialRate)) {
            $data['status']  = 'error';
            $data['message'] = 'Error!!! Contactarse con el Administrador.';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        if ($data["status"] === "success") {
            session()->setFlashdata('retornoExito', 'You have loaded the data.');
        }

        return $this->response->setJSON($data);
    }

    /**
     * Cargo modal- formulario de captura Invoice
     * @since 4/1/2021
     * @review 06/05/2026 - new CI4 version
     */
    public function cargarModalReceipts()
    {
        $idWorkorder = $this->request->getPost('idWorkorder');
        $porciones   = explode('-', $idWorkorder);
        $data['idWorkorder'] = $porciones[1];

        return $this->response
            ->setContentType('text/html')
            ->setBody(view('App\Modules\Workorders\Views\modal_receipt', $data));
    }

    /**
     * Actualizar info de invoice
     * @since 4/1/2021
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function update_receipt()
    {
        $post        = $this->request->getPost();
        $idUser      = $this->session->get('id');
        $idWorkorder = $post['hddidWorkorder'];
        $view        = $post['view'];

        if ($this->workordersModel->saveReceipt($post, $idUser)) {
            session()->setFlashdata('retornoExito', 'You have updated the information!!');
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('workorders/' . $view . '/' . $idWorkorder));
    }

    /**
     * Load markup WO
     * @since 4/1/2021
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function load_markup_wo()
    {
        $idWO    = $this->request->getPost('identificador');
        $data    = ['idWO' => $idWO];

        $arrParam = ['idWorkOrder' => $idWO];
        $infoWO   = $this->workordersModel->get_workorder_by_idJob($arrParam);
        $markup   = $infoWO[0]['markup'];

        $workorderMaterials = $this->workordersModel->get_workorder_materials($arrParam);
        $workorderReceipt   = $this->workordersModel->get_workorder_receipt($arrParam);
        $workorderOcasional = $this->workordersModel->get_workorder_ocasional($arrParam);

        $data["status"] = "success";

        if ($workorderReceipt && !$this->workordersModel->update_wo_invoice_markup($workorderReceipt, $markup)) {
            $data['status'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }
        if ($workorderMaterials && !$this->workordersModel->update_wo_material_markup($workorderMaterials, $markup)) {
            $data['status'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }
        if ($workorderOcasional && !$this->workordersModel->update_wo_ocasional_markup($workorderOcasional, $markup)) {
            $data['status'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        if ($data['status'] === 'success') {
            session()->setFlashdata('retornoExito', 'You have loaded the data.');
        }

        return $this->response->setJSON($data);
    }

    /**
     * Cambio de estado de las WO
     * @since 12/1/2021
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function update_wo_state()
    {
        $post   = $this->request->getPost();
        $idUser = $this->session->get('id');
        $data   = [];

        if ($post['wo'] ?? null) {
            if ($this->workordersModel->updateWOState($post, $idUser)) {
                $data["status"] = "success";
                session()->setFlashdata('retornoExito', 'You have updated the state!!');
            } else {
                $data['status'] = 'error';
                session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
            }
        } else {
            $data['status']  = 'error';
            $data['message'] = ' You have to select a W.O.';
            session()->setFlashdata('retornoError', 'You have to select a W.O.');
        }

        return $this->response->setJSON($data);
    }

    /**
     * Cargo modal- formulario de captura Expense
     * @since 13/1/2022
     * @review 05/05/2026 - new CI4 version
     */
    public function cargarModalExpense()
    {
        $data['idWorkorder'] = $this->request->getPost('idWorkorder');

        $arrParam = ['idWorkOrder' => $data['idWorkorder']];
        $data['information'] = $this->workordersModel->get_workorder_by_idJob($arrParam);
        $data['idJob']       = $data['information'][0]['fk_id_job'];

        $data['chapterList']    = $this->generalModel->get_chapter_list(['idJob' => $data['idJob']]);
        $data['sumPercentage']  = $this->generalModel->sumPercentageByJob(['idJob' => $data['idJob']]);

        return $this->response
            ->setContentType('text/html')
            ->setBody(view('App\Modules\Workorders\Views\modal_expense', $data));
    }

    /**
     * Update WO Expenses Values
     * @since 21/1/2023
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    protected function update_wo_expenses_values($idWorkorder, $idJob)
    {
        $arrParam = ['idWorkOrder' => $idWorkorder];
        $workorderExpenses      = $this->workordersModel->get_workorder_expense($arrParam);
        $sumPercentageExpense   = $this->workordersModel->sumPercentageExpense($arrParam);

        $arrParam['idJob'] = $idJob;
        $arrParam['table'] = 'workorder_personal';
        $incomePersonal    = $this->workordersModel->countIncome($arrParam);

        $arrParam['table'] = 'workorder_materials';
        $incomeMaterial    = $this->workordersModel->countIncome($arrParam);

        $arrParam['table'] = 'workorder_equipment';
        $incomeEquipment   = $this->workordersModel->countIncome($arrParam);

        $arrParam['table'] = 'workorder_ocasional';
        $incomeSubcontractor = $this->workordersModel->countIncome($arrParam);

        $arrParam['table'] = 'workorder_receipt';
        $incomeReceipt     = $this->workordersModel->countIncome($arrParam);

        $totalWOIncome = $incomePersonal + $incomeMaterial + $incomeEquipment + $incomeSubcontractor + $incomeReceipt;

        if ($totalWOIncome > 0 && $sumPercentageExpense) {
            $this->generalModel->updateRecord([
                'table'      => 'param_jobs',
                'primaryKey' => 'id_job',
                'id'         => $idJob,
                'column'     => 'flag_expenses',
                'value'      => 1,
            ]);
        }
        return true;
    }

    /**
     * Recalculate Expenses
     * @since 9/06/2023
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function recalculate_expenses()
    {
        $idWorkorder = $this->request->getPost('identificador');
        $data        = ['idWO' => $idWorkorder];

        $infoWO = $this->workordersModel->get_workorder_by_idJob(['idWorkOrder' => $idWorkorder]);
        $idJob  = $infoWO[0]['fk_id_job'];

        if ($this->update_wo_expenses_values($idWorkorder, $idJob)) {
            $data["status"] = "success";
            session()->setFlashdata('retornoExito', 'The information was saved successfully!!');
        } else {
            $data['status'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    /**
     * Attachement List
     * @since 28/6/2023
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function attachmentList()
    {
        $lista = $this->generalModel->get_attachments_by_equipment([
            'idEquipment' => $this->request->getPost('equipmentId'),
        ]);

        $html = '';
        if ($lista) {
            $html .= "<option value=''>Select...</option>";
            foreach ($lista as $fila) {
                $html .= "<option value='" . esc($fila['id_attachment']) . "'>" . esc($fila['attachment_number']) . " - " . esc($fila['attachment_description']) . "</option>";
            }
        }

        return $this->response->setContentType('text/html')->setBody($html);
    }

    /**
     * LOG Workorders
     * @since 20/02/2024
     * @author FOROZCO
     * @review 05/05/2026 - new CI4 version
     */
    public function log($goBack = 'x')
    {
        $idUser = $this->session->get('id');

        $data['jobList'] = $this->generalModel->get_job(['state' => 1]);
        $data['user'] = $this->generalModel->get_user([]);

        foreach ([0 => 'noOnfield', 1 => 'noProgress', 2 => 'noRevised', 3 => 'noSend', 4 => 'noClosed'] as $state => $key) {
            $data[$key] = $this->workordersModel->countWorkorders(['state' => $state]);
        }

        if ($goBack === 'y') {
            $workOrderGoBackInfo = $this->workordersModel->get_workorder_go_back($idUser);

            if (!$workOrderGoBackInfo) {
                return redirect()->to(base_url('workorders'));
            }

            $to = date('Y-m-d', strtotime('+1 day', strtotime($workOrderGoBackInfo['post_to'])));
            $arrParam = [
                'jobId'           => $workOrderGoBackInfo['post_id_job'],
                'idWorkOrder'     => $workOrderGoBackInfo['post_id_work_order'],
                'idWorkOrderFrom' => $workOrderGoBackInfo['post_id_wo_from'],
                'idWorkOrderTo'   => $workOrderGoBackInfo['post_id_wo_to'],
                'from'            => $workOrderGoBackInfo['post_from'],
                'to'              => $to,
                'state'           => $workOrderGoBackInfo['post_state'],
            ];
            $data['workOrderInfo'] = $this->workordersModel->get_workorder_by_idJob($arrParam);
            return $this->renderTopOnly('App\Modules\Workorders\Views\asign_rate_list', $data);

        } elseif ($this->request->getPost('jobName') || $this->request->getPost('user') || $this->request->getPost('from') || $this->request->getPost('workOrderNumber')) {
            $data['jobName']        = $this->request->getPost('jobName');
            $data['workOrderNumber'] = $this->request->getPost('workOrderNumber');
            $data['user']           = $this->request->getPost('user');
            $data['from']           = $this->request->getPost('from');
            $data['to']             = $this->request->getPost('to');

            $from = $data['from'] ? $data['from'] : '';
            $to   = $data['to']   ? date('Y-m-d', strtotime('+1 day', strtotime($data['to']))) : '';

            $arrParam = [
                'jobId'       => $this->request->getPost('jobName'),
                'idWorkOrder' => $this->request->getPost('workOrderNumber'),
                'userId'      => $this->request->getPost('user'),
                'from'        => $from,
                'to'          => $to,
            ];

            $data['workOrderInfo'] = $this->workordersModel->get_workorder_log($arrParam);
            return $this->renderTopOnly('App\Modules\Workorders\Views\log_list', $data);
        }

        return $this->render('App\Modules\Workorders\Views\job_search', $data);
    }

    /**
     * View workorder_expenses
     * @since 23/03/2024
     * @author BMOTTAG
     * @review 06/05/2026 - new CI4 version
     */
    public function workorder_expenses($idWorkOrder)
    {
        $arrParam = ['idWorkOrder' => $idWorkOrder, 'flag_expenses' => true];

        $data['information']        = $this->workordersModel->get_workorder_by_idJob($arrParam);
        $data['workorderPersonal']  = $this->workordersModel->get_workorder_personal($arrParam);
        $data['workorderMaterials'] = $this->workordersModel->get_workorder_materials($arrParam);
        $data['workorderReceipt']   = $this->workordersModel->get_workorder_receipt($arrParam);
        $data['workorderEquipment'] = $this->workordersModel->get_workorder_equipment($arrParam);
        $data['workorderOcasional'] = $this->workordersModel->get_workorder_ocasional($arrParam);
        $data['workorderExpenses']  = $this->generalModel->get_workorder_expense($arrParam);

        $data['jobDetails'] = $this->generalModel->get_job_detail([
            'idJob'  => $data['information'][0]['fk_id_job'],
            'status' => 1,
        ]);

        return $this->render('App\Modules\Workorders\Views\expenses', $data);
    }

    /**
     * Save WO Expenses
     * @since 24/03/2024
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function save_wo_expenses()
    {
        $post        = $this->request->getPost();
        $idWorkOrder = $post['hddidWorkorder'];
        $idJob       = $post['hddidJob'];
        $data        = [];

        if ($this->workordersModel->saveWOExpenses($post)) {
            $this->generalModel->updateRecord([
                'table'      => 'param_jobs',
                'primaryKey' => 'id_job',
                'id'         => $idJob,
                'column'     => 'flag_expenses',
                'value'      => 1,
            ]);
            $this->generalModel->updateRecord([
                'table'      => 'workorder',
                'primaryKey' => 'id_workorder',
                'id'         => $idWorkOrder,
                'order'      => 'id_workorder',
                'column'     => 'expenses_flag',
                'value'      => 1,
            ]);
            $data["status"] = "success";
            session()->setFlashdata('retornoExito', 'You have updated the W.O. Expenses!!');
        } else {
            $data['status'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('workorders/workorder_expenses/' . $idWorkOrder));
    }

    /**
     * Subcontractors list
     * @since 13/02/2025
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function subcontractor_invoice()
    {
        $data['information'] = $this->workordersModel->get_subcontractors_invoice([]);
        return $this->render('App\Modules\Workorders\Views\subcontractors_invoices', $data);
    }

    /**
     * Form To add invoice
     * @since 12/02/2025
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function add_invoice($id = 'x')
    {
        $data['information']  = false;
        $data['deshabilitar'] = '';
        $data['companyList']  = $this->generalModel->get_company(['allSubcontractors' => true]);

        if ($id !== 'x') {
            $data['information'] = $this->workordersModel->get_subcontractors_invoice(['idSubcontractorInvoice' => $id]);
            if (!$data['information']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('ERROR!!! - You are in the wrong place.');
            }
        }

        return $this->render('App\Modules\Workorders\Views\form_invoice', $data);
    }

    /**
     * Save Subcontractor Invoice
     * @since 13/02/2025
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function save_subcontractor_invoice()
    {
        $post   = $this->request->getPost();
        $data   = [];
        $idSubcontractorInvoice = $post['hddIdentificador'];

        $msj = ($idSubcontractorInvoice != '')
            ? 'You have updated a Subcontractor Invoice.'
            : 'You have added a new Subcontractor Invoice.';

        $file = $this->request->getFile('userfile');

        if ($file && $file->isValid() && !$file->hasMoved()) {
            if ($file->getClientMimeType() !== 'application/pdf') {
                $data['status']  = 'error';
                $data['message'] = 'Only PDF files are allowed.';
                return $this->response->setJSON($data);
            }
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'files/sub_invoices/', $newName);
            $archivo = $newName;
        } elseif ($file && !$file->isValid() && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $data['status']  = 'error';
            $data['message'] = 'File upload failed.';
            return $this->response->setJSON($data);
        } else {
            $archivo = 'xxx';
        }

        if ($idRecord = $this->workordersModel->saveSubcontractorInvoice($post, $archivo)) {
            $data["status"] = "success";
            $data['idRecord'] = $idRecord;
            session()->setFlashdata('retornoExito', $msj);
        } else {
            $data['status'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    private function _xlsSetHeaders(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'Work Order #')
            ->setCellValue('B1', 'Supervisor')
            ->setCellValue('C1', 'Date of Issue')
            ->setCellValue('D1', 'Work Order Date')
            ->setCellValue('E1', 'Job Code/Name')
            ->setCellValue('F1', 'Work Done')
            ->setCellValue('G1', 'Description')
            ->setCellValue('H1', 'Employee Name')
            ->setCellValue('I1', 'Employee Type')
            ->setCellValue('J1', 'Material')
            ->setCellValue('K1', 'Equipment')
            ->setCellValue('L1', 'Hours')
            ->setCellValue('M1', 'Quantity')
            ->setCellValue('N1', 'Unit')
            ->setCellValue('O1', 'Unit price')
            ->setCellValue('P1', 'Operated by')
            ->setCellValue('Q1', 'Line Total');
    }

    private function _xlsApplySheetStyles(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();

        $colWidths = ['A' => 15, 'B' => 22, 'C' => 22, 'D' => 20, 'E' => 50, 'F' => 60, 'G' => 60, 'H' => 20, 'I' => 20, 'J' => 15, 'K' => 50, 'L' => 15, 'M' => 15, 'N' => 15, 'O' => 15, 'P' => 15, 'Q' => 15];
        foreach ($colWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $style = $sheet->getStyle('A1:Q1');
        $style->getFont()->setSize(11)->setBold(true);
        $style->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('236e09');
        $style->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
}
