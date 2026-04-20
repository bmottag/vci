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
		$pdf->SetTitle('JOB HAZARDS ANALYSIS report');

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




}
