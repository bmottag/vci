<?php
namespace App\Modules\Jobs\Models;

use CodeIgniter\Model;


class JobsModel extends Model
{

	protected $protectFields = false;

	/**
	 * Get activity list
	 * @since 23/2/2017
	 */
	public function get_activity_list()
	{
		$builder = $this->db->table('param_hazard H');

		$builder->select('A.id_hazard_activity, A.hazard_activity');

		$builder->join(
			'param_hazard_activity A',
			'A.id_hazard_activity = H.fk_id_hazard_activity',
			'INNER'
		);

		$builder->groupBy('A.id_hazard_activity');
		$builder->orderBy('A.hazard_activity', 'asc');

		return $builder->get()->getResultArray();
	}

	public function get_hazards_grouped($idJob)
	{
		$builder = $this->db->table('param_hazard H');

		$builder->select('
			H.id_hazard,
			H.hazard_description,
			H.solution,
			A.id_hazard_activity,
			A.hazard_activity,
			JH.id_job_hazard
		');

		$builder->join('param_hazard_activity A', 'A.id_hazard_activity = H.fk_id_hazard_activity');
		$builder->join('job_hazards JH', 'JH.fk_id_hazard = H.id_hazard AND JH.fk_id_job = '.$idJob, 'left');

		$query = $builder->get()->getResultArray();

		$result = [];

		foreach ($query as $row) {
			$result[$row['id_hazard_activity']][] = $row;
		}

		return $result;
	}

	/**
	 * Add JOB HAZARDS
	 * @since 27/11/2016
	 */
	public function add_safety_hazard(array $post = []): bool
	{
		$idJob =  $post['hddId'];
		$hazards =  $post['hazards'];
		// 🔥 DELETE hazards
		$this->db->table('job_hazards')
				->where('fk_id_job', $idJob)
				->delete();

		// 🔥 INSERT hazards
		if (!empty($hazards)) {
			$dataBatch = [];
			foreach ($hazards as $idHazard) {
				$dataBatch[] = [
					'fk_id_job' => $idJob,
					'fk_id_hazard' => (int)$idHazard
				];
			}

			return (bool) $this->db->table('job_hazards')
								->insertBatch($dataBatch);
		}

		return true;
	}

	/**
	 * Add JOB HAZARDS LOG
	 * @since 27/11/2016
	 */
	public function add_hazard_log(array $post): bool
	{
		$data = [
			'date_log' => date("Y-m-d G:i:s"),
			'fk_id_job' => $post['hddId'] ?? null,
			'fk_id_user' => session()->get("id"),
			'observation' => $post['observation'] ?? null,
		];

		$builder = $this->db->table('job_hazards_log');
		return $builder->insert($data);
	}

	/**
	 * Lista hazards logs
	 * @since 21/8/2018
	 */
	public function get_hazards_logs($arrDatos)
	{
		$builder = $this->db->table('job_hazards_log A');
		$builder->select('A.*, J.*, CONCAT(U.first_name, " " , U.last_name) name');
		$builder->join('param_jobs J', 'J.id_job = A.fk_id_job', 'INNER');
		$builder->join('user U', 'U.id_user = A.fk_id_user', 'INNER');

		if (isset($arrDatos["idJob"])) {
			$builder->where('fk_id_job', $arrDatos["idJob"]);
		}

		if (isset($arrDatos["idJobHazardLog"])) {
			$builder->where('id_job_hazard_log', $arrDatos["idJobHazardLog"]);
		}

		$builder->orderBy('date_log', 'DESC');
		return $builder->get()->getResultArray();
	}

	/**
	 * Get job hazard info
	 * @since 27/11/2017
	 */
	public function get_job_hazards_v2($idJob)
	{
		$builder = $this->db->table('job_hazards H');
		$builder->select();
		$builder->join('param_hazard PH', 'PH.id_hazard = H.fk_id_hazard', 'INNER');
		$builder->join('param_hazard_activity PA', 'PA.id_hazard_activity = PH.fk_id_hazard_activity', 'INNER');
		$builder->join('param_hazard_priority PP', 'PP.id_priority = PH.fk_id_priority', 'INNER');
		$builder->where('H.fk_id_job', $idJob);
		$builder->orderBy('PA.hazard_activity, PH.hazard_description', 'ASC');

		return $builder->get()->getResultArray();
	}


}
