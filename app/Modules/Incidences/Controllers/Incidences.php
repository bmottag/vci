<?php
namespace App\Modules\Incidences\Controllers;

use App\Controllers\BaseController;
use App\Modules\Incidences\Models\IncidencesModel;
use App\Models\GeneralModel;

class Incidences extends BaseController
{
    protected $incidencesModel;
    protected $generalModel;
    
    public function __construct()
    {
        $this->incidencesModel   = new IncidencesModel();
        $this->generalModel   = new GeneralModel();
    }

	/**
	 * Near Miss list
     * @since 17/3/2017
     * @author BMOTTAG
	 * @review 08/04/2026 - new CI4 version
	 */
	public function near_miss($idJob)
	{
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		$data['nearMissInfo'] = $this->incidencesModel->get_near_miss_by_idUser(["jobId" => $idJob]);
		return $this->render('App\Modules\Incidences\Views\near_miss_list', $data);
	}

	/**
	 * Form Near Miss
     * @since 17/3/2017
     * @author BMOTTAG
	 * @review 08/04/2026 - new CI4 version
	 */
	public function add_near_miss($idJob, $id = 'x')
	{
		$data = [];
		$data['information'] = null;
		$data['deshabilitar'] = '';
			
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);

		//incident type list
		$arrParam = [
			"table" => "param_incident_type",
			"order" => "id_incident_type",
			"id" => "x"
		];
		$data['incidentType'] = $this->generalModel->get_basic_search($arrParam);

		//workers list
		$arrWorkers = [
			"table" => "user",
			"order" => "first_name, last_name",
			"column" => "state",
			"id" => 1
		];
		$data['workersList'] = $this->generalModel->get_basic_search($arrWorkers);

		$arrJob = [
			"table" => "param_jobs",
			"order" => "job_description",
			"column" => "state",
			"id" => 1
		];
		$data['jobs'] = $this->generalModel->get_basic_search($arrJob);

		//si envio el id, entonces busco la informacion 
		if ($id != 'x') 
		{	
			$data['information'] = $this->incidencesModel->get_near_miss_by_idUser(["idNearMiss" => $id]);
			if (!$data['information']) { 
				throw new \Exception('ERROR!!! - You are in the wrong place.');
			}

			//busco lista de personal involucrado, para el formulario de NEAR MISS (1)
			$arrIncident = [
				'idIncident' => $id,
				'form' => 1
			];
			$data['personsInvolved'] = $this->incidencesModel->get_persons_involved($arrIncident);
			}			

