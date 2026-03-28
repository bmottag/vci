<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AdminModel;
use App\Models\GeneralModel;

class Admin extends BaseController
{
    protected $adminModel;
    protected $generalModel;
    protected $helpers = ['form'];
    
    public function __construct()
    {
        $this->adminModel   = new AdminModel();
        $this->generalModel   = new GeneralModel();
    }

	/**
	 * employee List
	 * @since 15/12/2016
	 * @author BMOTTAG
	 * @review 21/03/2026 - new CI4 version
	 */
	public function employee($state)
	{
		$data = [];
		$data['state'] = $state;
		if ($state == 1) {
			$arrParam = ["filtroState" => TRUE];
		} else {
			$arrParam = ["state" => $state];
		}

		// 1. Obtener usuarios
		$info = $this->generalModel->get_user($arrParam);

		// 2. Obtener TODOS los certificados (una sola consulta)
		$certificates = $this->generalModel->get_user_certificates([]);

		// 3. Agrupar certificados por usuario
		$groupedCertificates = [];
		if ($certificates) {
			foreach ($certificates as $cert) {
				$groupedCertificates[$cert['fk_id_user']][] = $cert;
			}
		}

		// 4. Asignar certificados a cada usuario
		if ($info) {
			foreach ($info as &$user) {
				$user['certificates'] = $groupedCertificates[$user['id_user']] ?? [];
			}
		}

		$data['info'] = $info;
		return $this->render('App\Modules\Admin\Views\employee', $data);
	}

	/**
	 * Cargo modal - formulario Employee
	 * @since 15/12/2016
	 * @review 21/03/2026 - new CI4 version
	 */
	public function cargarModalEmployee()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data['information'] = FALSE;
		$data["idEmployee"] = $this->request->getPost("idEmployee");

		$arrParam = array("filtro" => TRUE);
		$data['roles'] = $this->generalModel->get_roles($arrParam);

		if ($data["idEmployee"] != 'x') {
			$arrParam = array(
				"table" => "user",
				"order" => "id_user",
				"column" => "id_user",
				"id" => $data["idEmployee"]
			);
			$data['information'] = $this->generalModel->get_basic_search($arrParam);
		}

