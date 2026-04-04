<?php
namespace App\Modules\Dayoff\Controllers;

use App\Controllers\BaseController;
use App\Modules\Dayoff\Models\DayoffModel;
use App\Models\GeneralModel;

class Dayoff extends BaseController
{
    protected $dayoffModel;
    protected $generalModel;
    
    public function __construct()
    {
        $this->dayoffModel   = new DayoffModel();
        $this->generalModel   = new GeneralModel();
    }

	/**
	 * List Day Off
     * @since 7/12/2016
     * @author BMOTTAG
	 * @review 04/04/2026 - new CI4 version
	 */
	public function index()
	{
			$data['dayoffList'] = $this->generalModel->get_day_off(["idEmployee" => true]);
			return $this->render('App\Modules\Dayoff\Views\dayoff_list', $data);
	}
	
    /**
     * Cargo modal- formulario dayoff
     * @since 07/12/2016
	 * @review 04/04/2026 - new CI4 version
     */
	public function cargarModal()
	{
		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Dayoff\Views\modal_dayoff'));
	}

	/**
	 * Save dayoff
     * @since 04/12/2016
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function save_dayoff()
	{
		$post = $this->request->getPost();

		//verify ---- 24 hours in advaced for Family/medical appointment and 72 hours for regular
		$type =  $post['type'];//1: Family/medical appointment; 2: Regular
		
		date_default_timezone_set('America/Phoenix');
		$today = date("Y-m-d H:i:s"); 
		$date =  $post['date'];

		// Validar formato YYYY-MM-DD
		if (!$date || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
			return $this->response->setJSON([
				"status" => "error",
				"message" => "Invalid date format. Please select a valid date."
			]);
		}

		// Validar que realmente sea una fecha válida (ej. 2025-02-30 no existe)
		list($y, $m, $d) = explode('-', $date);
		if (!checkdate((int)$m, (int)$d, (int)$y)) {
			return $this->response->setJSON([
				"status" => "error",
				"message" => "The date is not valid. Please select a correct one."
			]);
		}

		//START hours calculation
		$minutes = (strtotime($today)-strtotime($date))/60;
		$minutes = abs($minutes);  
		$minutes = round($minutes);

		$hours = $minutes/60;
		
		if($type == 1 && 24 > $hours ){
			return $this->response->setJSON([
				"status" => "error",
				"message" => "Error!!. You need more than 24 hours to request the dayoff."
			]);
		}
		if($type == 2 && 72 > $hours ){
			return $this->response->setJSON([
				"status" => "error",
				"message" => "Error!!. You need more than 72 hours to request the dayoff."
			]);
		}



		$data = [];

		if ($idDayoff = $this->dayoffModel->add_dayoff($post)) {

/*

						//revisar si se envia correo o se envia mensaje de texto y a quien se le envia
						$configuracionAlertas = $this->generalModel->get_notifications_access(["idNotification" => ID_NOTIFICATION_DAYOFF]);

						if($configuracionAlertas){
							//buscar info del day off
							$arrParam["idDayoff"] = $idDayoff;
							$dayoffInfo = $this->generalModel->get_day_off($arrParam);
							
							switch ($dayoffInfo[0]['id_type_dayoff']) {
								case 1:
									$tipo = 'Family/medical appointment';
									break;
								case 2:
									$tipo = 'Regular';
									break;
							}
							//mensaje del correo
							$subjet = "DAY OFF APP-VCI";
							$observation =  $this->security->xss_clean($this->input->post('observation'));
							$observation =  addslashes($observation);
							$mensajeEmail = "<p>There is a new request for a Day Off:</p>";
							$mensajeEmail .= "<strong>Employee: </strong>" . $dayoffInfo[0]["name"];
							$mensajeEmail .= "<br><strong>Type: </strong>" . $tipo;
							$mensajeEmail .= "<br><strong>Date of dayoff: </strong>" . $dayoffInfo[0]["date_dayoff"];
							$mensajeEmail .= "<br><strong>Observation: </strong>" . $observation;
							$mensajeEmail .= "<p>Follow the link to approve or deny the Day Off: </p>";
							
							//mensaje de texto
							$mensajeSMS = "DAY OFF APP-VCI";
							$mensajeSMS .= "\nThere is a new request for a Day Off:";
							$mensajeSMS .= "\nEmployee: " . $dayoffInfo[0]["name"];
							$mensajeSMS .= "\nType: " . $tipo;
							$mensajeSMS .= "\nDate of dayoff: " . $dayoffInfo[0]["date_dayoff"];
							$mensajeSMS .= "\nObservation: " . $dayoffInfo[0]["observation"];
							$mensajeSMS .= "\nFollow the link to review: ";

							$this->sendNotifications($idDayoff, $configuracionAlertas, $subjet, $mensajeEmail, $mensajeSMS);
						}


*/








			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'Thank you. The ADMIN will review your request.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}
	