		return $this->render('App\Modules\Incidences\Views\form_near_miss', $data);
	}

	/**
	 * Save near miss
     * @since 28/3/2017
     * @author BMOTTAG
	 * @review 08/04/2026 - new CI4 version
	 */
	public function save_near_miss()
	{
		$post = $this->request->getPost();
		$idReport = $post['hddIdentificador'] ?? null;
		$data = [];
		$data["idJob"] = $post['jobName'] ?? null;
		if ($idNearmiss = $this->incidencesModel->add_near_miss($post)) {
			$data["idNearmiss"] = $idNearmiss;
			if ($idReport == '') {
				//$this->email_to($idNearmiss, 1);//si es un reporte nuevo envio correo
			}	
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', "You have saved the Near Miss Report, continue uploading the information.");
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

    /**
     * Safe Person Involved
     * @since 24/4/2021
     * @author BMOTTAG
	 * @review 08/04/2026 - new CI4 version
     */
    public function save_person_involved() 
	{
		$post = $this->request->getPost();
		$idIncident = $this->request->getPost('hddId');
		$formIdentifier = $this->request->getPost('hddFormIdentifier');
		$idJob = $this->request->getPost('hddIdJob');
		
		if($formIdentifier==1){
			$path = 'incidences/add_near_miss/' . $idJob . '/' . $idIncident;
		}else{
			$path = 'incidences/add_incident/' . $idJob . '/' . $idIncident;
		}

		if ($this->incidencesModel->savePersonInvolved($post)) {
			session()->setFlashdata('retornoExito', 'You have added a Person Involved.');
		} else {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}
		return redirect()->to(base_url($path));
    }

    /**
     * Delete personl involved
     * @since 24/4/2021
     * @author BMOTTAG
	 * @review 08/04/2026 - new CI4 version
     */
    public function deleteIncidentPersonInvolved($idPerson, $idIncident, $formIdentifier, $idJob) 
	{
		$arrParam = [
			"table" => "incidence_incident_person",
			"primaryKey" => "id_incident_person",
			"id" => $idPerson
		];
		if ($this->generalModel->deleteRecord($arrParam)) {
			session()->setFlashdata('retornoExito', 'You have deleted a Person Involved.');
		} else {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}
		$path = $formIdentifier==1 ? 'incidences/add_near_miss/' . $idJob . '/' . $idIncident : 'incidences/add_incident/' . $idJob . '/' . $idIncident;
		return redirect()->to(base_url($path));
    }

	/**
	 * Signature
	 * param $idPersonal: llave principal del formulario
     * @since 15/5/2017
     * @author BMOTTAG
	 * @review 13/04/2026 - new CI4 version
	 */
	public function save_signature_person_involved()
	{
		$imageData = $this->request->getPost('image'); 
		$idPersonal = $this->request->getPost('id'); 
		$fileName = 'personsInvolved_' . $idPersonal. '.png';
		$filePath = WRITEPATH . '../public/images/signature/safety/' . $fileName;

		if(!$imageData){
			return redirect()->back()->with('error', 'No signature provided.');
		}

		$imageData = str_replace('data:image/png;base64,', '', $imageData);
		$imageData = str_replace(' ', '+', $imageData);

		if(!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0755, true);

		if(file_put_contents($filePath, base64_decode($imageData))){
			$this->generalModel->updateRecord([
				"table" => "incidence_incident_person",
				"primaryKey" => "id_incident_person ",
				"id" => $idPersonal,
				"column" => "person_signature",
				"value" => 'images/signature/safety/' . $fileName
			]);
			return redirect()->back()->with('retornoExito', 'Signature saved successfully.');
		} else {
			return redirect()->back()->with('retornoError', 'Error saving signature.');
		}
	}

	/**
	 * Signature
	 * param $incidencesType: near_miss / incident / accident
	 * param $userType: supervisor / coordinator
	 * param $idFormulario: llave principal del formulario
     * @since 15/5/2017
     * @author BMOTTAG
	 * @review 13/04/2026 - new CI4 version
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
	 * Incident list
     * @since 15/5/2017
     * @author BMOTTAG
	 * @review 08/04/2026 - new CI4 version
	 */
	public function incident($idJob)
	{
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);
		$data['incidentInfo'] = $this->incidencesModel->get_incident_by(["jobId" => $idJob]);
		return $this->render('App\Modules\Incidences\Views\incident_list', $data);
	}

	/**
	 * Form Incident
     * @since 15/5/2017
     * @author BMOTTAG
	 * @review 13/04/2026 - new CI4 version
	 */
	public function add_incident($idJob, $id = 'x')
	{
		$data = [];
		$data['information'] = null;
		$data['deshabilitar'] = '';
			
		$data['jobInfo'] = $this->generalModel->get_job(['idJob' => $idJob]);

		//incident type list
		$arrParam = [
			"table" => "param_incident_type",
			"order" => "id_incident_type",
			"id" => "x"
		];
		$data['incidentType'] = $this->generalModel->get_basic_search($arrParam);

		//workers list
		$arrWorkers = [
			"table" => "user",
			"order" => "first_name, last_name",
			"column" => "state",
			"id" => 1
		];
		$data['workersList'] = $this->generalModel->get_basic_search($arrWorkers);

		$arrJob = [
			"table" => "param_jobs",
			"order" => "job_description",
			"column" => "state",
			"id" => 1
		];
		$data['jobs'] = $this->generalModel->get_basic_search($arrJob);
			
		//si envio el id, entonces busco la informacion 
		if($id != 'x')
		{			
			$data['information'] = $this->incidencesModel->get_incident_by(['idIncident' => $id]);
			if (!$data['information']) { 
				throw new \Exception('ERROR!!! - You are in the wrong place.');
			}

			//busco lista de personal involucrado, para el formulario de INCIDENT (2)
			$arrIncident= [
				'idIncident' => $id,
				'form' => 2
			];
			$data['personsInvolved'] = $this->incidencesModel->get_persons_involved($arrIncident);
		}			

		return $this->render('App\Modules\Incidences\Views\form_incident', $data);
	}

	/**
	 * Save incident
     * @since 15/5/2017
     * @author BMOTTAG
	 * @review 13/04/2026 - new CI4 version
	 */
	public function save_incident()
	{
		$post = $this->request->getPost();
		$idReport = $post['hddIdentificador'] ?? null;
		$data = [];
		$data["idJob"] = $post['jobName'] ?? null;
		if ($idRecord = $this->incidencesModel->add_incident($post)) {
			$data["idRecord"] = $idRecord;
			if ($idReport == '') {
				//$this->email_to($idNearmiss, 2);//si es un reporte nuevo envio correo
			}	
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have saved the Incident Report, continue uploading the information!!');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}




}