		return view('App\Modules\Admin\Views\employee_modal', $data);
	}

	/**
	 * Update Employee
	 * @since 15/12/2016
	 * @author BMOTTAG
	 * @review 21/03/2026 - new CI4 version
	 */
	public function save_employee()
	{
		$post = $this->request->getPost();
		$idUser = $post['hddId'] ?? null;

		$msj = $idUser ? "You have updated an Employee!!" : "You have added a new Employee!!";

		$log_user = $post['user'];
		$social_insurance = $post['insuranceNumber'];

		$result_user = false;
		$result_insurance = false;

		if (empty($idUser)) {
			$result_user = $this->generalModel->verifyUser([
				"column" => "log_user",
				"value" => $log_user
			]);
			$result_insurance = $this->generalModel->verifyUser([
				"column" => "social_insurance",
				"value" => $social_insurance
			]);
		}

		if ($result_user || $result_insurance) {

			$mensaje = $result_user && $result_insurance
				? "User and SIN already exist."
				: ($result_user ? "User already exists." : "Social Insurance Number already exists.");

			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> ' . $mensaje);

			return $this->response->setJSON([
				"result" => "error",
				"mensaje" => $mensaje
			]);

		} else {
			if ($this->adminModel->saveEmployee($post)) {
				session()->setFlashdata('retornoExito', $msj);

				// ⚡ IMPORTANTE: enviar 'state' para la redirección
				$state = $post['state'] ?? 1;

				return $this->response->setJSON([
					"result" => true,
					"mensaje" => $msj,
					"state" => $state
				]);
			} else {
				$error = "Error saving data";
				session()->setFlashdata('retornoError', '<strong>Error!!!</strong> ' . $error);

				return $this->response->setJSON([
					"result" => "error",
					"mensaje" => $error
				]);
			}
		}
	}

	/**
	 * Reset employee password
	 * Reset the password to '123456'
	 * And change the status to '0' to changue de password 
	 * @since 11/1/2017
	 * @author BMOTTAG
	 */
	public function resetPassword($idUser)
	{
		if ($this->adminModel->resetEmployeePassword($idUser)) {
			$this->session->set_flashdata('retornoExito', 'You have reset the Employee pasword to: 123456');
		} else {
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		redirect("/admin/employee/", 'refresh');
	}

	/**
	 * Material List
	 * @since 13/12/2016
	 * @author BMOTTAG
	 * @review 28/03/2026 - new CI4 version
	 */
	public function material()
	{
		$data['info'] = $this->adminModel->get_material_with_shop();
		return $this->render('App\Modules\Admin\Views\material', $data);
	}

	/**
	 * Cargo modal - formulario material type
	 * @since 13/12/2016
	 * @review 28/03/2026 - new CI4 version
	 */
	public function cargarModalMaterial()
	{
		$data = [];
		$data['information'] = null;

		$idMaterial = $this->request->getPost("idMaterial");
		$data["idMaterial"] = $idMaterial;

		if (!empty($idMaterial) && $idMaterial !== 'x') {
			$arrParam = array(
				"table" => "param_material_type",
				"order" => "id_material",
				"column" => "id_material",
				"id" => $idMaterial
			);
			$data['information'] = $this->generalModel->get_basic_search($arrParam);
		}

		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Admin\Views\material_modal', $data));
	}

	/**
	 * Update material
	 * @since 13/12/2016
	 * @author BMOTTAG
	 * @review 28/03/2026 - new CI4 version
	 */
	public function save_material()
	{
		$post = $this->request->getPost();

		$idMaterial = $post['hddId'] ?? null;
		$msj = $idMaterial 
			? "You have updated a Material Type!!" 
			: "You have added a new Material Type!!";

		$data = [];

		if ($this->adminModel->saveMaterial($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', $msj);
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Cargo modal - Shop
	 * @since 30/10/2023
	 */
	public function loadModalShop()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data["idMaterial"] = $this->request->getPost("idMaterial");

		$arrParam = array(
			"table" => "param_shop",
			"order" => "shop_name",
			"id" => "x"
		);
		$data['shopList'] = $this->generalModel->get_basic_search($arrParam);

		return view('App\Modules\Admin\Views\shop_modal', $data);
	}

	/**
	 * Save Shop Parts
	 * @since 30/10/2023
	 * @author BMOTTAG
	 */
	public function save_shop_materials()
	{
		header('Content-Type: application/json');
		$data = array();

		$idMaterial = $this->request->getPost('hddId');

		$msj = "You have added the Shop Information for Material!!";
		if ($idMaterial != '') {
			$msj = "You have updated the Shop Information for Material!!";
		}

		if ($idMaterial = $this->adminModel->saveShopParts()) {
			$data["result"] = true;
			$this->session->set_flashdata('retornoExito', $msj);
		} else {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		echo json_encode($data);
	}

	/**
	 * Company List
	 * @since 15/12/2016
	 * @author BMOTTAG
	 * @review 28/03/2026 - new CI4 version
	 */
	public function company()
	{
		//se filtra por company_type para que solo se pueda editar los subcontratistas
		$arrParam = array(
			"table" => "param_company",
			"order" => "id_company",
			"column" => "company_type",
			"id" => 2
		);
		$data['info'] = $this->generalModel->get_basic_search($arrParam);

		return $this->render('App\Modules\Admin\Views\company', $data);
	}

	/**
	 * Cargo modal - formulario company
	 * @since 15/12/2016
	 * @review 28/03/2026 - new CI4 version
	 */
	public function cargarModalCompany()
	{
		$data = [];
		$data['information'] = null;

		$idCompany = $this->request->getPost("idCompany");
		$data["idCompany"] = $idCompany;

		if (!empty($idCompany) && $idCompany !== 'x') {
			$arrParam = array(
				"table" => "param_company",
				"order" => "id_company",
				"column" => "id_company",
				"id" => $idCompany
			);
			$data['information'] = $this->generalModel->get_basic_search($arrParam);
		}

		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Admin\Views\company_modal', $data));
	}

	/**
	 * Update company
	 * @since 15/12/2016
	 * @author BMOTTAG
	 * @review 28/03/2026 - new CI4 version
	 */
	public function save_company()
	{
		$post = $this->request->getPost();

		$idCompany = $post['hddId'] ?? null;
		$msj = $idCompany 
			? "You have updated a Company!!" 
			: "You have added a new Company!!";

		$data = [];

		if ($this->adminModel->saveCompany($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', $msj);
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * hazard List
	 * @since 15/12/2016
	 * @author BMOTTAG
	 * @review 28/03/2026 - new CI4 version
	 */
	public function hazard()
	{
		$data['info'] = $this->adminModel->get_hazard_list();
		return $this->render('App\Modules\Admin\Views\hazard', $data);
	}

	/**
	 * Cargo modal - formulario hazard
	 * @since 15/12/2016
	 * @review 28/03/2026 - new CI4 version
	 */
	public function cargarModalHazard()
	{
		$data = [];
		$data['information'] = null;

		$idHazard = $this->request->getPost("idHazard");
		$data["idHazard"] = $idHazard;

		$arrParam = array(
			"table" => "param_hazard_activity",
			"order" => "hazard_activity",
			"id" => "x"
		);
		$data['activityList'] = $this->generalModel->get_basic_search($arrParam);

		$arrParam = array(
			"table" => "param_hazard_priority",
			"order" => "priority_description",
			"id" => "x"
		);
		$data['priorityList'] = $this->generalModel->get_basic_search($arrParam);

		if (!empty($idHazard) && $idHazard !== 'x') {
			$arrParam = array(
				"table" => "param_hazard",
				"order" => "id_hazard",
				"column" => "id_hazard",
				"id" => $idHazard
			);
			$data['information'] = $this->generalModel->get_basic_search($arrParam);
		}

		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Admin\Views\hazard_modal', $data));
	}

	/**
	 * Update hazard
	 * @since 15/12/2016
	 * @author BMOTTAG
	 * @review 28/03/2026 - new CI4 version
	 */
	public function save_hazard()
	{
		$post = $this->request->getPost();

		$id = $post['hddId'] ?? null;
		$msj = $id 
			? "You have updated a Hazard!!" 
			: "You have added a new Hazard!!";

		$data = [];

		if ($this->adminModel->saveHazard($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', $msj);
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * job List
	 * @since 15/12/2016
	 * @author BMOTTAG
	 */
	public function job($state)
	{
		if ($state == 'log') {

			//job list
			$this->load->model("generalModel");
			$arrParam = array(
				"table" => "param_jobs",
				"order" => "job_description",
				"column" => "state",
				"id" => 1
			);
			$data['jobList'] = $this->generalModel->get_basic_search($arrParam); //job list

			$arrParam = array(
				"table" => "user",
				"order" => "id_user",
				"column" => "id_user",
				"id" => "x"
			);
			$data['user'] = $this->generalModel->get_basic_search($arrParam); //job list

			if ($this->request->getPost('jobName') || $this->request->getPost('user') || $this->request->getPost('from')) {

				$data['jobName'] =  $this->request->getPost('jobName');
				$data['user'] =  $this->request->getPost('user');
				$data['from'] =  $this->request->getPost('from');
				$data['to'] =  $this->request->getPost('to');

				//le sumo un dia al dia final para que ingrese ese dia en la consulta
				if ($data['to']) {
					$to = date('Y-m-d', strtotime('+1 day ', strtotime(formatear_fecha($data['to']))));
				} else {
					$to = "";
				}
				if ($data['from']) {
					$from = formatear_fecha($data['from']);
				} else {
					$from = "";
				}

				$arrParam = array(
					"jobId" => $this->request->getPost('jobName'),
					"userId" => $this->request->getPost('user'),
					"from" => $from,
					"to" => $to
				);

				//informacion Work Order
				$data['workOrderInfo'] = $this->adminModel->get_job_log($arrParam);

				$data["view"] = "log_list";
				$this->load->view("layout_calendar", $data);
			} else {
				$data["view"] = 'job_log';
				$this->load->view("layout", $data);
			}
		} else {
			$data['state'] = $state;

			$arrParam['state'] = $state;
			$data['info'] = $this->generalModel->get_job($arrParam);
			$data['dashboardURL'] = $this->session->userdata("dashboardURL");
			$data["view"] = 'job';
			$this->load->view("layout_calendar", $data);
		}
	}

	/**
	 * Cargo modal - formulario job
	 * @since 15/12/2016
	 */
	public function cargarModalJob()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data['information'] = FALSE;
		$data["idJob"] = $this->request->getPost("idJob");

		//company list
		$arrParam = array(
			"table" => "param_company",
			"order" => "company_name",
			"column" => "company_type",
			"id" => 2
		);
		$data['companyList'] = $this->generalModel->get_basic_search($arrParam);

		if ($data["idJob"] != 'x') {
			$arrParam['idJob'] = $data["idJob"];
			$data['information'] = $this->generalModel->get_job($arrParam);
		}

		return view('App\Modules\Admin\Views\job_modal', $data);
	}

	/**
	 * Update job
	 * @since 15/12/2016
	 * @author BMOTTAG
	 */
	public function save_job()
	{
		header('Content-Type: application/json');
		$data = array();

		$idJob = $this->request->getPost('hddId');
		$jobCode = trim($this->security->xss_clean($this->request->getPost('jobCode')));
		$jobName = trim($this->security->xss_clean($this->request->getPost('jobName')));
		$jobDescription = $jobCode . " " . $jobName;
		$companyId = trim($this->security->xss_clean($this->request->getPost('company')));

		$msj = "You have added a new job!!";
		if ($idJob != '') {
			$msj = "You have updated a Job!!";
		}

		//verificar si ya el job code
		$arrParam = array(
			"idJob" => $idJob,
			"column" => "job_code",
			"value" => $jobCode
		);
		$result_job = $this->generalModel->jobCodeVerify($arrParam);

		if ($result_job) {
			$data["result"] = "error";
			$data["mensaje"] = " Error. The Job Code already exist.";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> The Job Code already exist.');
		} else {
			if ($idJobSaved = $this->adminModel->saveJob()) {

				//If it is a new JOB, then send text messague to SAFETY USER
				if ($idJob == '') {
					//revisar si se envia mensaje de texto y a quien se le envia
					$arrParam = array("idNotification" => ID_NOTIFICATION_NEW_JOB);
					$configuracionAlertas = $this->generalModel->get_notifications_access($arrParam);

					if ($configuracionAlertas) {

						//mensaje de texto
						$mensajeSMS = "NEW JOB APP-VCI";
						$mensajeSMS .= "\nFor your records, a new Job Code has been created in the system.";
						$mensajeSMS .= "\nJob Code/Name: " . $jobDescription;

						if ($companyId) {
							$arrParam = array(
								"table" => "param_company",
								"order" => "id_company",
								"column" => "id_company",
								"id" => $companyId
							);
							$company = $this->generalModel->get_basic_search($arrParam); //company list

							$company = $company[0]['company_name'];

							$mensajeSMS .= "\nCompany name: " . $company;

							//foreman company
							$arrParam = array(
								"table" => "param_company_foreman",
								"order" => "fk_id_param_company",
								"column" => "fk_id_param_company",
								"id" => $companyId
							);
							$company_foreman = $this->generalModel->get_basic_search($arrParam); //company list

							if ($company_foreman) {
								$company_foreman = $company_foreman[0]['foreman_name'];

								$mensajeSMS .= "\nForeman name: " . $company_foreman;
							}
						}

						$this->db->select("L.*, CONCAT(first_name, ' ', last_name) name, J.job_description");
						$this->db->join('user U', 'U.id_user = L.created_by', 'INNER');
						$this->db->join('param_jobs j', 'L.type_id = j.id_job', 'LEFT');
						$this->db->order_by('L.id', 'asc');
						$this->db->where('L.type_id', $idJobSaved);
						$this->db->where('L.token', 'insert');
						$query = $this->db->get('logger L');
						$data = $query->result_array();

						$mensajeSMS .= "\nCreate for: " . $data[0]['name'];

						$this->sendNotifications($configuracionAlertas, $mensajeSMS);
					}
				}

				//save info FOREMAN
				$nameForeman = $this->request->getPost('foreman');
				if ($nameForeman != '') {
					$this->adminModel->save_foreman($idJobSaved);
				}

				$data["result"] = true;
				$this->session->set_flashdata('retornoExito', $msj);
			} else {
				$data["result"] = "error";
				$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
			}
		}

		echo json_encode($data);
	}

	/**
	 * vehicle List
	 * @since 15/12/2016
	 * @review 5/5/2017
	 * @author BMOTTAG
	 */
	public function vehicle($companyType, $vehicleType = 1, $vehicleState = 1)
	{
		$data['companyType'] = $companyType;
		$data['vehicleType'] = $vehicleType;
		$data['vehicleState'] = $vehicleState;
		$data['title'] = $companyType == 1 ? "VCI" : "RENTALS";

		$arrParam = array(
			"companyType" => $companyType,
			"vehicleState" => $vehicleState
		);
		//si es estado en 1 entonces envio el tipo de vehiculo
		if ($vehicleState == 1) {
			$arrParam['vehicleType'] = $vehicleType;
		}

		$data['info'] = $this->adminModel->get_vehicle_info_by($arrParam); //vehicle list
		$data["view"] = 'vehicle';
		$this->load->view("layout", $data);
	}

	/**
	 * Cargo modal - formulario vehicle
	 * @since 15/12/2016
	 * @review 27/12/2016
	 */
	public function cargarModalVehicle()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data['information'] = FALSE;
		$idVehicle = $this->request->getPost("idVehicle");
		//como se coloca un ID diferente para que no entre en conflicto con los otros modales, toca sacar el ID
		$porciones = explode("-", $idVehicle);

		$data["companyType"] = $porciones[0];
		$data["idVehicle"] = $porciones[1];

		$arrParam = array(
			"table" => "param_company",
			"order" => "company_name",
			"column" => "company_type",
			"id" => 2
		);
		$data['company'] = $this->generalModel->get_basic_search($arrParam); //company list

		//buscar la lista de tipo de vehiculo
		$arrParam = array(
			"table" => "param_vehicle_type_2",
			"order" => "type_2",
			"column" => "show_vehicle",
			"id" => 1
		);
		$data['vehicleType'] = $this->generalModel->get_basic_search($arrParam); //vehicleType list

		if ($data["idVehicle"] != 'x') {
			$arrParam = array(
				"table" => "param_vehicle",
				"order" => "id_vehicle",
				"column" => "id_vehicle",
				"id" => $data["idVehicle"]
			);
			$data['information'] = $this->generalModel->get_basic_search($arrParam);
		}

		return view('App\Modules\Admin\Views\vehicle_modal', $data);
	}

	/**
	 * Update vehicle
	 * @since 15/12/2016
	 * @review 27/12/2016
	 * @author BMOTTAG
	 */
	public function save_vehicle()
	{
		header('Content-Type: application/json');
		$data = array();

		$idVehicle = $this->request->getPost('hddId');
		$idCompany = $this->request->getPost('company');
		$data["compannyType"] = $idCompany == 1 ? 1 : 2; //1:VCI; 2:Subcontractor

		$pass = $this->generaPass(); //clave para colocarle al codigo QR

		$msj = "You have added a new vehicle!!";
		$flag = true;
		if ($idVehicle != '') {
			$msj = "You have updated a vehicle!!";
			$flag = false;
		}

		if ($idVehicle = $this->adminModel->saveVehicle($pass)) {

			if ($flag) { //si es un registro nuevo entonces guardo el historial de cambio de aceite
				$state = 0; //primer registro
				$this->adminModel->saveVehicleNextOilChange($idVehicle, $state);

				//si es un registro nuevo genero el codigo QR y subo la imagen
				//INCIO - genero imagen con la libreria y la subo 
				$this->load->library('ciqrcode');

				$valorQRcode = base_url("login/index/" . $idVehicle . $pass);
				$rutaImagen = "images/vehicle/" . $idVehicle . "_qr_code.png";

				$params['data'] = $valorQRcode;
				$params['level'] = 'H';
				$params['size'] = 10;
				$params['savename'] = FCPATH . $rutaImagen;

				$this->ciqrcode->generate($params);
				//FIN - genero imagen con la libreria y la subo
			}

			$data["result"] = true;
			$this->session->set_flashdata('retornoExito', $msj);
		} else {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}
		echo json_encode($data);
	}

	public function generaPass()
	{
		//Se define una cadena de caractares. Te recomiendo que uses esta.
		$cadena = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
		//Obtenemos la longitud de la cadena de caracteres
		$longitudCadena = strlen($cadena);

		//Se define la variable que va a contener la contraseña
		$pass = "";
		//Se define la longitud de la contraseña, en mi caso 10, pero puedes poner la longitud que quieras
		$longitudPass = 50;

		//Creamos la contraseña
		for ($i = 1; $i <= $longitudPass; $i++) {
			//Definimos numero aleatorio entre 0 y la longitud de la cadena de caracteres-1
			$pos = rand(0, $longitudCadena - 1);

			//Vamos formando la contraseña en cada iteraccion del bucle, añadiendo a la cadena $pass la letra correspondiente a la posicion $pos en la cadena de caracteres definida.
			$pass .= substr($cadena, $pos, 1);
		}
		return $pass;
	}

	/**
	 * photo
	 */
	public function photo($idVehicle, $error = '')
	{
		if (empty($idVehicle)) {
			show_error('ERROR!!! - You are in the wrong place.');
		}

		//busco datos del vehiculo
		$arrParam = array(
			"idVehicle" => $idVehicle
		);
		$data['vehicleInfo'] = $this->adminModel->get_vehicle_info_by($arrParam);

		$data['error'] = $error; //se usa para mostrar los errores al cargar la imagen 
		$data['idVehicle'] = $idVehicle;
		$data["view"] = 'vehicle_photo';
		$this->load->view("layout", $data);
	}

	/**
	 * FUNCIÓN PARA SUBIR LA IMAGEN 
	 * @param int vistaRegreso -> para saber si es de VCI o RENTADA
	 */
	function do_upload($type, $vistaRegreso)
	{
		$config['upload_path'] = './images/vehicle/';
		$config['overwrite'] = true;
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		$config['max_size'] = '3000';
		$config['max_width'] = '2024';
		$config['max_height'] = '2008';
		$idVehicle = $this->request->getPost("hddId");
		$config['file_name'] = $idVehicle . "_" . $type;

		$this->load->library('upload', $config);
		//SI LA IMAGEN FALLA AL SUBIR MOSTRAMOS EL ERROR EN LA VISTA 
		if (!$this->upload->do_upload()) {
			$error = $this->upload->display_errors();
			$this->$type($idVehicle, $error);
		} else {
			$file_info = $this->upload->data(); //subimos la imagen

			//USAMOS LA FUNCIÓN create_thumbnail Y LE PASAMOS EL NOMBRE DE LA IMAGEN,
			//ASÍ YA TENEMOS LA IMAGEN REDIMENSIONADA
			if ($type == "photo") {
				$this->_create_thumbnail($file_info['file_name']);
				$data = array('upload_data' => $this->upload->data());
				$imagen = $file_info['file_name'];
				$path = "images/vehicle/thumbs/" . $imagen;
			} elseif ($type == "qr_code") {
				$path = "images/vehicle/" . $file_info['file_name'];
			}

			//actualizamos el campo photo
			$arrParam = array(
				"table" => "param_vehicle",
				"primaryKey" => "id_vehicle",
				"id" => $idVehicle,
				"column" => $type,
				"value" => $path
			);

			$data['linkBack'] = "admin/vehicle/" . $vistaRegreso;
			$data['titulo'] = "<i class='fa fa-automobile'></i>VEHICLE";

			if ($this->generalModel->updateRecord($arrParam)) {
				$data['clase'] = "alert-success";
				$data['msj'] = "Good job, you have uploaded the photo.";
			} else {
				$data['clase'] = "alert-danger";
				$data['msj'] = "Ask for help.";
			}

			$data["view"] = 'template/answer';
			$this->load->view("layout", $data);
			//redirect('employee/photo');
		}
	}

	//FUNCIÓN PARA CREAR LA MINIATURA A LA MEDIDA QUE LE DIGAMOS
	function _create_thumbnail($filename)
	{
		$config['image_library'] = 'gd2';
		//CARPETA EN LA QUE ESTÁ LA IMAGEN A REDIMENSIONAR
		$config['source_image'] = 'images/vehicle/' . $filename;
		$config['create_thumb'] = TRUE;
		$config['maintain_ratio'] = TRUE;
		//CARPETA EN LA QUE GUARDAMOS LA MINIATURA
		$config['new_image'] = 'images/vehicle/thumbs/';
		$config['width'] = 150;
		$config['height'] = 150;
		$this->load->library('image_lib', $config);
		$this->image_lib->resize();
	}

	/**
	 * qr_code
	 */
	public function qr_code($idVehicle, $error = '')
	{
		if (empty($idVehicle)) {
			show_error('ERROR!!! - You are in the wrong place.');
		}

		//busco datos del vehiculo
		$arrParam = array(
			"idVehicle" => $idVehicle
		);
		$data['vehicleInfo'] = $this->adminModel->get_vehicle_info_by($arrParam);

		$data['error'] = $error; //se usa para mostrar los errores al cargar la imagen 
		$data['idVehicle'] = $idVehicle;
		$data["view"] = 'vehicle_qr_code';
		$this->load->view("layout", $data);
	}

	/**
	 * Next Oil change
	 * @param int $idVehicle
	 * @since 17/1/2017
	 */
	public function nextOilChange($idVehicle)
	{
		if (empty($idVehicle)) {
			show_error('ERROR!!! - You are in the wrong place.');
		}

		//busco datos del vehiculo
		$arrParam['idVehicle'] = $idVehicle;
		$data['vehicleInfo'] = $this->generalModel->get_vehicle_by($arrParam);

		$data['info'] = false;
		if($data['vehicleInfo'][0]['table_inspection']){
			$data['info'] = $this->generalModel->get_vehicle_oil_change($data['vehicleInfo']); //vehicle oil change history
		}

		$data['idVehicle'] = $idVehicle;
		$data["view"] = 'vehicle_inspections';
		$this->load->view("layout", $data);
	}

	/**
	 * Add vehicle oil change
	 * @since 17/1/2017
	 * @author BMOTTAG
	 */
	public function save_vehicle_oil_change()
	{
		header('Content-Type: application/json');
		$data = array();

		$data["idRecord"] = $this->request->getPost('hddId');
		$state = 2; //next oil change

		if ($this->adminModel->saveVehicleNextOilChange($data["idRecord"], $state)) {
			$data["result"] = true;
			$this->session->set_flashdata('retornoExito', "You have added the Next Oil Change");
		} else {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}
		echo json_encode($data);
	}

	/**
	 * Employee Type List
	 * @since 4/2/2017
	 * @author BMOTTAG
	 */
	public function employeeType()
	{
		$arrParam = array(
			"table" => "param_employee_type",
			"order" => "employee_type",
			"id" => "x"
		);
		$data['info'] = $this->generalModel->get_basic_search($arrParam);

		$data["view"] = 'employee_type';
		$this->load->view("layout", $data);
	}

	/**
	 * Cargo modal - formulario employee type
	 * @since 4/2/2017
	 */
	public function cargarModalEmployeeType()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data['information'] = FALSE;
		$data["idEmployeeType"] = $this->request->getPost("idEmployeeType");

		if ($data["idEmployeeType"] != 'x') {
			$arrParam = array(
				"table" => "param_employee_type",
				"order" => "id_employee_type",
				"column" => "id_employee_type",
				"id" => $data["idEmployeeType"]
			);
			$data['information'] = $this->generalModel->get_basic_search($arrParam);
		}

		return view('App\Modules\Admin\Views\employee_type_modal', $data);
	}

	/**
	 * Update employee type
	 * @since 4/2/2017
	 * @author BMOTTAG
	 */
	public function save_employee_type()
	{
		header('Content-Type: application/json');
		$data = array();

		$idEmployeeType = $this->request->getPost('hddId');

		$msj = "You have added a new Employee Type!!";
		if ($idEmployeeType != '') {
			$msj = "You have updated an Employee Type!!";
		}

		if ($idEmployeeType = $this->adminModel->saveEmployeeType()) {
			$data["result"] = true;
			$data["idRecord"] = $idEmployeeType;

			$this->session->set_flashdata('retornoExito', $msj);
		} else {
			$data["result"] = "error";
			$data["idRecord"] = "";

			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		echo json_encode($data);
	}

	/**
	 * Hazard Activity List
	 * @since 5/2/2017
	 * @author BMOTTAG
	 */
	public function hazardActivity()
	{
		$arrParam = array(
			"table" => "param_hazard_activity",
			"order" => "hazard_activity",
			"id" => "x"
		);
		$data['info'] = $this->generalModel->get_basic_search($arrParam);

		$data["view"] = 'hazard_activity';
		$this->load->view("layout", $data);
	}

	/**
	 * Cargo modal - formulario hazard Activity
	 * @since 5/2/2017
	 */
	public function cargarModalHazardActivity()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data['information'] = FALSE;
		$data["idHazardActivity"] = $this->request->getPost("idHazardActivity");

		if ($data["idHazardActivity"] != 'x') {
			$arrParam = array(
				"table" => "param_hazard_activity",
				"order" => "id_hazard_activity",
				"column" => "id_hazard_activity",
				"id" => $data["idHazardActivity"]
			);
			$data['information'] = $this->generalModel->get_basic_search($arrParam);
		}

		return view('App\Modules\Admin\Views\hazard_activity_modal', $data);
	}

	/**
	 * Update Hazard Activity
	 * @since 5/2/2017
	 * @author BMOTTAG
	 */
	public function save_hazard_activity()
	{
		header('Content-Type: application/json');
		$data = array();

		$idHazardActivity = $this->request->getPost('hddId');

		$msj = "You have added a new Activity!!";
		if ($idHazardActivity != '') {
			$msj = "You have updated an Activity!!";
		}

		if ($idHazardActivity = $this->adminModel->saveHazardActivity()) {
			$data["result"] = true;
			$data["idRecord"] = $idHazardActivity;

			$this->session->set_flashdata('retornoExito', $msj);
		} else {
			$data["result"] = "error";
			$data["idRecord"] = "";

			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		echo json_encode($data);
	}

	/**
	 * Change password
	 * @since 15/4/2017
	 * @author BMOTTAG
	 * @review 21/03/2026 - new CI4 version
	 */
	public function change_password($idUser)
	{
		if (empty($idUser)) {
			show_error('ERROR!!! - You are in the wrong place. The ID USER is missing.');
		}

		$arrParam = array(
			"table" => "user",
			"order" => "id_user",
			"column" => "id_user",
			"id" => $idUser
		);
		$data['information'] = $this->generalModel->get_basic_search($arrParam);

		return $this->render('App\Modules\Admin\Views\form_password', $data);
	}

	/**
	 * Update user´s password
	 * @review 27/03/2026 - new CI4 version
	 */
	public function update_password()
	{
		$data = [];
		$data["titulo"] = "<i class='fa fa-unlock fa-fw'></i>CHANGE PASSWORD";
		$data['linkBack'] = "admin/employee/1";

		$newPassword = $this->request->getPost("inputPassword");
		$confirm     = $this->request->getPost("inputConfirm");
		$user        = $this->request->getPost("hddUser");
		$idUser = $this->request->getPost("hddId");

		// Validación básica
		if (!$newPassword || !$confirm) {
			$data["msj"] = "Password is required.";
			$data["clase"] = "alert-danger";
			return $this->render('App\Views\template\answer', $data);
		}

		if ($newPassword !== $confirm) {
			$data["msj"] = "Passwords do not match.";
			$data["clase"] = "alert-danger";
			return $this->render('App\Views\template\answer', $data);
		}

		// Enviar al modelo
		if ($this->adminModel->updatePassword($idUser, $newPassword)) {
			$data["msj"] = "Password updated successfully.<br><strong>User:</strong> " . $user;
			$data["clase"] = "alert-success";
		} else {
			$data["msj"] = "<strong>Error!</strong> Please try again.";
			$data["clase"] = "alert-danger";
		}

		return $this->render('App\Views\template\answer', $data);
	}

	/**
	 * Cambio de estado de los proyectos
	 * @since 12/1/2019
	 * @author BMOTTAG
	 */
	public function jobs_state($state)
	{
		if (empty($this->request->getPost('job'))) {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> If you want to change the state of a single JOB CODE, you must do it through the form. This functionality is intended for all JOB CODES.');
			redirect(base_url('admin/job/1'), 'refresh');
			return; 
		}

		if ($this->adminModel->updateJobsState($state)) {
			$data["result"] = true;
			$this->session->set_flashdata('retornoExito', "You have updated the state!!");
		} else {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		redirect(base_url('admin/job/1'), 'refresh');
	}

	/**
	 * Cargo modal- formulario OIL CHANGE
	 * @since 13/1/2019
	 */
	public function cargarModalOilChange()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		//busco datos del vehiculo
		$arrParam['idVehicle'] = $this->request->getPost("idVehicle");
		$data['vehicleInfo'] = $this->generalModel->get_vehicle_by($arrParam);

		$tipo = $data['vehicleInfo'][0]['type_level_2'];

		//si es la sweeper o hydrovac muestro un formulario diferente
		if ($tipo == 15) {
			$vista = "modal_oil_change_sweeper";
		} elseif ($tipo == 16) {
			$vista = "modal_oil_change_hydrovac";
		} else {
			$vista = "modal_oil_change_normal";
		}

		$view = "App\Modules\Admin/Views/" . $vista;
		return view($view, $data);
	}

	/**
	 * Stock List
	 * @since 17/3/2020
	 * @author BMOTTAG
	 */
	public function stock()
	{
		//se filtra por company_type para que solo se pueda editar los subcontratistas
		$arrParam = array(
			"table" => "stock",
			"order" => "stock_description",
			"id" => "x"
		);
		$data['info'] = $this->generalModel->get_basic_search($arrParam);

		$data["view"] = 'stock';
		$this->load->view("layout", $data);
	}

	/**
	 * Cargo modal - formulario stock
	 * @since 17/3/2020
	 */
	public function cargarModalStock()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data['information'] = FALSE;
		$data["idStock"] = $this->request->getPost("idStock");

		if ($data["idStock"] != 'x') {
			$arrParam = array(
				"table" => "stock",
				"order" => "id_stock",
				"column" => "id_stock",
				"id" => $data["idStock"]
			);
			$data['information'] = $this->generalModel->get_basic_search($arrParam);
		}

		return view('App\Modules\Admin\Views\stock_modal', $data);
	}

	/**
	 * Update stock
	 * @since 17/3/2020
	 * @author BMOTTAG
	 */
	public function save_stock()
	{
		header('Content-Type: application/json');
		$data = array();

		$idStock = $this->request->getPost('hddId');

		$msj = "You have added a new stock!!";
		if ($idStock != '') {
			$msj = "You have updated a stock!!";
		}

		if ($idStock = $this->adminModel->saveStock()) {
			$data["result"] = true;
			$data["idRecord"] = $idStock;

			$this->session->set_flashdata('retornoExito', $msj);
		} else {
			$data["result"] = "error";
			$data["idRecord"] = "";

			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		echo json_encode($data);
	}

	/**
	 * Certificate List
	 * @since 14/1/2022
	 * @author BMOTTAG
	 * @review 28/03/2026 - new CI4 version
	 */
	public function certificate()
	{
		$params = [];
		$data['certificateList'] = $this->generalModel->get_certificate_list($params);
		$idCertificate = $this->request->getPost('idCertificate');
		$date = $this->request->getPost('date');
		if (!empty($idCertificate)) {
			$params['idCertificate'] = $idCertificate;
		}
		if (!empty($date)) {
			$params['date'] = $date;
		}
		$rows = $this->generalModel->get_certificates_with_users($params);

		$data['info'] = [];
		foreach ($rows as $row) {

			$id = $row['id_certificate'];

			if (!isset($data['info'][$id])) {
				$data['info'][$id] = [
					'id_certificate' => $row['id_certificate'],
					'certificate' => $row['certificate'],
					'certificate_description' => $row['certificate_description'],
					'employees' => []
				];
			}

			if (!empty($row['id_user'])) {
				$data['info'][$id]['employees'][] = [
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'date_through' => $row['date_through']
				];
			}
		}

		return $this->render('App\Modules\Admin\Views\certificate', $data);
	}

	/**
	 * Cargo modal - Certificados
	 * @since 14/1/2022
	 * @review 28/03/2026 - new CI4 version
	 */
	public function cargarModalCertificate()
	{
		$data = [];
		$data['information'] = null;

		$idCertificate = $this->request->getPost("idCertificate");
		$data["idCertificate"] = $idCertificate;

		if (!empty($idCertificate) && $idCertificate !== 'x') {
			$params['idCertificate'] = $idCertificate;
			$data['information'] = $this->generalModel->get_certificate_list($params);
		}

		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Admin\Views\certificate_modal', $data));
	}

	/**
	 * Guardar certificados
	 * @since 14/1/2022
	 * @author BMOTTAG
	 * @review 28/03/2026 - new CI4 version
	 */
	public function save_certificate()
	{
		$post = $this->request->getPost();

		$idCertificate = $post['hddId'] ?? null;
		$msj = $idCertificate 
			? "You have updated a Certificate!!" 
			: "You have added a new Certificate!!";

		$data = [];

		if ($this->adminModel->saveCertificate($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', $msj);
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * User Certificates
	 * @param int $idEmployee
	 * @since 15/1/2022
	 * @review 21/03/2026 - new CI4 version
	 */
	public function userCertificates($idUser)
	{
		if (empty($idUser)) {
			show_error('ERROR!!! - You are in the wrong place.');
		}

		//busco datos del vehiculo
		$arrParam['idUser'] = $idUser;
		$data = [];
		$data['UserInfo'] = $this->generalModel->get_user($arrParam);

		$data['info'] = $this->generalModel->get_user_certificates($arrParam);

		return $this->render('App\Modules\Admin\Views\employee_certificates', $data);
	}

	/**
	 * Cargo modal- Formulario de certificados
	 * @since 15/1/2022
	 */
	public function cargarModalUserCertificate()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data["idEmployee"] = $this->request->getPost("idEmployee");

		$arrParam = array(
			"table" => "param_certificates ",
			"order" => "certificate",
			"id" => "x"
		);
		$data['certificateList'] = $this->generalModel->get_basic_search($arrParam);

		return view('App\Modules\Admin\Views\employee_certificates_modal', $data);
	}

	/**
	 * Save employee certificate
	 * @since 15/1/2022
	 * @author BMOTTAG
	 */
	public function save_employee_certificate()
	{
		$post = $this->request->getPost();

		$data = [];

		$idEmployee = $post['hddidEmployee'] ?? null;
		$idEmployeeCertificate = $post['hddidEmployeeCertificate'] ?? null;
		$data["idRecord"] = $idEmployee;

		$msj = "You have added a new Certificate!!";

		$certificate_exist = false;

		// Validar si ya existe
		if (empty($idEmployeeCertificate)) {
			$certificate_exist = $this->generalModel->get_user_certificates([
				"idUser" => $idEmployee,
				"idCertificate" => $post['certificate']
			]);
		}

		if ($certificate_exist) {

			$data["result"] = "error";
			$data["mensaje"] = "Error. The Employee already has the certificate.";

			session()->setFlashdata(
				'retornoError',
				'<strong>Error!!!</strong> The certificate already exists.'
			);

		} else {

			if ($this->adminModel->saveEmployeeCertificate($post)) {
				$data["result"] = true;
				session()->setFlashdata('retornoExito', $msj);
			} else {

				$data["result"] = "error";
				$data["mensaje"] = "Error saving data";

				session()->setFlashdata(
					'retornoError',
					'<strong>Error!!!</strong> Ask for help'
				);
			}
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Delete Employee Certificatte
	 * @since 8/7/2018
	 */
	public function delete_user_certificate()
	{
		$post = $this->request->getPost();

		$data = [];

		$idUserCertificate = $post['identificador'] ?? null;

		// Buscar certificado
		$certificate = $this->generalModel->get_user_certificates([
			'idUserCertificate' => $idUserCertificate
		]);

		if (!$certificate) {
			$data["result"] = "error";
			$data["mensaje"] = "Record not found";

			return $this->response->setJSON($data);
		}

		$idUser = $certificate[0]['fk_id_user'];
		$data["idRecord"] = $idUser;

		// Eliminar registro
		$arrParam = [
			"table" => "user_certificates",
			"primaryKey" => "id_user_certificate",
			"id" => $idUserCertificate
		];

		if ($this->generalModel->deleteRecord($arrParam)) {

			$data["result"] = true;
			$data["mensaje"] = "You have deleted one record.";

			session()->setFlashdata(
				'retornoExito',
				'You have deleted one record'
			);

		} else {

			$data["result"] = "error";
			$data["mensaje"] = "Error!!! Ask for help.";

			session()->setFlashdata(
				'retornoError',
				'<strong>Error!!!</strong> Ask for help'
			);
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Update User Certificate
	 * @since 15/1/2022
	 * @author BMOTTAG
	 */
	public function update_user_certificate()
	{
		$post = $this->request->getPost();

		$idUserCertificate = $post['hddidEmployeeCertificate'] ?? null;

		// Buscar info del certificado
		$certificate_exist = $this->generalModel->get_user_certificates([
			'idUserCertificate' => $idUserCertificate
		]);

		if (!$certificate_exist) {
			session()->setFlashdata(
				'retornoError',
				'<strong>Error!!!</strong> Record not found'
			);

			return redirect()->to(base_url('admin/employee/1'));
		}

		$idUser = $certificate_exist[0]['fk_id_user'];

		// Guardar (UPDATE)
		if ($this->adminModel->saveEmployeeCertificate($post)) {

			session()->setFlashdata(
				'retornoExito',
				"You have updated the Date!!"
			);

		} else {

			session()->setFlashdata(
				'retornoError',
				'<strong>Error!!!</strong> Ask for help'
			);
		}
		return redirect()->to(base_url('admin/userCertificates/' . $idUser));
	}

	/**
	 * Alert List
	 * @since 23/01/2022
	 * @review 22/12/2022
	 * @author BMOTTAG
	 */
	public function notifications()
	{
		$arrParam = array();
		$data['info'] = $this->generalModel->get_notifications_access_view($arrParam);

		$data["view"] = 'notifications';
		$this->load->view("layout", $data);
	}

	/**
	 * Cargo modal - formulario configuracion de alertas
	 * @since 23/01/2022
	 */
	public function cargarModalNotification()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data['information'] = FALSE;
		$data["idNotificationAccess"] = $this->request->getPost("idNotificationAccess");

		$arrParam = array("state" => 1);
		$data['workersList'] = $this->generalModel->get_user($arrParam);

		$sql = "SELECT n.*
			FROM notifications n
			LEFT JOIN notifications_access na ON n.id_notification = na.fk_id_notification
			WHERE na.fk_id_notification IS NULL AND n.setup = 1;
			";

		$query = $this->db->query($sql);

		if ($query->num_rows() >= 1) {
			$data['notificationsList'] =  $query->result_array();
		} else {
			$data['notificationsList'] = [];
		}

		if ($data["idNotificationAccess"] != 'x') {
			$arrParam = array(
				"idNotificationAccess" => $data["idNotificationAccess"]
			);
			$data['information'] = $this->generalModel->get_notifications_access_view($arrParam);
		}

		return view('App\Modules\Admin\Views\notifications_modal', $data);
	}

	/**
	 * Save notifications access settings
	 * @since 23/01/2022
	 * @review 22/12/2022
	 * @author BMOTTAG
	 */
	public function save_notifications()
	{
		header('Content-Type: application/json');
		$data = array();

		$idNotificationAccess = $this->request->getPost('hddId');

		$msj = "You have added a new Notification Access!!";
		if ($idNotificationAccess != '') {
			$msj = "You have updated the Notification Access!!";
		}

		if ($this->adminModel->saveNotification()) {
			$data["result"] = true;
			$this->session->set_flashdata('retornoExito', $msj);
		} else {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		echo json_encode($data);
	}

	/**
	 * CRON
	 * Verificar si esta proximo a vencerse los certificados para los empleados activos
	 * El CRON se corre todos los lunes
	 * @since 23/1/2022
	 */
	function certifications_check()
	{
		$arrParam = array(
			"state" => 1,
			"expires" => 1,
		);
		$information  = $this->generalModel->get_user_certificates($arrParam);

		if ($information) {
			//revisar si se envia correo o se envia mensaje de texto y a quien se le envia
			$arrParam = array("idNotification" => ID_NOTIFICATION_CERTIFICATION);
			$configuracionAlertas = $this->generalModel->get_notifications_access($arrParam);

			if ($configuracionAlertas) {
				$filtroFecha = strtotime(date('Y-m-d'));
				$msj = "";
				//para cada certificado reviso si esta vencido
				foreach ($information as $lista) :
					//semaforo de acuerdo a fecha de vencimiento
					$fechaVencimiento = strtotime($lista['date_through']);
					$diferencia = $fechaVencimiento - $filtroFecha;
					$updateAlert = false;
					//2678400 --> equivalen a 30 dias
					if ($diferencia < 8035200 && $lista['alerts_sent'] == 0) {
						//si la diferencia es menor de 90 dias
						//envio notificacion y actualizo el campo a 1
						$updateAlert = true;
					} elseif ($diferencia <= 5356800 && $lista['alerts_sent'] == 1) {
						//si la diferencia es entre 60 dias y 30 dias 
						//envio notificacion y actualizo el campo a 2
						$updateAlert = true;
					} elseif ($diferencia <= 2678400 && $diferencia >= 0 && $lista['alerts_sent'] == 2) {
						//si la diferencia es menor de 30 dias
						//envio notificacion y actualizo el campo a 3
						$updateAlert = true;
					}

					if ($updateAlert) {
						//mensaje para el correo
						$msj .= "<p>";
						$msj .= "<strong>Employee: </strong>" . $lista['first_name'] . " " . $lista['last_name'];
						$msj .= "<br><strong>Certificate: </strong>" . $lista['certificate'];
						$msj .= "<br><strong>Date Throught : </strong>" . $lista['date_through'];
						$msj .= "</p>";

						$alerts_sent = $lista['alerts_sent'] + 1;
						$arrParam = array(
							"table" => "user_certificates",
							"primaryKey" => "id_user_certificate",
							"id" => $lista['id_user_certificate'],
							"column" => "alerts_sent",
							"value" => $alerts_sent
						);
						$this->generalModel->updateRecord($arrParam);
					}
				endforeach;

				//configuracion para envio de mensaje de texto
				$this->load->library('encrypt');
				require 'vendor/Twilio/autoload.php';

				//busco datos parametricos twilio
				$arrParam = array(
					"table" => "parametric",
					"order" => "id_parametric",
					"id" => "x"
				);
				$parametric = $this->generalModel->get_basic_search($arrParam);
				$dato1 = $this->encrypt->decode($parametric[3]["value"]);
				$dato2 = $this->encrypt->decode($parametric[4]["value"]);
				$twilioPhone = $parametric[5]["value"];

				$client = new Twilio\Rest\Client($dato1, $dato2);

				foreach ($configuracionAlertas as $envioAlerta) :
					//envio correo 
					if ($envioAlerta['email'] && $msj) {
						$user = $envioAlerta['name_email'];
						$to = $envioAlerta['email'];

						//Contenido correo
						$subjet = "Certificate Overdue";
						$mensaje = "<html>
							<head>
							<title> $subjet </title>
							</head>
							<body>
								<p>Dear	$user:</p>
								<p>The following employees has a certificate that is about to expire:</p>
								<p>$msj</p>
								<p>Cordially,</p>
								<p><strong>V-CONTRACTING INC</strong></p>
							</body>
							</html>";

						$headers = "MIME-Version: 1.0\r\n";
						$headers .= "Content-Type: text/html; charset=utf-8\r\n";
						$headers .= "From: VCI APP <info@v-contracting.ca>\r\n";

						//enviar correo
						$envio = mail($to, $subjet, $mensaje, $headers);
					}

					//envio mensaje de texto
					if ($envioAlerta['movil'] && $msj) {
						$to = '+1' . $envioAlerta['movil'];
						$mensaje = "APP VCI - Employees Certificates";
						$mensaje .= "\n There are some employees who have a certificate that is about to expire, go to Settings - Employee and check.";

						$client->messages->create(
							$to,
							array(
								'from' => $twilioPhone,
								'body' => $mensaje
							)
						);
					}

				endforeach;
			}
		}
		return true;
	}

	/**
	 * Employee Rate List
	 * @since 16/2/2022
	 * @author BMOTTAG
	 */
	public function employeeSettings()
	{
		$arrParam = array("filtroState" => TRUE);
		$data['info'] = $this->generalModel->get_user($arrParam);

		$data['dashboardURL'] = $this->session->userdata("dashboardURL");
		$data["view"] = 'employee_settings';
		$this->load->view("layout_calendar", $data);
	}

	/**
	 * Update the employee rate of each field
	 * @since 16/2/2022
	 * @author BMOTTAG
	 */
	public function update_employee_rate()
	{
		if ($this->adminModel->updateEmployeeRate()) {
			$data["result"] = true;
			$this->session->set_flashdata('retornoExito', "You have updated the Employee Rate List!!");
		} else {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		redirect(base_url("admin/employeeSettings"), 'refresh');
	}

	/**
	 * Form CkeckIN check
	 * Used by cron
	 * @since 4/06/2022
	 * @author BMOTTAG
	 */
	public function checkin_check()
	{
		$this->load->library('encrypt');
		require 'vendor/Twilio/autoload.php';

		//busco datos parametricos twilio
		$arrParam = array(
			"table" => "parametric",
			"order" => "id_parametric",
			"id" => "x"
		);
		$parametric = $this->generalModel->get_basic_search($arrParam);
		$twilioSID = $this->encrypt->decode($parametric[3]["value"]);
		$twilioToken = $this->encrypt->decode($parametric[4]["value"]);
		$twilioPhone = $parametric[5]["value"];

		$client = new Twilio\Rest\Client($twilioSID, $twilioToken);

		$arrParam = array(
			"today" => date('Y-m-d'),
			"checkout" => true
		);
		$checkinList = $this->generalModel->get_checkin($arrParam);

		if ($checkinList) {
			$x = 0;
			foreach ($checkinList as $data) :
				$x++;
				//send sms to the employee
				$mensaje = "VCI Sign-Out";
				$mensaje .= "\n" . $data['worker_name'];
				$mensaje .= "\n";
				$mensaje .= "This message is to remind you that you still ON the working list at the work site, it is possible that you forgot to sign out.";
				$mensaje .= "\nUse the following link to Sign-Out.";
				$mensaje .= "\n";
				$mensaje .= "\n";
				$mensaje .= base_url("external/checkin/" . $data['fk_id_job'] . "/" . $data['id_checkin']);

				$to = '+1' . $data['worker_movil'];
				$client->messages->create(
					$to,
					array(
						'from' => $twilioPhone,
						'body' => $mensaje
					)
				);
			endforeach;
			echo $x . " messages have been sent to people that haven't Check-Out";
		} else {
			echo "Everybody have the Check-Out done.";
		}
	}

	/**
	 * Employee Bank Time List
	 * @since 9/9/2022
	 * @author BMOTTAG
	 */
	public function employeBankTime($idUser)
	{
		$data["idUser"] = $idUser;

		$arrParam = array("idUser" => $idUser);
		$data['info'] = $this->generalModel->get_bank_time($arrParam);

		$data["view"] = 'employee_bank_time';
		$this->load->view("layout_calendar", $data);
	}

	/**
	 * Cargo modal - formulario Add Balance to Bank time
	 * @since 9/9/2022
	 */
	public function cargarModalBankTimeBalance()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data['information'] = FALSE;
		$data["idEmployee"] = $this->request->getPost("idEmployee");

		$arrParam = array(
			"idUser" => $data["idEmployee"]
		);
		$data['information'] = $this->generalModel->get_user($arrParam);

		return view('App\Modules\Admin\Views\employee_bank_time_modal', $data);
	}

	/**
	 * Insert bank time
	 * @since 9/9/2022
	 * @author BMOTTAG
	 */
	public function save_bank_time_balance()
	{
		header('Content-Type: application/json');
		$data = array();

		$data["idEmployee"] = $this->request->getPost('hddId');
		$bankTimeAdd = $this->request->getPost('time');
		$msj = "You have added Balance to Bank Time!!";

		$arrParam = array(
			"idUser" => $data["idEmployee"],
			"limit" => 1
		);
		$infoBankTime = $this->generalModel->get_bank_time($arrParam);

		$bankNewBalance = $infoBankTime ? $infoBankTime[0]["balance"] + $bankTimeAdd : $bankTimeAdd;

		$arrParamBankTime = array(
			"idPeriod" => 0,
			"idEmployee" => $data["idEmployee"],
			"bankTimeAdd" => $bankTimeAdd,
			"bankTimeSubtract" => 0,
			"bankNewBalance" => $bankNewBalance,
			"observation" => "Bank Time Added"
		);
		if ($this->generalModel->saveBankTimeBalance($arrParamBankTime)) {
			$data["result"] = true;
			$this->session->set_flashdata('retornoExito', $msj);
		} else {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		echo json_encode($data);
	}

	/**
	 * View for JOB CODE - QR CODE
	 * @since 19/12/2022
	 * @author BMOTTAG
	 */
	public function job_qr_code($idJob)
	{
		if (empty($idJob)) {
			show_error('ERROR!!! - You are in the wrong place.');
		}

		//job info
		$arrParam = array(
			"table" => "param_jobs",
			"order" => "job_description",
			"column" => "id_job",
			"id" => $idJob
		);
		$data['jobInfo'] = $this->generalModel->get_basic_search($arrParam);

		//if there is not a QR CORE then it generate the QR CODE
		if (!$data['jobInfo'][0]["qr_code_timesheet"]) {
			//INCIO - genero imagen con la libreria y la subo 
			$this->load->library('ciqrcode');
			https: //v-contracting.ca/app//576
			$valorQRcode = base_url("external/checkin/" . $idJob);
			$rutaImagen = "images/qrcode/job_timesheet/" . $idJob . "_qr_code.png";

			$params['data'] = $valorQRcode;
			$params['level'] = 'H';
			$params['size'] = 10;
			$params['savename'] = FCPATH . $rutaImagen;

			$this->ciqrcode->generate($params);
			//FIN - genero imagen con la libreria y la subo

			//Update timesheet qr code field
			$arrParam = array(
				"table" => "param_jobs",
				"primaryKey" => "id_job",
				"id" => $idJob,
				"column" => "qr_code_timesheet",
				"value" => $rutaImagen
			);
			$this->generalModel->updateRecord($arrParam);
		}

		$data['idJob'] = $idJob;
		$data["view"] = 'job_qr_code';
		$this->load->view("layout", $data);
	}

	/**
	 * Attachments List
	 * @since 23/06/2023
	 * @author BMOTTAG
	 */
	public function attachments($status)
	{
		$data['status'] = $status;
		$arrParam = array(
			"status" => $status
		);
		$data['info'] = $this->adminModel->get_attachments($arrParam);

		$data["view"] = 'attachment';
		$this->load->view("layout", $data);
	}

	/**
	 * Cargo modal - formulario company
	 * @since 23/06/2023
	 */
	public function cargarModalAttachments()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$data['information'] = FALSE;
		$data['informationAttachments'] = FALSE;
		$data["idAttachment"] = $this->request->getPost("idAttachment");

		$data['equipmentType'] = $this->generalModel->equipmentByTypeList();

		if ($data["idAttachment"] != 'x') {
			$arrParam = array(
				"idAttachment" => $data["idAttachment"]
			);
			$data['information'] = $this->adminModel->get_attachments($arrParam);
			$data['informationAttachments'] = $this->adminModel->get_attachments_equipment($arrParam);
		}

		return view('App\Modules\Admin\Views\attachment_modal', $data);
	}

	/**
	 * Update Attachments
	 * @since 23/06/2023
	 * @author BMOTTAG
	 */
	public function save_attachments()
	{
		header('Content-Type: application/json');
		$data = array();

		$idAttachment = $this->request->getPost('hddId');

		$msj = "You have added a new Attachment!!";
		if ($idAttachment != '') {
			$msj = "You have updated an Attachment!!";
		}

		if ($idAttachment = $this->adminModel->saveAttachment()) {
			$this->adminModel->add_equipment_attachement($idAttachment);
			$data["result"] = true;
			$this->session->set_flashdata('retornoExito', $msj);
		} else {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		echo json_encode($data);
	}

	/**
	 * Update Attachments
	 * @since 23/06/2023
	 * @author BMOTTAG
	 */
	public function update_status()
	{
		header('Content-Type: application/json');

		$data = array();

		$idAttachment = $this->request->getPost('attachmentId');
		$status = $this->request->getPost('status');
		$value = $status == "active" ? "inactive" : "active";

		$arrParam = array(
			"table" => "param_attachments",
			"primaryKey" => "id_attachment",
			"id" => $idAttachment,
			"column" => "attachment_status",
			"value" => $value
		);
		if ($this->generalModel->updateRecord($arrParam)) {
			$data["result"] = true;
			$this->session->set_flashdata('retornoExito', "You have changed the Attachment status!!");
		} else {
			$data["result"] = "error";
			$this->session->set_flashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		echo json_encode($data);
	}

	/**
	 * Equipment list
	 * @since 24/6/2023
	 * @author BMOTTAG
	 */
	public function equipmentList()
	{
		header("Content-Type: text/plain; charset=utf-8"); //Para evitar problemas de acentos

		$arrParam = array(
			"vehicleType" => $this->request->getPost('type'),
			"vehicleState" => 1
		);
		$lista = $this->generalModel->get_vehicle_by($arrParam);

		if ($this->request->getPost('idAttachment') != "") {
			$arrParam = array(
				"idAttachment" => $this->request->getPost('idAttachment'),
				"relation" => true
			);
			$arrayInformationAttachments = $this->adminModel->get_attachments_equipment($arrParam);
		}

		echo "<option value=''>Select...</option>";
		if ($lista) {
			foreach ($lista as $fila) {
				$s = "";
				if ($arrayInformationAttachments) {
					$found = false;
					foreach ($arrayInformationAttachments as $idVehicle) {
						if (in_array($fila['id_vehicle'], $idVehicle)) {
							$found = true;
							break;
						}
					}
					$s = $found ? "selected" : "";
				}
				echo "<option value='" . $fila["id_vehicle"] . "'" . $s . ">" . $fila["unit_number"] . " -----> " . $fila["description"]  . "</option>";
			}
		}
	}

	/**
	 * Notifications
	 * @author BMOTTAG
	 * @since  14/01/2025
	 */
	public function sendNotifications($configuracionAlertas, $mensajeSMS)
	{
		//configuracion para envio de mensaje de texto
		$this->load->library('encrypt');
		require 'vendor/Twilio/autoload.php';

		//busco datos parametricos twilio
		$arrParam = array(
			"table" => "parametric",
			"order" => "id_parametric",
			"id" => "x"
		);
		$parametric = $this->generalModel->get_basic_search($arrParam);
		$dato1 = $this->encrypt->decode($parametric[3]["value"]);
		$dato2 = $this->encrypt->decode($parametric[4]["value"]);
		$twilioPhone = $parametric[5]["value"];

		$client = new Twilio\Rest\Client($dato1, $dato2);

		foreach ($configuracionAlertas as $envioAlerta):
			//envio mensaje de texto
			if ($envioAlerta['movil']) {
				$to = '+1' . $envioAlerta['movil'];
				$client->messages->create(
					$to,
					array(
						'from' => $twilioPhone,
						'body' => $mensajeSMS
					)
				);
			}

		endforeach;
		return true;
	}
}
