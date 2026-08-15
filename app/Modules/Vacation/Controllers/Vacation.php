<?php
namespace App\Modules\Vacation\Controllers;

use App\Controllers\BaseController;
use App\Modules\Vacation\Models\VacationModel;
use App\Models\GeneralModel;

class Vacation extends BaseController
{
    protected $vacationModel;
    protected $generalModel;

    public function __construct()
    {
        $this->vacationModel = new VacationModel();
        $this->generalModel  = new GeneralModel();
    }

	/**
	 * List Vacation
	 * @since 15/08/2026
	 */
	public function index()
	{
			$arrParam = [];
			if (session()->get('rol') == ID_ROL_BASIC) {
				$arrParam['idEmployee'] = true;
			}
			$data['vacationList'] = $this->generalModel->get_vacation($arrParam);
			return $this->render('App\Modules\Vacation\Views\vacation_list', $data);
	}

    /**
     * Cargo modal- formulario vacation
     * @since 15/08/2026
     */
	public function cargarModal()
	{
		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Vacation\Views\modal_vacation'));
	}

	/**
	 * Save vacation
     * @since 15/08/2026
	 */
	public function save_vacation()
	{
		$post = $this->request->getPost();

		date_default_timezone_set('America/Phoenix');
		$today     = date("Y-m-d H:i:s");
		$dateStart = $post['date_start'] ?? '';
		$dateEnd   = $post['date_end'] ?? '';

		// Validar formato YYYY-MM-DD
		if (!$dateStart || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $dateStart)
			|| !$dateEnd || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $dateEnd)) {
			return $this->response->setJSON([
				"status" => "error",
				"message" => "Invalid date format. Please select valid dates."
			]);
		}

		// Validar que realmente sean fechas válidas
		list($ys, $ms, $ds) = explode('-', $dateStart);
		list($ye, $me, $de) = explode('-', $dateEnd);
		if (!checkdate((int)$ms, (int)$ds, (int)$ys) || !checkdate((int)$me, (int)$de, (int)$ye)) {
			return $this->response->setJSON([
				"status" => "error",
				"message" => "The date is not valid. Please select correct ones."
			]);
		}

		if ($dateEnd < $dateStart) {
			return $this->response->setJSON([
				"status" => "error",
				"message" => "The end date must be on or after the start date."
			]);
		}

		//START hours calculation - 72 hours in advanced
		$minutes = (strtotime($today) - strtotime($dateStart)) / 60;
		$minutes = abs($minutes);
		$minutes = round($minutes);

		$hours = $minutes / 60;

		if (72 > $hours) {
			return $this->response->setJSON([
				"status" => "error",
				"message" => "Error!!. You need more than 72 hours to request the vacation."
			]);
		}

		if ($this->vacationModel->has_overlap($post)) {
			return $this->response->setJSON([
				"status" => "error",
				"message" => "You already have a pending or approved vacation request that overlaps with these dates."
			]);
		}

		$data = [];

		if ($idVacation = $this->vacationModel->add_vacation($post)) {

			//revisar si se envia correo o se envia mensaje de texto y a quien se le envia
			$configuracionAlertas = $this->generalModel->get_notifications_access(["idNotification" => ID_NOTIFICATION_VACATION]);

			if ($configuracionAlertas) {
				$vacationInfo = $this->generalModel->get_vacation(["idVacation" => $idVacation]);

				$subject     = "Vacation App - VCI";
				$observation = esc($post['observation']);

				$emailBody  = "<p>There is a new request for a Vacation:</p>";
				$emailBody .= "<strong>Employee: </strong>" . esc($vacationInfo[0]["name"]);
				$emailBody .= "<br><strong>Date start: </strong>" . esc($vacationInfo[0]["date_start"]);
				$emailBody .= "<br><strong>Date end: </strong>" . esc($vacationInfo[0]["date_end"]);
				$emailBody .= "<br><strong>Observation: </strong>" . $observation;
				$emailBody .= "<p>Follow the link to approve or deny the Vacation: </p>";

				$smsMessage  = "Vacation App - VCI";
				$smsMessage .= "\nThere is a new request for a Vacation:";
				$smsMessage .= "\nEmployee: " . $vacationInfo[0]["name"];
				$smsMessage .= "\nDate start: " . $vacationInfo[0]["date_start"];
				$smsMessage .= "\nDate end: " . $vacationInfo[0]["date_end"];
				$smsMessage .= "\nObservation: " . $vacationInfo[0]["observation"];
				$smsMessage .= "\nFollow the link to review: ";

				send_notification($configuracionAlertas, $subject, $emailBody, $smsMessage, 'external/aprove_vacation', $idVacation);
			}

			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'Thank you. The ADMIN will review your request.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * List Vacation, for ADMIN
     * @since 15/08/2026
	 */
	public function newVacationList()
	{
			$data["state"] = 1;//new
			$data['vacationList'] = $this->generalModel->get_vacation(['state' => 1]);

			$data["tittle"] = "New Request";
			$data["icon"] = "fa-hand-o-right";
			return $this->render('App\Modules\Vacation\Views\admin_vacation_list', $data);
	}

    /**
     * Cargo modal - formulario aprobar vacation
     * @since 15/08/2026
     */
    public function cargarModalApproved()
	{
		$data["idVacation"] = $this->request->getPost("idVacation");
		return $this->response
					->setContentType('text/html')
					->setBody(view('App\Modules\Vacation\Views\modal_approved', $data));
    }

	/**
	 * Save approved
     * @since 15/08/2026
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
		if ($this->vacationModel->update_vacation($post)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'Information saved successfully!!');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * List Vacation, for ADMIN
     * @since 15/08/2026
	 */
	public function approvedVacationList()
	{
			$data["state"] = 2;//approved
			$data['vacationList'] = $this->generalModel->get_vacation(['state' => 2]);

			$data["tittle"] = "Approved Request";
			$data["icon"] = "fa-hand-o-up";
			return $this->render('App\Modules\Vacation\Views\admin_vacation_list', $data);
	}

	/**
	 * List Vacation, for ADMIN
     * @since 15/08/2026
	 */
	public function deniedVacationList()
	{
			$data["state"] = 3;//Denied
			$data['vacationList'] = $this->generalModel->get_vacation(['state' => 3]);

			$data["tittle"] = "Denied Request";
			$data["icon"] = "fa-hand-o-down";
			return $this->render('App\Modules\Vacation\Views\admin_vacation_list', $data);
	}

}
