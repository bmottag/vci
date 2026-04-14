<?php
namespace App\Modules\Safety\Controllers;

use App\Controllers\BaseController;
use App\Modules\Safety\Models\SafetyModel;
use App\Models\GeneralModel;

class Safety extends BaseController
{
    protected $safetyModel;
    protected $generalModel;
	protected $helpers = ['form'];
    
    public function __construct()
    {
        $this->safetyModel   = new SafetyModel();
        $this->generalModel   = new GeneralModel();
    }

	/**
	 * Form Add Safety
     * @since 13/4/2021
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function add_safety($idJob, $idSafety = 'x')
	{
		$data = [];
		$data['information'] = FALSE;
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		$data['hazards'] = $this->generalModel->get_job_hazards($idJob);
		//si envio el idSafety, entonces busco la informacion 
		if ($idSafety != 'x') {
			$data['information'] = $this->safetyModel->get_safety_by_id($idSafety);//info safety
		}			
		return $this->render('App\Modules\Safety\Views\form_add_safety', $data);
	}

	/**
	 * Save safety
     * @since 14/4/2021
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function save_safety()
	{
		$post = $this->request->getPost();
		$data = [];
		if ($idSafety = $this->safetyModel->add_safety($post)) {
			$data["idSafety"] = $idSafety;
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have saved your FLHA record, do not forget to add Hazards, Workers and signatures.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Form Upload safety info
     * @since 15/4/2021
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function upload_info_safety($id = 'x')
	{
		$data = [];
		$data['information'] = FALSE;
		$data['safetyClose'] = FALSE;	
		if ($id != 'x') 
		{
			$data['information'] = $this->safetyModel->get_safety_by_id($id);//info safety
			$data['safetyHazard'] = $this->safetyModel->get_safety_hazard($id);//safety_hazard list
			//consultar si esta cerrado
			if($data['information'][0]['state'] == 2){
				$data['safetyClose'] = TRUE;
				return $this->render('App\Modules\Safety\Views\view_safety', $data);
			}
		}		
		return $this->render('App\Modules\Safety\Views\form_upload_info_safety', $data);
	}

	/**
	 * Form Add Hazards to FLHA
	 * Muestre lista de Hazards por trabajo y los que estan asignados al FLHA estan con check
     * @since 16/5/2019
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function add_hazards_flha($idJob, $idSafety)
	{
		if (empty($idJob) || empty($idSafety)) {
			throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid IDs');
		}

		$activityList = $this->safetyModel->get_activity_list_by_job($idJob);

		// 🔥 SOLO UNA QUERY
		$selected = array_column(
			$this->safetyModel->get_selected_hazards($idSafety),
			'fk_id_hazard'
		);

		if (!empty($activityList)) {
			foreach ($activityList as &$activity) {

				$hazards = $this->safetyModel
					->get_hazard_list_by_job($activity['id_hazard_activity'], $idJob);

				if (!empty($hazards)) {
					foreach ($hazards as &$hazard) {

						// ✅ ahora es solo memoria (rápido)
						$hazard['found'] = in_array($hazard['id_hazard'], $selected);
					}
				}

				$activity['hazards'] = $hazards;
			}
		}

		$data = [
			'activityList' => $activityList,
			'idJob' => $idJob,
			'idSafety' => $idSafety
		];

		return $this->render('App\Modules\Safety\Views\form_add_hazards_flha', $data);
	}

	/**
	 * Save hazards
     * @since 06/12/2016
	 * @review 10/12/2016
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function save_safety_hazards()
	{			
		$post = $this->request->getPost();
		$idSafety = $post['hddId'];
		$data = [];
		if ($this->safetyModel->add_safety_hazard($post)) {
			$data["idSafety"] = $idSafety;
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have added Hazards.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
    }	




}
