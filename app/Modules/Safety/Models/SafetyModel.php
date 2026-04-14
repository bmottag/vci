<?php
namespace App\Modules\Safety\Models;

use CodeIgniter\Model;


class SafetyModel extends Model
{

	protected $protectFields = false;

	/**
	 * Payroll
	 * @since 4/02/2017
	 */
	public function get_safety_by_id($idSafety)
	{
		$builder = $this->db->table('safety S');

		$builder->select("S.*, CONCAT(first_name, ' ', last_name) name, J.job_description");
		$builder->join('user U', 'U.id_user = S.fk_id_user', 'INNER');
		$builder->join('param_jobs J', 'J.id_job = S.fk_id_job', 'INNER');
		
		$builder->where('S.id_safety', $idSafety);
		
		$builder->orderBy('S.date', 'asc');

		return $builder->get()->getResultArray();
	}

	/**
	 * Add SAFETY
	 * @since 3/12/2016
	 */
	public function add_safety(array $post)
	{
		$id = $post['hddIdentificador'] ?? null;

		$data = [
			'fk_id_operation' => $post['hddTask'] ?? null,
			'work' => $post['work'] ?? null,
			'fk_id_job' => $post['hddIdJob'] ?? null,
			'muster_point' => $post['musterPoint'] ?? null,
			'muster_point_2' => $post['musterPoint2'] ?? null,
			'primary_head_counter' => $post['primaryHeadCounter'] ?? null,
			'secondary_head_counter' => $post['secondaryHeadCounter'] ?? null,
			'specify_ppe' => $post['specify'] ?? null,
		];

		$ppe = $post['hddTask'] ?? null; // 1: con ppe; 2: sin ppe

		if(!empty($ppe)) {
			if($ppe == 'on') {
				$data['ppe'] = 1;
			} else if($ppe == 'off') {
				unset($data['ppe']);
			}
		} else {
			$data['ppe'] = 2;
		}

		$builder = $this->db->table('safety');

		if (empty($id)) {
			$data['fk_id_user'] = session()->get("id"); //solo se ingresa el usuario cuando se crea
			$data['date'] = date("Y-m-d G:i:s");//fecha del registro
			if ($builder->insert($data)) {
				return $this->db->insertID();
			}
			return false;
		} else {
			$update = $builder->where('id_safety', $id)
							->update($data);

			return $update ? $id : false;
		}
	}

	/**
	 * Get safety hazard info
	 * @since 4/12/2016
	 */
	public function get_safety_hazard($idSafety) 
	{		
		$builder = $this->db->table('safety_hazards H');
		$builder->select();
		$builder->join('param_hazard PH', 'PH.id_hazard = H.fk_id_hazard', 'INNER');
		$builder->join('param_hazard_activity PA', 'PA.id_hazard_activity = PH.fk_id_hazard_activity', 'INNER');
		$builder->join('param_hazard_priority PP', 'PP.id_priority = PH.fk_id_priority', 'INNER');
		$builder->where('H.fk_id_safety', $idSafety); 
		$builder->orderBy('PA.id_hazard_activity, PH.hazard_description', 'asc');
		return $builder->get()->getResultArray();
	}



}