	/**
	 * List Day Off, for ADMIN
     * @since 8/12/2016
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function newDayoffList()
	{
			$data["state"] = 1;//new
			$data['dayoffList'] = $this->generalModel->get_day_off(['state' => 1]);
			
			$data["tittle"] = "New Request";
			$data["icon"] = "fa-hand-o-right";
			return $this->render('App\Modules\Dayoff\Views\admin_dayoff_list', $data);
	}
	
    /**
     * Cargo modal - formulario aprobar dayoff
     * @since 8/12/2016
	 * @review 03/04/2026 - new CI4 version
     */
    public function cargarModalApproved() 
	{
		$data["idDayoff"] = $this->request->getPost("idDayoff");
		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Dayoff\Views\modal_approved', $data));
    }
	
	/**
	 * Save approved
     * @since 8/12/2016
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */

	public function save_approved()
	{
		$post = $this->request->getPost();
		$state =  $post['state'];
		$observation =  $post['observation'];
		
		if($state == 3 && $observation ==''){
			return $this->response->setJSON([
				"status" => "error",
				"message" => "You must write an observation."
			]);
		}

		$data = [];
		if ($this->dayoffModel->update_dayoff($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'Information saved successfully!!');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}
	
	/**
	 * List Day Off, for ADMIN
     * @since 8/12/2016
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function approvedDayoffList()
	{
			$data["state"] = 2;//approved
			$data['dayoffList'] = $this->generalModel->get_day_off(['state' => 2]);
			
			$data["tittle"] = "Approved Request";
			$data["icon"] = "fa-hand-o-up";
			return $this->render('App\Modules\Dayoff\Views\admin_dayoff_list', $data);
	}
	
	/**
	 * List Day Off, for ADMIN
     * @since 8/12/2016
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function deniedDayoffList()
	{
			$data["state"] = 3;//Denied
			$data['dayoffList'] = $this->generalModel->get_day_off(['state' => 3]);
			
			$data["tittle"] = "Denied Request";
			$data["icon"] = "fa-hand-o-down";
			return $this->render('App\Modules\Dayoff\Views\admin_dayoff_list', $data);
	}

    /**
     * Notifications
     * @author BMOTTAG
     * @since  26/12/2022
     */
    public function sendNotifications($idDayoff, $configuracionAlertas, $subjet, $mensajeEmail, $mensajeSMS) 
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

		foreach($configuracionAlertas as $envioAlerta):
			//envio correo 
			if (!empty($envioAlerta['email'])) 
			{
				$user = $envioAlerta['name_email'];
				$to = $envioAlerta['email'];
				//$to = "benmotta@gmail.com";

				// ⚡ usar id_user individual, no el JSON completo
				$urlEmail = base_url("external/aproveDayOff/" . $idDayoff . "/" . $envioAlerta['id_user_email']);
				$mensajeEmailFinal = $mensajeEmail . $urlEmail;

				// Contenido correo					
				$mensaje = "<html>
							<head>
							<title>$subjet</title>
							</head>
							<body>
								<p>Dear $user:<br/></p>
								$mensajeEmailFinal
								<p>Cordially,</p>
								<p><strong>V-CONTRACTING INC</strong></p>
							</body>
							</html>";

				$headers = "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: text/html; charset=utf-8\r\n";
				$headers .= "From: VCI APP <info@v-contracting.ca>\r\n";

				mail($to, $subjet, $mensaje, $headers);
			}

			// envío de SMS
			if (!empty($envioAlerta['movil'])) 
			{
				$toSms = '+1'. $envioAlerta['movil'];
				//$toSms = '+14034089921';

				// ⚡ usar id_user individual
				$urlMovil = base_url("external/aproveDayOff/" . $idDayoff . "/" . $envioAlerta['id_user_sms']);
				$mensajeSMSFinal = $mensajeSMS . $urlMovil;

				$client->messages->create(
					$toSms,
					array(
						'from' => $twilioPhone,
						'body' => $mensajeSMSFinal
					)
				);
			}
		endforeach;
		return true;
	}
	
}
