<?php
namespace App\Modules\Prices\Models;

use CodeIgniter\Model;


class PricesModel extends Model
{

	protected $protectFields = false;

	/**
	 * Add JOB EMPLOYEE TYPE PRICES
	 * @since 5/11/2020
	 */
	public function add_employee_type($idJob, $employeeTypeList)
	{
		$db = \Config\Database::connect();
		$db->transStart();

		$db->table('job_employee_type_price')
		->where('fk_id_job', $idJob)
		->delete();

		if (!empty($employeeTypeList)) {

			$dataBatch = [];

			foreach ($employeeTypeList as $item) {
				$dataBatch[] = [
					'fk_id_job' => $idJob,
					'fk_id_employee_type' => $item['id_employee_type'],
					'job_employee_type_unit_price' => $item['employee_type_unit_price']
				];
			}

			$db->table('job_employee_type_price')->insertBatch($dataBatch);
		}

		$db->transComplete();

		return $db->transStatus();
	}





}
