<?php
namespace App\Modules\Jobs\Controllers;

use App\Controllers\BaseController;
use App\Modules\Jobs\Models\JobsModel;
use App\Models\GeneralModel;
use TCPDF;

class Jobs extends BaseController
{
    protected $jobsModel;
    protected $generalModel;
	protected $helpers = ['form'];
    
    public function __construct()
    {
        $this->jobsModel   = new JobsModel();
        $this->generalModel   = new GeneralModel();
    }
	/**
	 * Attachments List
	 * @since 23/06/2023
	 * @author BMOTTAG
	 * @review 13/04/2026 - new CI4 version
	 */
	public function index()
	{
		$data['info'] = $this->generalModel->get_job(['state' => 1]);
		$data['dashboardURL'] = $this->session->get("dashboardURL");
		return $this->renderTopOnly('App\Modules\Jobs\Views\jobs_safety_list', $data);
	}

	/**
	 * SAFETY list
	 * @since 2/1/2018
	 * @author BMOTTAG
	 * @review 13/04/2026 - new CI4 version
	 */
	public function safety($idJob)
	{
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);

		//info de safety
		$arrParam = [
			"limit" => 30,
			"idJob" => $idJob
		];
		$data['information'] = $this->generalModel->get_safety($arrParam); //info de safety

		//hazards list
		$data['hazards'] = $this->generalModel->get_job_hazards($idJob);

