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
		$data['information'] = FALSE;

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
	 * Save hazards
	 * @since 27/11/2017
	 * @author BMOTTAG
	 * @review 14/04/2026 - new CI4 version
	 */
	public function save_safety_hazards()
	{
		$data = [];
		$post = $this->request->getPost();
		$data["idJob"] =  $post['hddId'];
		if ($this->jobsModel->add_safety_hazard($post)) {
			$this->jobsModel->add_hazard_log($post);
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have added Hazards.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
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

}
