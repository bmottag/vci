<?php
namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{

	protected $protectFields = false;
	/**
	 * Add/Edit USER
	 * @since 8/11/2016
	 */

	public function saveEmployee($post)
	{
		$idUser = $post['hddId'] ?? null;

		$bankTime = ($post['employee_subcontractor'] ?? null) == 1 
			? 2 
			: ($post['bank_time'] ?? null);

		$data = [
			'first_name' => $post['firstName'],
			'last_name' => $post['lastName'],
			'log_user' => $post['user'],
			'social_insurance' => $post['insuranceNumber'],
			'health_number' => $post['healthNumber'],
			'birthdate' => $post['birth'],
			'movil' => $post['movilNumber'],
			'email' => $post['email'],
			'address' => $post['address'],
			'postal_code' => $post['postalCode'],
			'perfil' => $post['perfil'],
			'employee_rate' => $post['employee_rate'],
			'employee_type' => $post['employee_type'],
			'employee_subcontractor' => $post['employee_subcontractor'],
			'bank_time' => $bankTime
		];

		$builder = $this->db->table('user');

		if (empty($idUser)) {
			$data['state'] = 0;
			$data['password'] = password_hash('123456', PASSWORD_DEFAULT);

			return $builder->insert($data);
		} else {
			$data['state'] = $post['state'];

			return $builder->where('id_user', $idUser)
						->update($data);
		}
	}

	/**
	 * Add/Edit JOB
	 * @since 10/11/2016
	 */
	public function saveJob()
	{
		$this->load->library('logger');
		$idJob = $this->request->getPost('hddId');
		$jobCode = trim($this->security->xss_clean($this->request->getPost('jobCode')));
		$jobName = trim($this->security->xss_clean($this->request->getPost('jobName')));
		$data = array(
			'job_code' => $jobCode,
			'job_name' => $jobName,
			'job_description' => $jobCode . " " . $jobName,
			'fk_id_company' => $this->request->getPost('company'),
			'markup' => $this->request->getPost('markup'),
			'profit' => $this->request->getPost('profit'),
			'state' => $this->request->getPost('stateJob'),
			'notes' => addslashes($this->security->xss_clean($this->request->getPost('notes'))),
			'planning_message' => $this->request->getPost('planning_message'),
		);

		//revisar si es para adicionar o editar
		if ($idJob == '') {
			$data['created_by'] = $this->session->userdata("id");
			$query = $this->db->insert('param_jobs', $data);
			$idJob = $this->db->insert_id();

			$log['old'] = null;
			$log['new'] = json_encode($data);

			$this->logger
				->user($this->session->userdata("id")) //;//Set UserID, who created this  Action ->user($this->session->userdata("id"))
				->type('job_code') //Entry type like, Post, Page, Entry
				->id($idJob) //Entry ID
				->token('insert') //Token identify Action
				->comment(json_encode($log))
				->log(); //Add Database Entry
		} else {
			$arrParam = array(
				"table" => "param_jobs",
				"order" => "id_job",
				"column" => "id_job",
				"id" => $idJob
			);
			$log['old'] = $this->general_model->get_basic_search($arrParam);
			$log['new'] = json_encode($data);

			$data['updated_by'] = $this->session->userdata("id");
			$this->db->where('id_job', $idJob);
			$query = $this->db->update('param_jobs', $data);

			$this->logger
				->user($this->session->userdata("id")) //$this->session->userdata("id");//Set UserID, who created this  Action
				->type('job_code') //Entry type like, Post, Page, Entry
				->id($idJob) //Entry ID
				->token('update') //Token identify Action
				->comment(json_encode($log))
				->log(); //Add Database Entry
		}

		if ($query) {
			return $idJob;
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit HAZARD
	 * @since 11/12/2016
	 */
	public function saveHazard()
	{
		$idHazard = $this->request->getPost('hddId');

		$data = array(
			'fk_id_hazard_activity' => $this->request->getPost('activity'),
			'hazard_description' => $this->request->getPost('hazardName'),
			'solution' => $this->request->getPost('solution'),
			'fk_id_priority' => $this->request->getPost('priority')
		);

		//revisar si es para adicionar o editar
		if ($idHazard == '') {
			$query = $this->db->insert('param_hazard', $data);
			$idHazard = $this->db->insert_id();
		} else {
			$this->db->where('id_hazard', $idHazard);
			$query = $this->db->update('param_hazard', $data);
		}
		if ($query) {
			return $idHazard;
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit COMPANY
	 * @since 13/12/2016
	 */
	public function saveCompany()
	{
		$idCompany = $this->request->getPost('hddId');

		$data = array(
			'company_name' => $this->request->getPost('company'),
			'contact' => $this->request->getPost('contact'),
			'movil_number' => $this->request->getPost('movilNumber'),
			'email' => $this->request->getPost('email'),
			'does_hauling' => $this->request->getPost('does_hauling')
		);

		//revisar si es para adicionar o editar
		if ($idCompany == '') {
			$query = $this->db->insert('param_company', $data);
			$idCompany = $this->db->insert_id();
		} else {
			$this->db->where('id_company', $idCompany);
			$query = $this->db->update('param_company', $data);
		}
		if ($query) {
			return $idCompany;
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit MATERIAL
	 * @since 13/12/2016
	 */
	public function saveMaterial()
	{
		$idMaterial = $this->request->getPost('hddId');

		$data = array(
			'material' => $this->request->getPost('material'),
			'material_price' => $this->request->getPost('unit_price')
		);

		//revisar si es para adicionar o editar
		if ($idMaterial == '') {
			$query = $this->db->insert('param_material_type', $data);
			$idMaterial = $this->db->insert_id();
		} else {
			$this->db->where('id_material', $idMaterial);
			$query = $this->db->update('param_material_type', $data);
		}
		if ($query) {
			return $idMaterial;
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit Shop Parts
	 * @since 30/10/2023
	 */
	public function saveShopParts()
	{
		$idMaterial = $this->request->getPost('hddId');
		$idShop = $this->request->getPost('id_shop');

		if ($idShop == "") {
			$data = array(
				'shop_name' => addslashes($this->security->xss_clean($this->request->getPost('shop_name'))),
				'shop_contact' => addslashes($this->security->xss_clean($this->request->getPost('shop_contact'))),
				'shop_address' => addslashes($this->security->xss_clean($this->request->getPost('shop_address'))),
				'mobile_number' => addslashes($this->security->xss_clean($this->request->getPost('mobile_number'))),
				'shop_email' => addslashes($this->security->xss_clean($this->request->getPost('shop_email')))
			);

			$query = $this->db->insert('param_shop', $data);
			$idShop = $this->db->insert_id();
		}

		$data = array(
			'fk_id_material' => $idMaterial,
			'fk_id_shop' => $idShop,
			'date' => date('Y-m-d'),
		);

		$query = $this->db->insert('material_shop', $data);
		$idMaterial = $this->db->insert_id();

		if ($query) {
			return $idMaterial;
		} else {
			return false;
		}
	}

	public function get_material_with_shop()
	{
		$this->db->select('P.*,
						(SELECT GROUP_CONCAT("<b>",V.shop_name,"</b> ",V.shop_contact," - Email: ",V.shop_email," - Address: ",V.shop_address," - Mobile: ", V.mobile_number SEPARATOR "<br>") 
						FROM material_shop E
						JOIN param_shop V ON V.id_shop = E.fk_id_shop
 						WHERE E.fk_id_material = P.id_material
 						GROUP BY P.id_material) AS shops');
		$query = $this->db->get('param_material_type P');

		if ($query->num_rows() > 0) {
			return $query->result_array();
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit vehicle
	 * @since 15/12/2016
	 * @review 27/12/2016
	 */
	public function saveVehicle($pass)
	{
		$idVehicle = $this->request->getPost('hddId');

		$data = array(
			'fk_id_company' => $this->request->getPost('company'),
			'type_level_1' => $this->request->getPost('type1'),
			'type_level_2' => $this->request->getPost('type2'),
			'make' => $this->request->getPost('make'),
			'model' => $this->request->getPost('model'),
			'manufacturer_date' => $this->request->getPost('manufacturer'),
			'description' => $this->request->getPost('description'),
			'unit_number' => $this->request->getPost('unitNumber'),
			'vin_number' => $this->request->getPost('vinNumber'),
			'state' => $this->request->getPost('state'),
			'hours' => $this->request->getPost('hours')
		);

		//revisar si es para adicionar o editar
		if ($idVehicle == '') {
			$query = $this->db->insert('param_vehicle', $data);
			$idVehicle = $this->db->insert_id();

			//actualizo la url del codigo QR
			$path = $idVehicle . $pass;
			$rutaQRcode = "images/vehicle/" . $idVehicle . "_qr_code.png";

			//actualizo campo con el path encriptado
			$sql = "UPDATE param_vehicle SET encryption = '$path',qr_code = '$rutaQRcode'  WHERE id_vehicle = $idVehicle";
			$query = $this->db->query($sql);
		} else {
			$this->db->where('id_vehicle', $idVehicle);
			$query = $this->db->update('param_vehicle', $data);
		}
		if ($query) {
			return $idVehicle;
		} else {
			return false;
		}
	}

	/**
	 * Get vehicle list
	 * Param int $companyType -> 1: VCI; 2: Subcontractor
	 * @since 15/12/2016
	 * @review 26/2/2017
	 */
	public function get_vehicle_list($companyType)
	{
		$this->db->select();
		$this->db->join('param_company C', 'C.id_company = A.fk_id_company', 'INNER');
		$this->db->join('param_vehicle_type_2 T', 'T.id_type_2 = A.type_level_2', 'INNER');
		$this->db->where('C.company_type', $companyType);

		$this->db->order_by('C.id_company, A.unit_number', 'asc');
		$query = $this->db->get('param_vehicle A');

		if ($query->num_rows() > 0) {
			return $query->result_array();
		} else {
			return false;
		}
	}

	/**
	 * Get vehicle list
	 * Param int $companyType -> 1: VCI; 2: Subcontractor
	 * Param int $vehicleType -> 1: Pickup; 2: Construction Equipment; 3: Trucks; 4: Special Equipment; 99: Otros
	 * @since 5/5/2017
	 */
	public function get_vehicle_info_by($arrData)
	{
		$this->db->select();
		$this->db->join('param_company C', 'C.id_company = A.fk_id_company', 'INNER');
		$this->db->join('param_vehicle_type_2 T', 'T.id_type_2 = A.type_level_2', 'INNER');

		if (array_key_exists("companyType", $arrData)) {
			$this->db->where('C.company_type', $arrData["companyType"]);

			//si es de VCI entonces filtrar por tipo de inspeccion de lo contrario no se hace el filtro
			if ($arrData["companyType"] == 1) {
				if (array_key_exists("vehicleType", $arrData)) {
					$this->db->where('T.inspection_type', $arrData["vehicleType"]);
				}
			}
		}

		if (array_key_exists("idVehicle", $arrData)) {
			$this->db->where('A.id_vehicle', $arrData["idVehicle"]);
		}
		if (array_key_exists("vehicleState", $arrData)) {
			$this->db->where('A.state', $arrData["vehicleState"]);
		}

		$this->db->order_by('T.inspection_type, C.id_company, A.unit_number', 'asc');
		$query = $this->db->get('param_vehicle A');

		if ($query->num_rows() > 0) {
			return $query->result_array();
		} else {
			return false;
		}
	}

	/**
	 * Reset user´s password
	 * @author BMOTTAG
	 * @since  11/1/2017
	 */
	public function resetEmployeePassword($idUser)
	{
		$passwd = '123456';
		$passwd = md5($passwd);

		$data = array(
			'password' => $passwd,
			'state' => 0
		);

		$this->db->where('id_user', $idUser);
		$query = $this->db->update('user', $data);

		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Add vehicle next oil change
	 * @since 17/1/2017
	 */
	public function saveVehicleNextOilChange($idVehicle, $state)
	{
		$idUser = $this->session->userdata("id");

		$data = array(
			'fk_id_vehicle' => $idVehicle,
			'fk_id_user' => $idUser,
			'current_hours' => $this->request->getPost('hours'),
			'next_oil_change' => $this->request->getPost('oilChange'),
			'state' => $state,
			'current_hours_2' => $this->request->getPost('hours2'),
			'next_oil_change_2' => $this->request->getPost('oilChange2'),
			'current_hours_3' => $this->request->getPost('hours3'),
			'next_oil_change_3' => $this->request->getPost('oilChange3')
		);

		$query = $this->db->insert('vehicle_oil_change', $data);
		$idVehicleNextOilChange = $this->db->insert_id();

		$fecha = date("Y-m-d G:i:s");

		//actualizo fecha del registo
		$sql = "UPDATE vehicle_oil_change SET date_issue = '$fecha' WHERE id_oil_change=$idVehicleNextOilChange";
		$query = $this->db->query($sql);

		if ($query) {

			$data = array(
				'hours' => $this->request->getPost('hours'),
				'oil_change' => $this->request->getPost('oilChange'),
				'hours_2' => $this->request->getPost('hours2'),
				'oil_change_2' => $this->request->getPost('oilChange2'),
				'hours_3' => $this->request->getPost('hours3'),
				'oil_change_3' => $this->request->getPost('oilChange3')
			);

			$this->db->where('id_vehicle', $idVehicle);
			$query = $this->db->update('param_vehicle', $data);

			if ($query) {
				return true;
			} else {
				//se debe borrar el registro en la tabla vehicle_oil_change
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit EMPLOYEE TYPE
	 * @since 4/2/2017
	 */
	public function saveEmployeeType()
	{
		$idEmployeeType = $this->request->getPost('hddId');

		$data = array(
			'employee_type' => $this->request->getPost('employeeType'),
			'employee_type_unit_price' => $this->request->getPost('unit_price')
		);

		//revisar si es para adicionar o editar
		if ($idEmployeeType == '') {
			$query = $this->db->insert('param_employee_type', $data);
			$idEmployeeType = $this->db->insert_id();
		} else {
			$this->db->where('id_employee_type', $idEmployeeType);
			$query = $this->db->update('param_employee_type', $data);
		}
		if ($query) {
			return $idEmployeeType;
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit HAZARD ACTIVITY
	 * @since 5/2/2017
	 */
	public function saveHazardActivity()
	{
		$idHazardActivity = $this->request->getPost('hddId');

		$data = array(
			'hazard_activity' => $this->request->getPost('hazardActivity')
		);

		//revisar si es para adicionar o editar
		if ($idHazardActivity == '') {
			$query = $this->db->insert('param_hazard_activity', $data);
			$idHazardActivity = $this->db->insert_id();
		} else {
			$this->db->where('id_hazard_activity', $idHazardActivity);
			$query = $this->db->update('param_hazard_activity', $data);
		}
		if ($query) {
			return $idHazardActivity;
		} else {
			return false;
		}
	}

	/**
	 * Get hazard list
	 * @since 5/2/2017
	 */
	public function get_hazard_list()
	{
		$this->db->select();
		$this->db->join('param_hazard_activity A', 'A.id_hazard_activity = H.fk_id_hazard_activity', 'INNER');
		$this->db->join('param_hazard_priority P', 'P.id_priority = H.fk_id_priority', 'INNER');
		$this->db->order_by('A.hazard_activity, H.hazard_description', 'asc');
		$query = $this->db->get('param_hazard H');

		if ($query->num_rows() > 0) {
			return $query->result_array();
		} else {
			return false;
		}
	}

	/**
	 * Update user´s password
	 * @author BMOTTAG
	 * @since  8/11/2016
	 */
	public function updatePassword($idUser, $plainPassword)
	{
		$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

		return $this->db->table('user')
				->where('id_user', $idUser)
				->update(['password' => $hashedPassword]);
	}

	/**
	 * Update jobs state
	 * @since 12/1/2019
	 */
	public function updateJobsState($state)
	{
		$this->load->library('logger');

		//if it comes from the active view, then inactive everything
		//else do nothing and continue with the activation
		if ($state == 1) {
			//update all states to inactive
			$data['state'] = 2;
			$data['updated_by'] = $this->session->userdata("id");
			$query = $this->db->update('param_jobs', $data);

			$sql = "SELECT id_job, state FROM param_jobs";

			$queryJob = $this->db->query($sql);
			$jobs = $queryJob->result_array();

			foreach ($jobs as $key => $job) {
				$log['old'] = json_encode($job['state']);
				$log['new'] = json_encode($data);

				$this->logger
					->user($this->session->userdata("id")) //$this->session->userdata("id");//Set UserID, who created this  Action
					->type('job_code_status') //Entry type like, Post, Page, Entry
					->id($job['id_job']) //Entry ID
					->token('update') //Token identify Action
					->comment(json_encode($log))
					->log(); //Add Database Entry
			}
		}

		//update states
		$query = 1;
		if ($jobs = $this->request->getPost('job')) {
			$tot = count($jobs);
			for ($i = 0; $i < $tot; $i++) {
				$data['state'] = 1;
				$data['updated_by'] = $this->session->userdata("id");
				$this->db->where('id_job', $jobs[$i]);
				$query = $this->db->update('param_jobs', $data);

				$sql = "SELECT id_job, state FROM param_jobs where id_job = " . $jobs[$i];

				$queryJob = $this->db->query($sql);
				$job = $queryJob->result_array()[0];

				$log['old'] = json_encode($job['state']);
				$log['new'] = json_encode($data);

				$this->logger
					->user($this->session->userdata("id")) //$this->session->userdata("id");//Set UserID, who created this  Action
					->type('job_code_status') //Entry type like, Post, Page, Entry
					->id($jobs[$i]) //Entry ID
					->token('update') //Token identify Action
					->comment(json_encode($log))
					->log(); //Add Database Entry
			}
		}
		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit STOCK
	 * @since 17/3/2020
	 */
	public function saveStock()
	{
		$idStock = $this->request->getPost('hddId');

		$data = array(
			'stock_description' => $this->request->getPost('stockDescription'),
			'stock_price' => $this->request->getPost('price'),
			'quantity' => $this->request->getPost('quantity')
		);

		//revisar si es para adicionar o editar
		if ($idStock == '') {
			$query = $this->db->insert('stock', $data);
			$idStock = $this->db->insert_id();
		} else {
			$this->db->where('id_stock', $idStock);
			$query = $this->db->update('stock', $data);
		}
		if ($query) {
			return $idStock;
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit CERTIFICATE
	 * @since 14/1/2022
	 */
	public function saveCertificate($post)
	{
		$idCertificate = $post['hddId'] ?? null;

		$data = [
			'certificate' => $post['certificate'] ?? null,
			'certificate_description' => $post['description'] ?? null
		];

		$builder = $this->db->table('param_certificates');

		if (empty($idCertificate)) {
			// Insertar nuevo certificado
			return $builder->insert($data);
		} else {
			// Actualizar certificado existente
			return $builder->where('id_certificate', $idCertificate)
						->update($data);
		}
	}

	/**
	 * Add/Edit CERTIFICATE to Employee
	 * @since 15/1/2022
	 */
	public function saveEmployeeCertificate($post)
	{
		$idEmployeeCertificate = $post['hddidEmployeeCertificate'] ?? null;

		$builder = $this->db->table('user_certificates'); // 🔥 SIN espacio

		if (empty($idEmployeeCertificate)) {

			// INSERT
			$expire = $post['expire'] ?? null;
			$date_through = ($expire == 2) ? null : ($post['dateThrough'] ?? null);

			$data = [
				'expires' => $expire,
				'date_through' => $date_through,
				'fk_id_user' => $post['hddidEmployee'],
				'fk_id_certificate' => $post['certificate'],
				'alerts_sent' => 0
			];

			return $builder->insert($data);

		} else {

			// UPDATE
			$expire = $post['expiresUpdate'] ?? null;
			$date_through = ($expire == 2) ? null : ($post['dateThroughUpdate'] ?? null);

			$data = [
				'expires' => $expire,
				'date_through' => $date_through
			];

			return $builder->where('id_user_certificate', $idEmployeeCertificate)
						->update($data);
		}
	}

	/**
	 * Add/Edit NOTIFICATIONS ACCESS SETTINGS
	 * @since 23/01/2022
	 * @REVIEW 22/12/2022
	 */
	public function saveNotification()
	{
		$smsTo = ($this->request->getPost('smsTo')) ? json_encode($this->request->getPost('smsTo')) : null;
		$emailTo = ($this->request->getPost('emailTo')) ? json_encode($this->request->getPost('emailTo')) : null;

		$idNotificationAccess = $this->request->getPost('hddId');

		$data = array(
			'fk_id_user_email' => $emailTo,
			'fk_id_user_sms' => $smsTo
		);

		//revisar si es para adicionar o editar
		if ($idNotificationAccess == '') {
			$data['fk_id_notification'] = $this->request->getPost('notification');
			$query = $this->db->insert('notifications_access', $data);
		} else {
			$this->db->where('id_notification_access', $idNotificationAccess);
			$query = $this->db->update('notifications_access', $data);
		}
		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update employee rate
	 * @since 16/2/2022
	 */
	public function updateEmployeeRate()
	{
		//update states
		$query = 1;

		$employee = $this->request->getPost('form');
		if ($employee) {
			$tot = count($employee['id']);

			for ($i = 0; $i < $tot; $i++) {
				$bankTime = $employee['employee_subcontractor'][$i] == 1 ? 2 : $employee['bank_time'][$i];

				$data = array(
					'employee_rate' => $employee['employee_rate'][$i],
					'employee_type' => $employee['type'][$i],
					'employee_subcontractor' => $employee['employee_subcontractor'][$i],
					'bank_time' => $bankTime
				);
				$this->db->where('id_user', $employee['id'][$i]);
				$query = $this->db->update('user', $data);
			}
		}

		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Add/Edit Attachment
	 * @since 23/06/2023
	 */
	public function saveAttachment()
	{
		$idAttachment = $this->request->getPost('hddId');

		$data = array(
			'attachment_number' => $this->request->getPost('attachment_number'),
			'attachment_description' => addslashes($this->security->xss_clean($this->request->getPost('attachment_description')))
		);

		//revisar si es para adicionar o editar
		if ($idAttachment == '') {
			$query = $this->db->insert('param_attachments', $data);
			$idAttachment = $this->db->insert_id();
		} else {
			$this->db->where('id_attachment', $idAttachment);
			$query = $this->db->update('param_attachments', $data);
		}
		if ($query) {
			return $idAttachment;
		} else {
			return false;
		}
	}

	/**
	 * Attachments list
	 * @since 23/6/2023
	 */
	public function get_attachments($arrDatos)
	{
		$this->db->select('P.*, S.*, 
						(SELECT GROUP_CONCAT(V.unit_number, " -----> ", V.description SEPARATOR "<br>") 
						FROM param_attachments_equipment A 
						JOIN param_vehicle V ON V.id_vehicle = A.fk_id_equipment 
						WHERE A.fk_id_attachment = P.id_attachment 
						GROUP BY P.id_attachment) AS equipments');
		$this->db->join('param_status S', 'S.status_slug = P.attachment_status', 'INNER');
		if (array_key_exists("idAttachment", $arrDatos)) {
			$this->db->where('id_attachment', $arrDatos["idAttachment"]);
		}
		if (array_key_exists("status", $arrDatos)) {
			$this->db->where('attachment_status', $arrDatos["status"]);
		}
		$this->db->order_by('attachment_number', 'asc');
		$query = $this->db->get('param_attachments P');

		if ($query->num_rows() > 0) {
			return $query->result_array();
		} else {
			return false;
		}
	}

	/**
	 * Add Equipment to Attachements
	 * @since 26/06/2023
	 */
	public function add_equipment_attachement($idAttachment)
	{
		//delete Attachements 
		$this->db->delete('param_attachments_equipment', array('fk_id_attachment' => $idAttachment));

		$query = 1;
		if ($equipment = $this->request->getPost('equipment')) {
			$tot = count($equipment);
			for ($i = 0; $i < $tot; $i++) {
				$data = array(
					'fk_id_attachment' => $idAttachment,
					'fk_id_equipment' => $equipment[$i]
				);
				$query = $this->db->insert('param_attachments_equipment', $data);
			}
		}
		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Attachments list
	 * @since 26/6/2023
	 */
	public function get_attachments_equipment($arrDatos)
	{
		if (array_key_exists("relation", $arrDatos)) {
			$this->db->select('P.fk_id_equipment');
		} else {
			$this->db->select('P.*, T.inspection_type');
			$this->db->join('param_vehicle V', 'V.id_vehicle = P.fk_id_equipment', 'INNER');
			$this->db->join('param_vehicle_type_2 T', 'T.id_type_2 = V.type_level_2', 'INNER');
		}
		if (array_key_exists("idAttachment", $arrDatos)) {
			$this->db->where('fk_id_attachment', $arrDatos["idAttachment"]);
		}
		$query = $this->db->get('param_attachments_equipment P');

		if ($query->num_rows() > 0) {
			return $query->result_array();
		} else {
			return false;
		}
	}

	/**
	 * Informacion del foreman
	 * @since 13/01/2025
	 */
	public function save_foreman($idJob)
	{
		$idForeman = $this->request->getPost('hddIdForeman');

		$data = array(
			'foreman_name' => $this->request->getPost('foreman'),
			'foreman_movil_number' => $this->request->getPost('movilNumber'),
			'foreman_email' => $this->request->getPost('email')
		);

		//revisar si es para adicionar o editar
		if ($idForeman == '') {
			$data['fk_id_job'] = $idJob;
			$data['fk_id_param_company'] = $this->request->getPost('company');
			$query = $this->db->insert('param_company_foreman', $data);
		} else {
			$this->db->where('id_company_foreman', $idForeman);
			$query = $this->db->update('param_company_foreman', $data);
		}
		if ($query) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Job Log
	 * @since 20/02/2024
	 */
	public function get_job_log($arrData)
	{
		$this->db->select("L.*, CONCAT(first_name, ' ', last_name) name, j.job_description");
		$this->db->join('user U', 'U.id_user = L.created_by', 'INNER');
		$this->db->join('param_jobs j', 'L.type_id = j.id_job', 'LEFT');
		$this->db->join('param_company C', 'C.id_company = j.fk_id_company', 'LEFT');

		$parameters = array('job_code_status', 'job_code');
		$this->db->where_in('L.type', $parameters);
		
		if (array_key_exists("jobId", $arrData) && $arrData["jobId"] != '' && $arrData["jobId"] != 0) {
			$this->db->where('j.id_job', $arrData["jobId"]);
		}

		if (array_key_exists("userId", $arrData) && $arrData["userId"] != '' && $arrData["userId"] != 0) {
			$this->db->where('L.created_by', $arrData["userId"]);
		}

		if (array_key_exists("from", $arrData) && $arrData["from"] != '') {
			$this->db->where('L.created_on >=', $arrData["from"]);
		}

		if (array_key_exists("to", $arrData) && $arrData["to"] != '' && $arrData["from"] != '') {
			$this->db->where('L.created_on <=', $arrData["to"]);
		}
		$this->db->order_by('L.id', 'asc');
		$query = $this->db->get('logger L');

		if ($query->num_rows() > 0) {
			return $query->result_array();
		} else {
			return false;
		}
	}
}