		return $this->render('App\Modules\Jobs\Views\safety_list', $data);
	}

	/**
	 * Form Upload Hazards 
	 * @since 27/11/2017
	 * @author BMOTTAG
	 * @review 13/04/2026 - new CI4 version
	 */
	public function hazards($idJob)
	{
		$data = [];
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		$data['hazards'] = $this->generalModel->get_job_hazards($idJob);

		return $this->render('App\Modules\Jobs\Views\hazards_list', $data);
	}

	/**
	 * Form Add Hazards
	 * @since 27/11/2017
	 * @author BMOTTAG
	 * @review 13/04/2026 - new CI4 version
	 */
	public function add_hazards($idJob)
	{
		$data = [];
		$data['activityList'] = $this->jobsModel->get_activity_list();
		$data['hazardsByActivity'] = $this->jobsModel->get_hazards_grouped($idJob);
		$data["idJob"] = $idJob;
		return $this->render('App\Modules\Jobs\Views\form_add_hazards', $data);
	}

	/**
	 * Delete Job hazard
	 * @review 14/04/2026 - new CI4 version
	 */
	public function deleteJobHazard($idJobHazard, $idJob)
	{
		if (empty($idJobHazard) || empty($idJob)) {
			throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid IDs');
		}

		$arrParam = [
			"table" => "job_hazards",
			"primaryKey" => "id_job_hazard",
			"id" => $idJobHazard
		];
		if ($this->generalModel->deleteRecord($arrParam)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have deleted one hazard.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('jobs/hazards/' . $idJob));
	}

	/**
	 * Hazards logs
	 * @since 21/8/2018
	 * @author BMOTTAG
	 * @review 14/04/2026 - new CI4 version
	 */
	public function hazards_logs($idJob)
	{
		$data = [];
		$data['info'] = $this->jobsModel->get_hazards_logs(["idJob" => $idJob]);
		$data["idJob"] = $idJob;
		return $this->render('App\Modules\Jobs\Views\hazards_logs', $data);
	}

	/**
	 * Generate JHA - JOB HAZARDS ANALYSIS Report in PDF
	 * @param int $idJobHazardLog
	 * @since 6/9/2018
	 * @author BMOTTAG
	 * @review 14/04/2026 - new CI4 version
	 */
	public function generaJHAPDF($idJobHazardLog)
	{
		$pdf = new TCPDF();

		$pdf->SetCreator('VCI');
		$pdf->SetAuthor('VCI');
		$pdf->SetTitle('JOB HAZARDS ANALYSIS Report');

		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// 👇 espacio para logo
		$pdf->SetMargins(10, 25, 10);
		$pdf->SetAutoPageBreak(TRUE, 10);

		$pdf->SetFont('dejavusans', '', 8);

		$data['info'] = $this->jobsModel->get_hazards_logs(["idJobHazardLog" => $idJobHazardLog]);

		//hazards list
		$data['hazards'] = $this->jobsModel->get_job_hazards_v2($data['info'][0]['id_job']);
		$vista = "jobs/reporte_jha_pdf";

		$pdf->AddPage();

		// LOGO
		$logo = FCPATH . 'images/logo.png';

		if (is_file($logo)) {
			$pdf->Image($logo, 10, 8, 30);
		}

		$html = view($vista, $data);

		$pdf->writeHTML($html, true, false, true, false, '');

		$pdf->lastPage();

		$filename = 'jha_' . $idJobHazardLog . '.pdf';

		return $this->response
			->setHeader('Content-Type', 'application/pdf')
			->setBody($pdf->Output($filename, 'I'));
	}

	/**
	 * tool_box list
	 * @since 24/10/2017
	 * @author BMOTTAG
	 * @review 13/04/2026 - new CI4 version
	 */
	public function tool_box($idJob)
	{
		$data = [];
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		$data['information'] = $this->generalModel->get_tool_box(["idJob" => $idJob]);
		return $this->render('App\Modules\Jobs\Views\tool_box_list', $data);
	}

	/**
	 * Form Tool Box
	 * @since 24/10/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function add_tool_box($idJob, $idToolBox = 'x')
	{
		$data = [];
		$data['information'] = FALSE;
		$data['deshabilitar'] = '';
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);

		$data['companyList'] = $this->generalModel->get_basic_search([
			"table" => "param_company",
			"order" => "company_name",
			"column" => "company_type",
			"id" => 2
		]); //company list

		$data['workersList'] = $this->generalModel->get_user(["state" => 1]); //workers list

		//si envio el id, entonces busco la informacion 
		if ($idToolBox != 'x') {

			$data['information'] = $this->generalModel->get_tool_box(["idToolBox" => $idToolBox]);
			if (!$data['information']) {
				throw new \Exception('ERROR!!! - You are in the wrong place.');
			}
			$data['newHazards'] = $this->jobsModel->get_new_hazards($idToolBox); //new hazard list
			$data['toolBoxWorkers'] = $this->jobsModel->get_tool_box_workers($idToolBox); //workers list

			//tool box subcontractors workers list
			$data['toolBoxSubcontractorsWorkers'] = $this->jobsModel->get_tool_box_subcontractors_workers($idToolBox);
		}
		return $this->render('App\Modules\Jobs\Views\form_tool_box', $data);
	}

	/**
	 * Save tool box
	 * @since 24/10/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function save_tool_box()
	{
		$data = [];
		$post = $this->request->getPost();
		$data["idRecord"] =  $post['hddIdJob'];
		if ($idToolBox = $this->jobsModel->add_TOOLBOX($post)) {
			$data["idToolBox"] = $idToolBox;
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have saved the IHSR, continue uploading the information!!');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Signature
	 * param $typo: supervisor / worker
	 * param $idToolBox: llave principal del formulario
	 * param $idWorker: llave principal del trabajador
	 * @since 24/5/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function save_signature_tool_box()
	{
		$imageData = $this->request->getPost('image'); 
		$id = $this->request->getPost('extraValue'); 
		$typo = $this->request->getPost('otherValue'); 
		
		switch ($typo) {
			case "supervisor":
				$fileName = "supervisor_" . $id . ".png";
				$arrParam = [
					"table" => "tool_box",
					"primaryKey" => "id_tool_box",
					"id" => $id,
					"column" => "signature",
					"value" => 'images/signature/tool_box/' . $fileName
				];
				break;

			case "worker":
				$fileName = "worker_" . $id . ".png";
				$arrParam = [
					"table" => "tool_box_workers",
					"primaryKey" => "id_tool_box_worker",
					"id" => $id,
					"column" => "signature",
					"value" => 'images/signature/tool_box/' . $fileName
				];
				break;

			case "subcontractor":
				$fileName = "subcontractor_" . $id . ".png";
				$arrParam = [
					"table" => "tool_box_workers_subcontractor",
					"primaryKey" => "id_tool_box_subcontractor",
					"id" => $id,
					"column" => "signature",
					"value" => 'images/signature/tool_box/' . $fileName
				];
				break;

			default:
				return $this->response->setJSON([
					"status" => "error",
					"message" => "Invalid user type"
				]);
		}
		$filePath = WRITEPATH . '../public/images/signature/tool_box/' . $fileName;

		if(!$imageData){
			return redirect()->back()->with('error', 'No signature provided.');
		}

		$imageData = str_replace('data:image/png;base64,', '', $imageData);
		$imageData = str_replace(' ', '+', $imageData);

		if(!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0755, true);

		if(file_put_contents($filePath, base64_decode($imageData))){
			$this->generalModel->updateRecord($arrParam);
			return redirect()->back()->with('retornoExito', 'Signature saved successfully.');
		} else {
			return redirect()->back()->with('retornoError', 'Error saving signature.');
		}
	}

	/**
	 * Cargo modal- formulario de captura new hazard
	 * @since 25/10/2017
	 * @review 20/04/2026 - new CI4 version
	 */
	public function cargarModalNewHazard()
	{
		$data = [];

		$idToolBox = $this->request->getPost("idToolBox");
		//como se coloca un ID diferente para que no entre en conflicto con los otros modales, toca sacar el ID
		$porciones = explode("-", $idToolBox);
		$data["idToolBox"] = $porciones[1];

		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Jobs\Views\modal_new_hazard', $data));
	}

	/**
	 * Save new hazard
	 * @since 25/10/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function save_modal_new_hazard()
	{
		$data = [];
		$post = $this->request->getPost();
		$data["idToolBox"] = $post['hddidToolBox'];
		//buscar ID del JOB
		$infoToolBox = $this->generalModel->get_tool_box(["idToolBox" => $data["idToolBox"]]);
		$data["idJob"] = $infoToolBox[0]["fk_id_job"];

		if ($this->jobsModel->saveNewHazard($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', "You have added a new record!!");
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Delete NEW HAZARD
	 * @review 20/04/2026 - new CI4 version
	 */
	public function deleteRecordNewHazard()
	{
		$data = [];
		$post = $this->request->getPost();
		$identificador = $post['identificador'];
		//toca recuperar todos los ID
		$porciones = explode("-", $identificador);

		$idNewHazard = $porciones[0];
		$idToolBox = $porciones[1];
		$idJob = $porciones[2];

		$data["idRecord"] = $idJob . '/' . $idToolBox;

		//eliminaos registros
		$arrParam = array(
			"table" => "tool_box_new_hazard",
			"primaryKey" => "id_new_hazard",
			"id" => $idNewHazard
		);
		if ($this->generalModel->deleteRecord($arrParam)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have deleted one record');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Update new hazard
	 * para editar un registro de new hazard
	 * @since 25/10/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function update_new_hazard()
	{
		$post = $this->request->getPost();
		$idJob = $post['hddIdJob'] ?? null;
		$idToolBox = $post['hddIdToolBox'] ?? null;

		if ($this->jobsModel->updateNewHazard($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', "You have updated the record!!");
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('jobs/add_tool_box/' . $idJob . '/' . $idToolBox));
	}

	/**
	 * Form Add Workers tool box
	 * @since 2/11/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function add_workers_tool_box($idJob, $idToolBox)
	{
		$workersList = $this->generalModel->get_user(["state" => 1]); //workers list

		// 🔥 traemos todos los workers asignados de una sola vez
		$selectedWorkers = array_column(
			$this->jobsModel->get_selected_workers_toolbox($idToolBox),
			'fk_id_user'
		);

		// 🔁 marcamos checked en memoria
		foreach ($workersList as &$worker) {
			$worker['found'] = in_array($worker['id_user'], $selectedWorkers);
		}

		$data = [
			'workersList' => $workersList,
			'idToolBox' => $idToolBox,
			'idJob' => $idJob
		];

		return $this->render('App\Modules\Jobs\Views\form_add_workers', $data);
	}
	
	/**
	 * Save worker
	 * @since 2/11/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function save_tool_box_workers()
	{			
		$post = $this->request->getPost();
		$idToolBox = $post['hddIdToolBox'];
		$idJob = $post['hddIdJob'];
		
		$data = [];
		$data["idRecord"] = $idJob . "/" . $idToolBox;
		if ($this->jobsModel->add_tool_box_worker($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have added the Workers, remember to get the signature of each one.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
    }

	/**
	 * Delete tool box worker
	 * @review 20/04/2026 - new CI4 version
	 */
	public function deleteToolBoxWorker($idJob, $idToolBox, $idToolBoxWorker)
	{
		$arrParam = [
			"table" => "tool_box_workers",
			"primaryKey" => "id_tool_box_worker",
			"id" => $idToolBoxWorker
		];
		if ($this->generalModel->deleteRecord($arrParam)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have deleted one worker.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('jobs/add_tool_box/' . $idJob . '/' . $idToolBox));
	}

	/**
	 * Safe one worker to the TOOL BOX
	 * @review 20/04/2026 - new CI4 version
	 */
	public function tool_box_One_Worker()
	{
		$post = $this->request->getPost();
		$idToolBox = $post['hddIdToolBox'];
		$idJob = $post['hddIdJob'];
		
		if ($this->jobsModel->toolBoxSaveOneWorker($post)) {
			session()->setFlashdata('retornoExito', 'You have added one Worker.');
		} else {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('jobs/add_tool_box/' . $idJob . '/' . $idToolBox));
	}

	/**
	 * Tool box subcontractor worker
	 * @review 20/04/2026 - new CI4 version
	 */
	public function tool_box_subcontractor_Worker()
	{
		$post = $this->request->getPost();
		$idToolBox = $post['hddIdToolBox'];
		$idJob = $post['hddIdJob'];

		if ($this->jobsModel->saveSubcontractorWorker($post)) {
			session()->setFlashdata('retornoExito', 'You have added one Worker.');
		} else {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('jobs/add_tool_box/' . $idJob . '/' . $idToolBox));
	}

	/**
	 * Delete tool box subcontractor
	 * @review 20/04/2026 - new CI4 version
	 */
	public function deleteToolBoxSubcontractorWorker($idJob, $idToolBox, $idToolBoxSubcontractor)
	{
		$arrParam = [
			"table" => "tool_box_workers_subcontractor",
			"primaryKey" => "id_tool_box_subcontractor",
			"id" => $idToolBoxSubcontractor
		];
		if ($this->generalModel->deleteRecord($arrParam)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have deleted one worker.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('jobs/add_tool_box/' . $idJob . '/' . $idToolBox));
	}

	/**
	 * Generate Template Report in PDF
	 * @param int $idToolBox
	 * @since 2/11/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function generaTemplatePDF($idToolBox)
	{
		$pdf = new TCPDF();

		$pdf->SetCreator('VCI');
		$pdf->SetAuthor('VCI');
		$pdf->SetTitle('IHSR Report');

		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// 👇 espacio para logo
		$pdf->SetMargins(10, 25, 10);
		$pdf->SetAutoPageBreak(TRUE, 10);

		$pdf->SetFont('dejavusans', '', 8);

		$data['info'] = $this->generalModel->get_tool_box(["idToolBox" => $idToolBox]);
		$data['newHazards'] = $this->jobsModel->get_new_hazards($idToolBox); //new hazard list
		$data['toolBoxWorkers'] = $this->jobsModel->get_tool_box_workers($idToolBox); //workers list
		$data['subcontractors'] = $this->jobsModel->get_tool_box_subcontractors_workers($idToolBox); //subcontractor list

		$pdf->AddPage();

		// LOGO
		$logo = FCPATH . 'images/logo.png';

		if (is_file($logo)) {
			$pdf->Image($logo, 10, 8, 30);
		}

		$html = view('jobs/reporte_pdf', $data);

		$pdf->writeHTML($html, true, false, true, false, '');

		$pdf->lastPage();

		$filename = 'IHSR_' . $idToolBox . '.pdf';

		return $this->response
			->setHeader('Content-Type', 'application/pdf')
			->setBody($pdf->Output($filename, 'I'));
	}

	/**
	 * Form ERP
	 * @since 20/11/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function erp($idJob)
	{
		$data = [];
		$data['information'] = FALSE;
		$data['trainingWorkers'] = FALSE;
		$data['deshabilitar'] = '';

		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		$data['workersList'] = $this->generalModel->get_user(["state" => 1]); //worker list

		//ERP info
		$data['information'] = $this->jobsModel->get_erp(["idJob" => $idJob]);

		//erp training list
		$data['trainingWorkers'] = $this->jobsModel->get_erp_training_workers($idJob);

		return $this->render('App\Modules\Jobs\Views\form_erp', $data);
	}

	/**
	 * Save ERP
	 * @since 20/11/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function save_erp()
	{
		$data = [];
		$post = $this->request->getPost();
		$data["idRecord"] =  $post['hddIdJob'];
		if ($this->jobsModel->add_erp($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have saved the ERP!!');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Form ERP - PERSONNEL
	 * @since 4/5/2018
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function erp_personnel($idJob)
	{
		$data = [];
		$data['information'] = FALSE;
		$data['trainingWorkers'] = FALSE;
		$data['deshabilitar'] = '';

		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		$data['workersList'] = $this->generalModel->get_user(["state" => 1]); //worker list

		//ERP info
		$data['information'] = $this->jobsModel->get_erp(["idJob" => $idJob]);

		//erp training list
		$data['trainingWorkers'] = $this->jobsModel->get_erp_training_workers($idJob);

		return $this->render('App\Modules\Jobs\Views\form_erp_personnel', $data);
	}

	/**
	 * Form Add Workers for ERP Trainning
	 * @since 23/11/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function add_workers_training($id)
	{
		$workersList = $this->generalModel->get_user(["state" => 1]); //workers list

		// 🔥 traemos todos los workers asignados de una sola vez
		$selectedWorkers = array_column(
			$this->jobsModel->get_selected_workers_erp($id),
			'fk_id_user'
		);

		// 🔁 marcamos checked en memoria
		foreach ($workersList as &$worker) {
			$worker['found'] = in_array($worker['id_user'], $selectedWorkers);
		}

		$data = [
			'workersList' => $workersList,
			'idJob' => $id
		];

		return $this->render('App\Modules\Jobs\Views\form_add_workers_training', $data);
	}

	/**
	 * Save worker trainigno
	 * @since 23/11/2017
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function save_training_workers()
	{
		$data = [];
		$post = $this->request->getPost();
		$data["idRecord"] =  $post['hddIdJob'];
		if ($this->jobsModel->add_training_worker($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have added the Workers, remember to get the signature of each one.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Save one worker to the ERP TRAINING
	 * @review 20/04/2026 - new CI4 version
	 */
	public function save_one_erp_training_worker()
	{
		$post = $this->request->getPost();
		$idJob = $post['hddId'];
		
		if ($this->jobsModel->saveOneWorker($post)) {
			session()->setFlashdata('retornoExito', 'You have added one Worker.');
		} else {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('jobs/erp_personnel/' . $idJob ));
	}

	/**
	 * Update infor personal
	 * @since 11/4/2021
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function update_erp_personnel()
	{
		$post = $this->request->getPost();
		$idJob = $post['hddIdERP'];

		if ($this->jobsModel->updateERPWorker($post)) {
			session()->setFlashdata('retornoExito', "You have saved the Worker Information!!");
		} else {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('jobs/erp_personnel/' . $idJob ));
	}

	/**
	 * Delete ERP TRAINING worker
	 * @review 20/04/2026 - new CI4 version
	 */
	public function deleteERPTRAINGINWorker($idJob, $idErpTrainingWorker)
	{
		$arrParam = [
			"table" => "erp_training_workers",
			"primaryKey" => "id_erp_training_worker",
			"id" => $idErpTrainingWorker
		];
		if ($this->generalModel->deleteRecord($arrParam)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have deleted one worker.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('jobs/erp_personnel/' . $idJob));
	}

	/**
	 * Form ERP - MAP
	 * @since 4/5/2018
	 * @author BMOTTAG
	 * @review 20/04/2026 - new CI4 version
	 */
	public function erp_map($idJob)
	{
		$data['information'] = FALSE;
		$data['deshabilitar'] = '';
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		$data['information'] = $this->jobsModel->get_erp(["idJob" => $idJob]);
		return $this->render('App\Modules\Jobs\Views\form_erp_map', $data);
	}

	/**
	 * FUNCIÓN PARA SUBIR LA IMAGEN 
	 * @review 20/04/2026 - new CI4 version
	 */
	public function do_upload()
	{
		$idJob = $this->request->getPost('hddIdJobMap');
		$file = $this->request->getFile('userfile');

		if (!$file->isValid()) {
			session()->setFlashdata('retornoError', $file->getErrorString());
			return redirect()->to(base_url('jobs/erp_map' . $idJob));
		}

		// Generar nombre seguro
		$newName = $idJob . '.' . $file->getExtension();

		// Ruta absoluta
		$path = FCPATH . 'images/erp/';

		// Mover archivo
		$file->move($path, $newName, true); // true = overwrite

		// Crear thumbnail si es photo
		$pathDb = 'images/erp/' . $newName;

		// Actualizar DB
		$arrParam = [
			"table" => "erp",
			"primaryKey" => "fk_id_job",
			"id" => $idJob,
			"column" => "evacuation_map",
			"value" => $pathDb
		];
		if ($this->generalModel->updateRecord($arrParam)) {
			session()->setFlashdata('retornoExito', 'Good job, you have uploaded the photo.');
		} else {
			session()->setFlashdata('retornoError', 'Ask for help.');
		}

		// Redirigir a la vista de regreso
		return redirect()->to(base_url('jobs/erp_map/' . $idJob));
	}

	/**
	 * Generate ERP Report in PDF
	 * @param int $idERP
	 * @since 20/11/2017
	 * @author BMOTTAG
	 * @review 21/04/2026 - new CI4 version
	 */
	public function generaERPPDF($idERP)
	{
		$pdf = new TCPDF();

		$pdf->SetCreator('VCI');
		$pdf->SetAuthor('VCI');
		$pdf->SetTitle('ERP Report');

		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// 👇 espacio para logo
		$pdf->SetMargins(10, 25, 10);
		$pdf->SetAutoPageBreak(TRUE, 10);

		$pdf->SetFont('dejavusans', '', 8);

		$data['info'] = $this->jobsModel->get_erp(["idERP" => $idERP]);

		$data['trainingWorkers'] = $this->jobsModel->get_erp_training_workers($data['info'][0]['fk_id_job']);

		$pdf->AddPage();

		// LOGO
		$logo = FCPATH . 'images/logo.png';

		if (is_file($logo)) {
			$pdf->Image($logo, 10, 8, 30);
		}

		// create some HTML content
		$html = '<p></p><p></p><p></p><p></p>
				<p><h1 align="center" style="color:#337ab7;">EMERGENCY RESPONSE PLAN</h1></p>
				<p></p><p></p><p></p><p></p><p></p>
				<p><h2 align="center" style="color:#337ab7;">Project code:<br>' . $data['info'][0]["job_description"] . '</h2></p>
				<p></p><p></p><p></p><p></p><p></p>
				<p><h2 align="center" style="color:#337ab7;">Facility Address:<br>' . $data['info'][0]["address"] . '</h2></p>
				<p></p><p></p><p></p><p></p><p></p>
				<p><h2 align="center" style="color:#337ab7;">DATE PREPARED:<br>' . $data['info'][0]["date_erp"] . '</h2></p>';

		// output the HTML content
		$pdf->writeHTML($html, true, false, true, false, '');

		// add a page
		$pdf->AddPage();

		// create some HTML content
		$html = '<p><h1 align="center" style="color:#337ab7;">EMERGENCY PERSONNEL NAMES AND PHONE NUMBERS</h1></p>
				<style>
				table {
					font-family: arial, sans-serif;
					border-collapse: collapse;
					width: 100%;
				}

				td, th {
					border: 1px solid #dddddd;
					text-align: left;
					padding: 8px;
				}
				</style>
			<table border="0" cellspacing="0" cellpadding="5">
	
				<tr>
					<th bgcolor="#337ab7" style="color:white;" width="30%"><strong>Site supervisor: </strong></th>
					<th width="30%">' . $data['info'][0]['responsible'] . '</th>
					<th bgcolor="#337ab7" style="color:white;" width="20%"><strong>Phone: </strong></th>
					<th width="20%">' . $data['info'][0]['phone_res'] . '</th>
				</tr>
				
				<tr>
					<th bgcolor="#337ab7" style="color:white;"><strong>Emergency coordinator: </strong></th>
					<th>' . $data['info'][0]['coordinator'] . '</th>
					<th bgcolor="#337ab7" style="color:white;"><strong>Phone: </strong></th>
					<th>' . $data['info'][0]['phone_co'] . '</th>
				</tr>
			
			</table>';

		// output the HTML content
		$pdf->writeHTML($html, true, false, true, false, '');

		$html = view("jobs/reporte_evacuation_pdf", $data);
		$pdf->writeHTML($html, true, false, true, false, '');

		// add a page
		$pdf->AddPage();
		$html = view("jobs/reporte_evacuation_procedures_pdf", $data);
		$pdf->writeHTML($html, true, false, true, false, '');

		// add a page
		$pdf->AddPage();
		$html = view("jobs/reporte_evacuation_procedures_fire_pdf", $data);
		$pdf->writeHTML($html, true, false, true, false, '');

		// add a page
		$pdf->AddPage();
		$html = view("jobs/reporte_evacuation_procedures_chemical_pdf", $data);
		$pdf->writeHTML($html, true, false, true, false, '');

		// add a page
		$pdf->AddPage();
		$html = view("jobs/reporte_evacuation_procedures_weather_pdf", $data);
		$pdf->writeHTML($html, true, false, true, false, '');

		// add a page
		$pdf->AddPage();
		$html = view("jobs/reporte_training_pdf", $data);
		$pdf->writeHTML($html, true, false, true, false, '');

		// reset pointer to the last page
		$pdf->lastPage();

		$filename = 'erp_' . $idERP . '.pdf';

		return $this->response
			->setHeader('Content-Type', 'application/pdf')
			->setBody($pdf->Output($filename, 'I'));
	}

	/**
	 * JSO
	 * @since 24/10/2017
	 * @author BMOTTAG
	 * @review 21/04/2026 - new CI4 version
	 */
	public function jso($idJob)
	{
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		//jso info
		$data['information'] = $this->jobsModel->get_jso(['idJob' => $idJob]);
		return $this->renderTopOnly('App\Modules\Jobs\Views\jso_list', $data);
	}

	/**
	 * Form JSO
	 * @since 3/1/2018
	 * @author BMOTTAG
	 * @review 21/04/2026 - new CI4 version
	 */
	public function add_jso($idJob, $idJobJso = 'x')
	{
		$data = [];
		$data['information'] = FALSE;
		$data['trainingWorkers'] = FALSE;
		$data['deshabilitar'] = '';

		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		$data['workersList'] = $this->generalModel->get_user(["state" => 1]); //workers list

		//si envio el id, entonces busco la informacion 
		if ($idJobJso != 'x') {
			//JSO info
			$data['information'] = $this->jobsModel->get_jso(["idJobJso" => $idJobJso]);

			if (!$data['information']) {
				throw new \Exception('ERROR!!! - You are in the wrong place.');
			}

			//workers list
			$data['infoWorkers'] = "";
			if ($data['information']) {
				$data['infoWorkers'] = $this->jobsModel->get_jso_workers(["idJobJso" => $idJobJso]);
			}
		}

		return $this->render('App\Modules\Jobs\Views\form_jso', $data);
	}

	/**
	 * Save JSO
	 * @since 3/1/2018
	 * @author BMOTTAG
	 * @review 21/04/2026 - new CI4 version
	 */
	public function save_jso()
	{
		$post = $this->request->getPost();

		$data = [];
		$data["idRecord"] = $post['hddIdJob'] ?? null;
		if ($idJSO = $this->jobsModel->addJSO($post)) {
			$data["idJSO"] = $idJSO;
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have saved the JSO!!');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Signature
	 * param $incidencesType: near_miss / incident / accident
	 * param $userType: supervisor / coordinator
	 * param $idFormulario: llave principal del formulario
     * @since 15/5/2017
     * @author BMOTTAG
	 * @review 21/04/2026 - new CI4 version
	 */
	public function save_signature()
	{
		$imageData = $this->request->getPost('image'); 
		$id = $this->request->getPost('otherValue'); 
		$incidencesType = $this->request->getPost('extraValue'); 
		$userType = $this->request->getPost('id'); 
		$fileName = $incidencesType . "_" . $userType . "_" . $id . ".png";
		$filePath = WRITEPATH . '../public/images/signature/incidences/' . $fileName;

		if(!$imageData){
			return redirect()->back()->with('error', 'No signature provided.');
		}

		$imageData = str_replace('data:image/png;base64,', '', $imageData);
		$imageData = str_replace(' ', '+', $imageData);

		if(!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0755, true);

		if(file_put_contents($filePath, base64_decode($imageData))){
			$this->incidencesModel->updateInfoSignature([
				"table" => "incidence_" . $incidencesType,
				"signatureColumn" => $userType. "_signature",
				"valSignature" => 'images/signature/incidences/' . $fileName,
				"fechaColumn" => "date_" . $userType,
				"idColumn" => "id_" . $incidencesType,
				"idValue" => $id
			]);
			return redirect()->back()->with('retornoExito', 'Signature saved successfully.');
		} else {
			return redirect()->back()->with('retornoError', 'Error saving signature.');
		}
	}

	/**
	 * Signature
	 * param $typo: supervisor / worker
	 * param $idJSO: llave principal del formulario
	 * param $idWorker: llave principal del trabajador
	 * @since 5/1/2018
	 * @author BMOTTAG
	 * @review 21/04/2026 - new CI4 version
	 */
	public function save_signature_jso()
	{
		$imageData = $this->request->getPost('image'); 
		$id = $this->request->getPost('extraValue'); 
		$typo = $this->request->getPost('otherValue'); 
		
		switch ($typo) {
			case "supervisor":
				$fileName = "supervisor_" . $id . ".png";
				$arrParam = [
					"table" => "job_jso",
					"primaryKey" => "id_job_jso",
					"id" => $id,
					"column" => "supervisor_signature",
					"value" => 'images/signature/jso/' . $fileName
				];
				break;

			case "manager":
				$fileName = "manager_" . $id . ".png";
				$arrParam = [
					"table" => "job_jso",
					"primaryKey" => "id_job_jso",
					"id" => $id,
					"column" => "manager_signature",
					"value" => 'images/signature/jso/' . $fileName
				];
				break;

			case "worker":
				$fileName = "worker_" . $id . ".png";
				$arrParam = [
					"table" => "job_jso_workers",
					"primaryKey" => "id_job_jso_worker",
					"id" => $id,
					"column" => "signature",
					"value" => 'images/signature/jso/' . $fileName
				];
				break;

			default:
				return $this->response->setJSON([
					"status" => "error",
					"message" => "Invalid user type"
				]);
		}
		$filePath = WRITEPATH . '../public/images/signature/jso/' . $fileName;

		if(!$imageData){
			return redirect()->back()->with('error', 'No signature provided.');
		}

		$imageData = str_replace('data:image/png;base64,', '', $imageData);
		$imageData = str_replace(' ', '+', $imageData);

		if(!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0755, true);

		if(file_put_contents($filePath, base64_decode($imageData))){
			$this->generalModel->updateRecord($arrParam);
			return redirect()->back()->with('retornoExito', 'Signature saved successfully.');
		} else {
			return redirect()->back()->with('retornoError', 'Error saving signature.');
		}
	}

	/**
	 * Cargo modal- formulario de captura workers para jso
	 * @since 5/1/2018
	 * @review 21/04/2026 - new CI4 version
	 */
	public function cargarModalWorker()
	{
		$data = [];
		$data['information'] = null;

		$idJobJso = $this->request->getPost("idJobJso");
		$idJobJsoWorker = $this->request->getPost("idJobJsoWorker");
		$data["idJobJso"] = $idJobJso;
		$data["idJobJsoWorker"] = $idJobJsoWorker;

		if (!empty($idJobJsoWorker) && $idJobJsoWorker !== 'x') {
			$data['information'] = $this->jobsModel->get_jso_workers(["idJobJsoWorker" => $data["idJobJsoWorker"]]);
		
			if ($data['information'] && is_array($data['information']) && isset($data['information'][0])) {
				$data["idJobJso"] = $data['information'][0]['fk_id_job_jso'];
			}
		}

		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Jobs\Views\modal_jso_worker', $data));
	}

	/**
	 * Save formularios
	 * @since 5/1/2018
	 * @author BMOTTAG
	 * @review 22/04/2026 - new CI4 version
	 */
	public function saveJSOWorker()
	{
		$post = $this->request->getPost();

		$idJobJso = $post['hddidJobJso'] ?? null;
		$idJobWorker = $post['hddidJobJsoWorker'] ?? null;

		//JSO info
		$infoJSO = $this->jobsModel->get_jso(["idJobJso" => $idJobJso]);

		$data = [];
		$data["idRecord"] = $infoJSO[0]['fk_id_job'];
		$data["idJSO"] = $idJobJso;
		$data["idRecordExternal"] = $idJobWorker;

		$msj = $idJobWorker 
			? "You have edited the information!!" 
			: "You have added a new worker!!";

		if ($this->jobsModel->saveJSOWorker($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', $msj);
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Generate JSO Report in PDF
	 * @param int $idJSO
	 * @since 7/1/2018
	 * @author BMOTTAG
	 * @review 22/04/2026 - new CI4 version
	 */
	public function generaJSOPDF($idJSO)
	{
		$pdf = new TCPDF();

		$pdf->SetCreator('VCI');
		$pdf->SetAuthor('VCI');
		$pdf->SetTitle('JSO Report');

		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// 👇 espacio para logo
		$pdf->SetMargins(10, 25, 10);
		$pdf->SetAutoPageBreak(TRUE, 10);

		$pdf->SetFont('dejavusans', '', 8);

		$data['info'] = $this->jobsModel->get_jso(["idJobJso" => $idJSO]);
		$data['workers'] = $this->jobsModel->get_jso_workers(["idJobJso" => $idJSO]);

		$pdf->AddPage();

		// LOGO
		$logo = FCPATH . 'images/logo.png';

		if (is_file($logo)) {
			$pdf->Image($logo, 10, 8, 30);
		}

		$html = view('jobs/reporte_jso', $data);
		if ($data['workers']) {
			$html .= view('jobs/reporte_jso_workers', $data);
		}

		$pdf->writeHTML($html, true, false, true, false, '');

		$pdf->lastPage();

		$filename = 'JSO_' . $data['info'][0]['job_description']  . $idJSO . '.pdf';

		return $this->response
			->setHeader('Content-Type', 'application/pdf')
			->setBody($pdf->Output($filename, 'I'));
	}







}