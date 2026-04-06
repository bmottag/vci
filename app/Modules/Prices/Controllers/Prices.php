<?php
namespace App\Modules\Prices\Controllers;

use App\Controllers\BaseController;
use App\Modules\Prices\Models\PricesModel;
use App\Models\GeneralModel;

class Prices extends BaseController
{
    protected $pricesModel;
    protected $generalModel;
    
    public function __construct()
    {
        $this->pricesModel   = new PricesModel();
        $this->generalModel   = new GeneralModel();
    }

	/**
	 * Lista de precios para tipos de empleados
     * @since 5/11/2020
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function employeeTypeUnitPrice($idJob)
	{
		//job info
		$data['jobInfo'] = $this->generalModel->get_job(["idJob" => $idJob]);
		
		//job_employee_type_unit_price list
		$data['employeeTypeUnitPrice'] = $this->generalModel->get_job_employee_type_unit_price($idJob);

		return $this->render('App\Modules\Prices\Views\employeeTypeUnitPrice_list', $data);
	}

	/**
	 * Load employee types
     * @since 5/11/2020
     * @author BMOTTAG
	 * @review 03/04/2026 - new CI4 version
	 */
	public function load_employee_type()
	{
		$data = [];

		$idJob = $this->request->getPost("identificador");
		$data["idJob"] = $idJob;

		$arrParam = array(
			"table" => "param_employee_type",
			"order" => "employee_type",
			"id" => "x"
		);
		$employeeTypeUnitPrice = $this->generalModel->get_basic_search($arrParam);

		if ($this->pricesModel->add_employee_type($idJob, $employeeTypeUnitPrice)) {
			$data["status"] = "success";
			session()->setFlashdata('retornoExito', 'You have loaded the data.');
		} else {
			$data["status"] = "error";
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}


}
