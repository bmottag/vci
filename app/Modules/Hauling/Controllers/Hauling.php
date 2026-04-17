<?php
namespace App\Modules\Hauling\Controllers;

use App\Controllers\BaseController;
use App\Modules\Hauling\Models\HaulingModel;
use App\Models\GeneralModel;

class Hauling extends BaseController
{
    protected $haulingModel;
    protected $generalModel;
    
    public function __construct()
    {
        $this->haulingModel   = new HaulingModel();
        $this->generalModel   = new GeneralModel();
    }

	/**
	 * Form Add Hauling
	 * @since 11/12/2016
	 * @author BMOTTAG
	 * @review 16/04/2026 - new CI4 version
	 */
	public function add_hauling($id = 'x')
	{
		$data = [
			'information' => null,
			'HaulingClose' => false,
			'companyList' => $this->generalModel->get_company(["isHauling" => true]),
			'truckTypeList' => $this->generalModel->get_basic_search([
				"table" => "param_truck_type",
				"order" => "truck_type",
				"id" => "x"
			]),
			'materialTypeList' => $this->generalModel->get_basic_search([
				"table" => "param_material_type",
				"order" => "material",
				"id" => "x"
			]),
			'jobs' => $this->generalModel->get_job(['state' => 1]),
			'paymentList' => $this->generalModel->get_basic_search([
				"table" => "param_payment",
				"order" => "payment",
				"id" => "x"
			]),
			'truckList' => $this->generalModel->get_basic_search([
				"table" => "param_vehicle",
				"order" => "unit_number",
				"id" => "x"
			]),
		];

		if ($id != 'x') {

			$data['information'] = $this->haulingModel->get_hauling_byId($id);

			if (!$data['information']) {
				throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
			}

			$idWorkorder = $data['information']['fk_id_workorder'];

			$workorder = $this->generalModel->get_basic_search([
				"table" => "workorder",
				"order" => "id_workorder",
				"column" => "id_workorder",
				"id" => $idWorkorder
			]);

			$data['workorder'] = $workorder
				? $workorder[0]['id_workorder'] . ' - ' . $workorder[0]['observation']
				: 'Not Work Order';
		}

		return $this->render('App\Modules\Hauling\Views\form_add_hauling', $data);
	}

	/**
	 * Save hauling
	 * @since 16/12/2016
	 * @author BMOTTAG
	 * @review 16/04/2026 - new CI4 version
	 */
	public function save_menu()
	{
		$post = $this->request->getPost();

		$data = [];
		if ($this->haulingModel->saveHauling($post)) {
			$data["idHauling"] = $idHauling;
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have saved your hauling record, remember to sign and get the contractor signature!!');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Company list
	 * @since 4/2/2017
	 * @author BMOTTAG
	 * @review 16/04/2026 - new CI4 version
	 */
	public function companyList()
	{
		$CompanyType = $this->request->getPost('CompanyType');

		//company list
		$arrParam = array(
			"table" => "param_company",
			"order" => "company_name",
			"column" => "company_type",
			"id" => $CompanyType
		);
		$lista = $this->generalModel->get_basic_search($arrParam); //company list

		echo "<option value=''>Select...</option>";
		if ($lista) {
			foreach ($lista as $fila) {
				echo "<option value='" . $fila["id_company"] . "' >" . $fila["company_name"] . "</option>";
			}
		}
	}

	/**
	 * Trucks´list by company
	 * @since 12/12/2016
	 * @author BMOTTAG
	 * @review 16/04/2026 - new CI4 version
	 */
	public function truckList()
	{
		$identificador = $this->request->getPost('identificador');
		$lista = $this->haulingModel->get_trucks_by_id($identificador);
		echo "<option value=''>Select...</option>";
		if ($lista) {
			foreach ($lista as $fila) {
				echo "<option value='" . $fila["id_truck"] . "' >" . $fila["unit_number"] . "</option>";
			}
		}
	}

	/**
	 * Save hauling
	 * @since 16/12/2016
	 * @author BMOTTAG
	 * @review 16/04/2026 - new CI4 version
	 */
	public function save_hauling()
	{
		$post = $this->request->getPost();

		$data = [];
		if ($idHauling = $this->haulingModel->saveHauling($post)) {
			$data["idHauling"] = $idHauling;
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have saved your hauling record, remember to sign and get the contractor signature!!');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Work Order list
	 * @since 9/1/2025
	 * @author BMOTTAG
	 * @review 16/04/2026 - new CI4 version
	 */
	public function list_by_job_code()
	{
		$jobCode = $this->request->getPost('jobCode');
		$id_workorder = $this->haulingModel->list_by_job_code($jobCode);

		echo $id_workorder;
	}

	/**
	 * Work Order list
	 * @since 9/1/2025
	 * @author BMOTTAG
	 * @review 16/04/2026 - new CI4 version
	 */
	public function woList()
	{
		$jobCode = $this->request->getPost('jobCode');
		$list = $this->haulingModel->get_wo_job_code($jobCode);
		echo "<option value=''>Select...</option>";
		if ($list) {
			foreach ($list as $fila) {
				echo "<option value='" . $fila["id_workorder"] . "' >" . $fila["id_workorder"] . " - " . $fila["observation"] . "</option>";
			}
		}
	}


}
