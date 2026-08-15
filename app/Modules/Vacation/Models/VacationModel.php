<?php
namespace App\Modules\Vacation\Models;

use CodeIgniter\Model;

class VacationModel extends Model
{

	protected $protectFields = false;

	/**
	 * Whether the user already has a Pending or Approved vacation
	 * overlapping the given range
	 * @since 15/08/2026
	 */
	public function has_overlap(array $post): bool
	{
		$idUser = session()->get("id");

		$builder = $this->db->table('vacation');
		$builder->where('fk_id_user', $idUser);
		$builder->whereIn('state', [1, 2]);
		$builder->where('date_start <=', $post['date_end']);
		$builder->where('date_end >=', $post['date_start']);

		return $builder->countAllResults() > 0;
	}

	/**
	 * Add vacation
	 * @since 15/08/2026
	 */
	public function add_vacation(array $post)
	{
		$idUser = session()->get("id");

		// Validación básica
		if (empty($post['date_start']) || empty($post['date_end'])) {
			return false;
		}

		$data = [
			'fk_id_user'  => $idUser,
			'date_issue'  => date("Y-m-d H:i:s"),
			'date_start'  => $post['date_start'],
			'date_end'    => $post['date_end'],
			'observation' => $post['observation'] ?? null,
			'state'       => 1,
		];

		$builder = $this->db->table('vacation');
		$result = $builder->insert($data);

		return $result ? $this->db->insertID() : false;
	}

	/**
	 * Update vacation´s state
	 * @since 15/08/2026
	 */
	public function update_vacation(array $post)
	{
		$idUser = session()->get("id");

		// Validación básica
		if (empty($post['hddIdParam']) || !isset($post['state'])) {
			return false;
		}

		$idVacation  = (int) $post['hddIdParam'];
		$state       = (int) $post['state'];
		$observation = $post['observation'] ?? null;

		$data = [
			'fk_id_boss'        => $idUser,
			'state'             => $state,
			'admin_observation' => $observation,
			'date_update'       => date("Y-m-d H:i:s"),
		];

		$builder = $this->db->table('vacation');
		$builder->where('id_vacation', $idVacation);

		return $builder->update($data);
	}

}
