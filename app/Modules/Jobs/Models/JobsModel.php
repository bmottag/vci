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

	/**
	 * Add tool box
	 * @since 24/10/2017
	 */
	public function add_TOOLBOX(array $post)
	{
		$id = $post['hddIdentificador'] ?? null;

		$data = [
			'new_safety' => $post['newSafety'] ?? null,
			'activities' => $post['activities'] ?? null,
			'suggestions' => $post['suggestions'] ?? null,
			'corrective_actions' => $post['correctiveActions'] ?? null
		];

		//solo usuarios SUPER_ADMIN pueden ingresar la fecha de la inspeccion
		$userRol = session()->get('rol');
		$dateIssue = $post['date'] ?? null;

		$builder = $this->db->table('tool_box');

		if (empty($id)) {
			$data['fk_id_user'] = session()->get('id');
			$data['fk_id_job'] = $post['hddIdJob'];

			//solo usuarios SUPER_ADMIN pueden ingresar la fecha de la inspeccion
			$data['date_tool_box'] = date("Y-m-d G:i:s");
			if ($userRol == 99 && $dateIssue != "") {
				$data['date_tool_box'] = $dateIssue;
			}
			if ($builder->insert($data)) {
				return $this->db->insertID();
			}
			return false;
		} else {
			if ($userRol == 99 && $dateIssue != "") {
				$data['date_tool_box'] = $dateIssue;
			}
			$update = $builder->where('id_tool_box', $id)
							->update($data);

			return $update ? $id : false;
		}
	}

	/**
	 * Get tool box new hazards info
	 * @since 25/10/2017
	 */
	public function get_new_hazards($idToolBox)
	{
		$builder = $this->db->table('tool_box_new_hazard W');
		$builder->select();
		$builder->where('W.fk_id_tool_box', $idToolBox);
		$builder->orderBy('W.hazard', 'ASC');

		return $builder->get()->getResultArray();
	}

	/**
	 * Get tool box workers info
	 * @since 2/11/2017
	 */
	public function get_tool_box_workers($idToolBox)
	{
		$builder = $this->db->table('tool_box_workers W');
		$builder->select("W.id_tool_box_worker, W.fk_id_tool_box, W.signature, CONCAT(first_name, ' ', last_name) name");
		$builder->join('user U', 'U.id_user = W.fk_id_user', 'INNER');
		$builder->where('W.fk_id_tool_box', $idToolBox);
		$builder->orderBy('U.first_name, U.last_name', 'ASC');

		return $builder->get()->getResultArray();
	}

	/**
	 * Get tool box subcontractor workers info
	 * @since 26/2/2018
	 */
	public function get_tool_box_subcontractors_workers($idToolBox)
	{
		$builder = $this->db->table('tool_box_workers_subcontractor W');
		$builder->select();
		$builder->join('param_company C', 'C.id_company = W.fk_id_company', 'INNER');
		$builder->where('W.fk_id_tool_box', $idToolBox);
		$builder->orderBy('C.company_name, W.worker_name', 'ASC');

		return $builder->get()->getResultArray();
	}

	/**
	 * Add new hazard
	 * @since 25/10/2017
	 */
	public function saveNewHazard($post)
	{
		$data = [
			'fk_id_tool_box' => $post['hddidToolBox'] ?? null,
			'hazard' => $post['hazard'] ?? null,
			'hazard_type' => $post['hazardType'] ?? null,
			'actions' => $post['actions'] ?? null
		];

		$builder = $this->db->table('tool_box_new_hazard');
		return $builder->insert($data);
	}

	/**
	 * Update NEW HAZARD
	 * @since 25/10/2017
	 */
	public function updateNewHazard($post)
	{
		$id = $post['hddIdNewHazard'] ?? null;

		$data = [
			'hazard' => $post['hazard'] ?? null,
			'hazard_type' => $post['hazardType'] ?? null,
			'actions' => $post['actions'] ?? null,
		];

		$builder = $this->db->table('tool_box_new_hazard');

		return $builder->where('id_new_hazard', $id)
						->update($data);
	}

	/**
	 * @author BMOTTAG
	 * @since 2/11/2017
	 * Consulta de empleados para un tool box especifico
	 */
	public function get_selected_workers_toolbox($idToolBox)
	{
		return $this->db->table('tool_box_workers')
			->select('fk_id_user')
			->where('fk_id_tool_box', $idToolBox)
			->get()
			->getResultArray();
	}

	/**
	 * Add TOOL BOX WORKER
	 * @since 2/11/2017
	 */
	public function add_tool_box_worker(array $post = []): bool
	{
		$idToolBox =  $post['hddIdToolBox'];
		$workers =  $post['workers'];

		// 🔥 INSERT hazards
		if (!empty($workers)) {
			$dataBatch = [];
			foreach ($workers as $idWorker) {
				$dataBatch[] = [
					'fk_id_tool_box' => (int)$idToolBox,
					'fk_id_user' => (int)$idWorker
				];
			}

			return (bool) $this->db->table('tool_box_workers')
								->insertBatch($dataBatch);
		}

		return true;
	}

	/**
	 * Save one worker TOOL BOX
	 * @since 8/5/2018
	 */
	public function toolBoxSaveOneWorker(array $post = []): bool
	{
		$data = [
			'fk_id_tool_box' => $post['hddIdToolBox'] ?? null,
			'fk_id_user' => $post['worker'] ?? null
		];

		$builder = $this->db->table('tool_box_workers');
		return $builder->insert($data);
	}

	/**
	 * Save subcontractor worker
	 * @since 26/2/2018
	 */
	public function saveSubcontractorWorker(array $post = []): bool
	{
		$data = [
			'fk_id_tool_box' => $post['hddIdToolBox'] ?? null,
			'fk_id_company' => $post['company'] ?? null,
			'worker_name' => $post['workerName'] ?? null
		];

		$builder = $this->db->table('tool_box_workers_subcontractor');
		return $builder->insert($data);
	}



}
