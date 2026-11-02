<?php
namespace App\Modules\Payroll\Models;

use CodeIgniter\Model;

class PayrollModel extends Model
{
	protected $protectFields = false;

	/**
	 * Add PAYROLL
	 * @since 9/11/2016
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function savePayroll(array $post): bool
	{
		$idUser          = session()->get('id');
		$idOperation     = $post['hddTask'] ?? null;
		$idJob           = $post['jobName'] ?? null;
		$task            = $post['taskDescription'] ?? '';
		$latitude        = $post['latitud'] ?? 0;
		$longitude       = $post['longitud'] ?? 0;
		$fk_id_programming = $post['programming'] ?? null;
		$address         = $post['address'] ?? '';
		$fecha           = date('Y-m-d G:i:s');

		if ($fk_id_programming) {
			$progRow = $this->db->table('programming')
				->select('fk_id_job, fk_id_workorder')
				->where('id_programming', $fk_id_programming)
				->get()->getRowArray();

			if (!$progRow || $progRow['fk_id_job'] != $idJob) {
				$fk_id_programming = null;
			}
		}

		$data = [
			'fk_id_user'       => $idUser,
			'fk_id_operation'  => $idOperation,
			'fk_id_job'        => $idJob,
			'task_description' => $task,
			'start'            => $fecha,
			'latitude_start'   => $latitude,
			'longitude_start'  => $longitude,
			'address_start'    => $address,
		];

		if ($fk_id_programming) {
			$data['fk_id_programming'] = $fk_id_programming;
		}

		return $this->db->table('task')->insert($data);
	}

	/**
	 * Update PAYROLL - working time and working hours
	 * param $adminUpdate: 'x' = employee closing shift, 1 = admin updating
	 * @since 17/11/2016
	 * @review 2/02/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function updateWorkingTimePayroll(string $fechaStart, string $fechaCierre, array $post, $adminUpdate = 'x'): bool
	{
		$dteStart    = new \DateTime($fechaStart);
		$dteEnd      = new \DateTime($fechaCierre);
		$dteDiff     = $dteStart->diff($dteEnd);
		$workingTime = $dteDiff->format('%R%a days %H:%I:%S');

		$workingHours  = calculate_time_difference_in_hours($fechaStart, $fechaCierre);
		$overtimeHours = 0;
		if ($workingHours > 8) {
			$regularHours  = 8;
			$overtimeHours = $workingHours - 8;
		} else {
			$regularHours = $workingHours;
		}

		$hours_first_project = $post['hours_first_project'] ?? 0;
		$idTask              = $post['hddIdentificador'] ?? null;

		$hours_first_project = $hours_first_project ?: 0;
		$hours_end_project   = $workingHours - $hours_first_project;

		$taskRow = $this->db->table('task')
			->select('fk_id_job')
			->where('id_task', $idTask)
			->get()->getRowArray();

		if (($post['jobName'] ?? null) == ($taskRow['fk_id_job'] ?? null)) {
			$hours_end_project = null;
		}

		$hoursNEW         = $dteDiff->h + ($dteDiff->days * 24);
		$minutesNEW       = $dteDiff->i;
		$secondsNEW       = $dteDiff->s;
		$formatNEW        = sprintf('%02d:%02d:%02d', $hoursNEW, $minutesNEW, $secondsNEW);
		$newOvertimeHours = 0;

		if ($hoursNEW >= 8) {
			$newRegularHours  = '08:00';
			$hoursNEW         = $hoursNEW - 8;
			$newOvertimeHours = sprintf('%02d:%02d:%02d', $hoursNEW, $minutesNEW, $secondsNEW);
		} else {
			$newRegularHours = $formatNEW;
		}

		if ($adminUpdate === 'x') {
			$idJob      = $post['jobName'] ?? null;
			$observation = $post['observation'] ?? '';
			$latitude   = $post['latitud'] ?? 0;
			$longitude  = $post['longitud'] ?? 0;
			$address    = $post['address'] ?? '';

			$data = [
				'observation'         => $observation,
				'finish'              => $fechaCierre,
				'fk_id_job_finish'    => $idJob,
				'latitude_finish'     => $latitude,
				'longitude_finish'    => $longitude,
				'address_finish'      => $address,
				'working_time'        => $workingTime,
				'working_hours'       => $workingHours,
				'working_hours_new'   => $formatNEW,
				'regular_hours'       => $regularHours,
				'regular_hours_new'   => $newRegularHours,
				'overtime_hours'      => $overtimeHours,
				'overtime_hours_new'  => $newOvertimeHours,
				'hours_end_project'   => $hours_end_project,
				'hours_start_project' => $hours_first_project,
			];
		} else {
			$data = [
				'working_time'        => $workingTime,
				'working_hours'       => $workingHours,
				'working_hours_new'   => $formatNEW,
				'regular_hours'       => $regularHours,
				'regular_hours_new'   => $newRegularHours,
				'overtime_hours'      => $overtimeHours,
				'overtime_hours_new'  => $newOvertimeHours,
				'hours_start_project' => $hours_first_project,
				'hours_end_project'   => $hours_end_project,
			];
		}

		/*
		 * Desactivado: ya no se asignan automaticamente las horas a la work order (workorder_personal) al cerrar el payroll.
		 * La asignacion se hace manualmente desde el Dashboard.
		 *
		$idProgramming = $post['programming'] ?? null;

		if ($idProgramming) {
			$progRow = $this->db->table('programming')
				->select('fk_id_job, fk_id_workorder')
				->where('id_programming', $idProgramming)
				->get()->getRowArray();

			$job_programming = $progRow['fk_id_job'] ?? null;
			$id_workorder    = $progRow['fk_id_workorder'] ?? null;
			$idJob           = $post['jobName'] ?? null;
			$idUser          = session()->get('id');

			if ($hours_first_project == 0) {
				if ($idJob == $job_programming && $id_workorder != null) {
					$existing = $this->db->table('workorder_personal')
						->where('fk_id_workorder', $id_workorder)
						->where('fk_id_user', $idUser)
						->get()->getRowArray();

					if ($existing) {
						if ($existing['hours'] == 0) {
							$this->db->table('workorder_personal')
								->where('fk_id_workorder', $id_workorder)
								->where('fk_id_user', $idUser)
								->update(['hours' => $workingHours]);
						}
					} else {
						$this->db->table('workorder_personal')->insert([
							'fk_id_workorder'     => $id_workorder,
							'fk_id_user'          => $idUser,
							'fk_id_employee_type' => 1,
							'hours'               => $workingHours,
						]);
					}

					$this->db->table('task')->where('id_task', $idTask)->update([
						'wo_start_project' => $id_workorder,
						'wo_end_project'   => $id_workorder,
					]);
				}
			} else {
				if ($id_workorder != null) {
					$existing = $this->db->table('workorder_personal')
						->where('fk_id_workorder', $id_workorder)
						->where('fk_id_user', $idUser)
						->get()->getRowArray();

					if ($existing && $existing['hours'] == 0) {
						$this->db->table('workorder_personal')
							->where('fk_id_workorder', $id_workorder)
							->where('fk_id_user', $idUser)
							->update(['hours' => $hours_first_project]);
					}
				}

				$this->db->table('task')->where('id_task', $idTask)->update([
					'wo_start_project' => $id_workorder,
				]);
			}
		}
		*/

		return $this->db->table('task')->where('id_task', $idTask)->update($data);
	}

	/**
	 * Update PAYROLL - from payroll_check cron
	 * @since 19/08/2025
	 * @review 30/04/2026 - new CI4 version
	 */
	public function updateWorkingTimePayrollCheck(string $fechaStart, string $fechaCierre, int $idTask): bool
	{
		$dteStart    = new \DateTime($fechaStart);
		$dteEnd      = new \DateTime($fechaCierre);
		$dteDiff     = $dteStart->diff($dteEnd);
		$workingTime = $dteDiff->format('%R%a days %H:%I:%S');

		$workingHours  = calculate_time_difference_in_hours($fechaStart, $fechaCierre);
		$overtimeHours = 0;
		if ($workingHours > 8) {
			$regularHours  = 8;
			$overtimeHours = $workingHours - 8;
		} else {
			$regularHours = $workingHours;
		}

		$hoursNEW         = $dteDiff->h + ($dteDiff->days * 24);
		$minutesNEW       = $dteDiff->i;
		$secondsNEW       = $dteDiff->s;
		$formatNEW        = sprintf('%02d:%02d:%02d', $hoursNEW, $minutesNEW, $secondsNEW);
		$newOvertimeHours = 0;

		if ($hoursNEW >= 8) {
			$newRegularHours  = '08:00';
			$hoursNEW         = $hoursNEW - 8;
			$newOvertimeHours = sprintf('%02d:%02d:%02d', $hoursNEW, $minutesNEW, $secondsNEW);
		} else {
			$newRegularHours = $formatNEW;
		}

		$observation = '********************<br><strong>Changue hour by the system, automatically.</strong><br>********************';

		return $this->db->table('task')->where('id_task', $idTask)->update([
			'observation'        => $observation,
			'finish'             => $fechaCierre,
			'working_time'       => $workingTime,
			'working_hours'      => $workingHours,
			'working_hours_new'  => $formatNEW,
			'regular_hours'      => $regularHours,
			'regular_hours_new'  => $newRegularHours,
			'overtime_hours'     => $overtimeHours,
			'overtime_hours_new' => $newOvertimeHours,
		]);
	}

	/**
	 * Get task by ID
	 * @since 17/11/2016
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function get_taskbyid(int $id)
	{
		$result = $this->db->table('task')
			->where('id_task', $id)
			->get()->getRowArray();

		return $result ?: false;
	}

	/**
	 * Update payroll hour (admin edit)
	 * @since 2/2/2018
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function savePayrollHour(array $post): bool
	{
		$idTask           = $post['hddIdentificador'] ?? null;
		$inicio           = $post['hddInicio'] ?? '';
		$fin              = $post['hddFin'] ?? '';
		$firstObservation = $post['hddObservation'] ?? '';
		$observation      = $post['observation'] ?? '';

		$moreInfo = '<strong>Change hour by SUPER ADMIN.</strong> <br>Before -> Start: ' .
			date('M j, Y - G:i', strtotime($inicio)) .
			' <br>Before -> Finish: ' . date('M j, Y - G:i', strtotime($fin));

		$observation = $firstObservation . '<br>********************<br>' . $moreInfo . '<br>' . $observation . '<br>Date: ' . date('Y-m-d G:i:s') . '<br>********************';

		$fechaStart  = ($post['start_date'] ?? '') . ' ' . ($post['start_hour'] ?? '00') . ':' . ($post['start_min'] ?? '00') . ':00';
		$fechaFinish = ($post['finish_date'] ?? '') . ' ' . ($post['finish_hour'] ?? '00') . ':' . ($post['finish_min'] ?? '00') . ':00';

		return $this->db->table('task')->where('id_task', $idTask)->update([
			'observation' => $observation,
			'finish'      => $fechaFinish,
			'start'       => $fechaStart,
		]);
	}

	/**
	 * Busca la semana (mitad del periodo) que contiene la fecha.
	 * Si hay periodos traslapados gana el mas reciente.
	 * @since 26/10/2026
	 */
	public function get_weak_period_by_date(string $date)
	{
		$row = $this->db->table('payroll_period_weaks W')
			->select('W.id_period_weak')
			->join('payroll_period P', 'P.id_period = W.fk_id_period', 'INNER')
			->where('W.date_weak_start <=', $date)
			->where('W.date_weak_finish >=', $date)
			->orderBy('P.id_period', 'DESC')
			->get(1)->getRowArray();

		return $row ? (int)$row['id_period_weak'] : false;
	}

	/**
	 * Periodos del ano con sus dos semanas y conteos de registros/paystubs
	 * @since 26/10/2026
	 */
	public function get_periods_with_weeks(int $year): array
	{
		$sql = "SELECT P.*,
					W1.id_period_weak id_weak_1, W1.date_weak_start weak_1_start, W1.date_weak_finish weak_1_finish,
					W2.id_period_weak id_weak_2, W2.date_weak_start weak_2_start, W2.date_weak_finish weak_2_finish,
					CONCAT(U.first_name, ' ', U.last_name) approved_by_name,
					(SELECT COUNT(*) FROM task T WHERE T.fk_id_weak_period IN (W1.id_period_weak, W2.id_period_weak)) tasks,
					(SELECT COUNT(*) FROM payroll_paystub S WHERE S.fk_id_period = P.id_period AND S.commit = 1) paystubs_generated
				FROM payroll_period P
				LEFT JOIN payroll_period_weaks W1 ON W1.fk_id_period = P.id_period AND W1.weak_number = 1
				LEFT JOIN payroll_period_weaks W2 ON W2.fk_id_period = P.id_period AND W2.weak_number = 2
				LEFT JOIN user U ON U.id_user = P.approved_by
				WHERE P.year_period = ?
				ORDER BY P.date_start ASC";

		return $this->db->query($sql, [$year])->getResultArray();
	}

	/**
	 * Resumen de registros de horas del ano: total, sin periodo y pagados
	 * @since 26/10/2026
	 */
	public function get_year_task_summary(int $year): array
	{
		$sql = "SELECT COUNT(*) total,
					SUM(T.fk_id_weak_period = 0 OR W.id_period_weak IS NULL) unassigned,
					SUM(T.period_status = 2) paid
				FROM task T
				LEFT JOIN payroll_period_weaks W ON W.id_period_weak = T.fk_id_weak_period
				WHERE T.start >= ? AND T.start < ?";

		return $this->db->query($sql, [$year . '-01-01', ($year + 1) . '-01-01'])->getRowArray();
	}

	/**
	 * Cantidad de paystubs generados (commit = 1) en periodos del ano
	 * @since 26/10/2026
	 */
	public function count_generated_paystubs_year(int $year): int
	{
		return $this->db->table('payroll_paystub S')
			->join('payroll_period P', 'P.id_period = S.fk_id_period', 'INNER')
			->where('P.year_period', $year)
			->where('S.commit', 1)
			->countAllResults();
	}

	/**
	 * Genera los 24 periodos semimensuales del ano (1-15 y 16-fin de mes),
	 * cada uno dividido en 2 semanas a la mitad. Si el ano ya tenia periodos
	 * se borran junto con sus semanas y paystubs en borrador, y los registros
	 * de horas quedan sin periodo (fk_id_weak_period = 0) para reasignarlos.
	 * @since 26/10/2026
	 */
	public function generateYearPeriods(int $year): bool
	{
		$this->db->transStart();

		$oldPeriods = array_column(
			$this->db->table('payroll_period')->select('id_period')->where('year_period', $year)->get()->getResultArray(),
			'id_period'
		);

		if ($oldPeriods) {
			$oldWeeks = array_column(
				$this->db->table('payroll_period_weaks')->select('id_period_weak')->whereIn('fk_id_period', $oldPeriods)->get()->getResultArray(),
				'id_period_weak'
			);
			if ($oldWeeks) {
				$this->db->table('task')->whereIn('fk_id_weak_period', $oldWeeks)->update(['fk_id_weak_period' => 0]);
			}
			$this->db->table('payroll_paystub')->whereIn('fk_id_period', $oldPeriods)->where('commit !=', 1)->delete();
			$this->db->table('payroll_period_weaks')->whereIn('fk_id_period', $oldPeriods)->delete();
			$this->db->table('payroll_period')->whereIn('id_period', $oldPeriods)->delete();
		}

		for ($month = 1; $month <= 12; $month++) {
			$lastDay = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

			foreach ([[1, 15], [16, $lastDay]] as [$dayStart, $dayFinish]) {
				$start  = sprintf('%04d-%02d-%02d', $year, $month, $dayStart);
				$finish = sprintf('%04d-%02d-%02d', $year, $month, $dayFinish);

				// Mitad estimada: la primera semana se queda con el dia extra si el periodo es impar
				$days       = $dayFinish - $dayStart + 1;
				$weak1End   = sprintf('%04d-%02d-%02d', $year, $month, $dayStart + (int)ceil($days / 2) - 1);
				$weak2Start = date('Y-m-d', strtotime('+1 day', strtotime($weak1End)));

				$this->db->table('payroll_period')->insert([
					'date_start'  => $start,
					'date_finish' => $finish,
					'period'      => $start . ' - ' . $finish,
					'year_period' => $year,
					'approved'    => 0,
				]);
				$idPeriod = $this->db->insertID();

				$this->db->table('payroll_period_weaks')->insertBatch([
					[
						'fk_id_period'     => $idPeriod,
						'date_weak_start'  => $start,
						'date_weak_finish' => $weak1End,
						'period_weak'      => $start . ' - ' . $weak1End,
						'weak_number'      => 1,
					],
					[
						'fk_id_period'     => $idPeriod,
						'date_weak_start'  => $weak2Start,
						'date_weak_finish' => $finish,
						'period_weak'      => $weak2Start . ' - ' . $finish,
						'weak_number'      => 2,
					],
				]);
			}
		}

		$this->db->transComplete();

		return $this->db->transStatus();
	}

	/**
	 * Cambia la fecha donde termina la semana 1 (la semana 2 empieza al dia siguiente)
	 * @since 26/10/2026
	 */
	public function updatePeriodWeeks(array $period, string $weak1End): bool
	{
		$weak2Start = date('Y-m-d', strtotime('+1 day', strtotime($weak1End)));

		$this->db->transStart();

		$this->db->table('payroll_period_weaks')
			->where('fk_id_period', $period['id_period'])
			->where('weak_number', 1)
			->update([
				'date_weak_finish' => $weak1End,
				'period_weak'      => $period['date_start'] . ' - ' . $weak1End,
			]);

		$this->db->table('payroll_period_weaks')
			->where('fk_id_period', $period['id_period'])
			->where('weak_number', 2)
			->update([
				'date_weak_start' => $weak2Start,
				'period_weak'     => $weak2Start . ' - ' . $period['date_finish'],
			]);

		$this->db->transComplete();

		return $this->db->transStatus();
	}

	/**
	 * Aprobar (1) o devolver a pendiente (0) un periodo
	 * @since 26/10/2026
	 */
	public function updatePeriodApproval(int $idPeriod, int $approved): bool
	{
		return $this->db->table('payroll_period')->where('id_period', $idPeriod)->update([
			'approved'      => $approved,
			'approved_by'   => $approved ? session()->get('id') : null,
			'approved_date' => $approved ? date('Y-m-d H:i:s') : null,
		]);
	}

	/**
	 * Asigna a cada registro de horas del ano la semana del periodo que contiene su fecha de inicio.
	 * No toca registros ya pagados (period_status = 2). Si se pasa $idPeriod solo reasigna
	 * los registros de ese periodo.
	 * @since 26/10/2026
	 * @return int registros actualizados
	 */
	public function assignTasksToPeriods(int $year, ?int $idPeriod = null): int
	{
		$sql = "UPDATE task T
				JOIN payroll_period_weaks W ON DATE(T.start) BETWEEN W.date_weak_start AND W.date_weak_finish
				JOIN payroll_period P ON P.id_period = W.fk_id_period AND P.year_period = ?
				SET T.fk_id_weak_period = W.id_period_weak
				WHERE T.start >= ? AND T.start < ?
				AND T.period_status <> 2";
		$binds = [$year, $year . '-01-01', ($year + 1) . '-01-01'];

		if ($idPeriod) {
			$sql    .= " AND P.id_period = ?";
			$binds[] = $idPeriod;
		}

		$this->db->query($sql, $binds);

		return $this->db->affectedRows();
	}

	/**
	 * Configuracion CPP/CPP2 por ano
	 * @since 26/10/2026
	 */
	public function get_cpp_settings(array $arrData = [])
	{
		$builder = $this->db->table('payroll_cpp_settings C');
		$builder->select("C.*, CONCAT(U.first_name, ' ', U.last_name) name");
		$builder->join('user U', 'U.id_user = C.fk_id_user', 'LEFT');

		if (array_key_exists('idCppSetting', $arrData)) {
			$builder->where('C.id_cpp_setting', $arrData['idCppSetting']);
		}
		if (array_key_exists('year', $arrData)) {
			$builder->where('C.year', $arrData['year']);
		}
		$builder->orderBy('C.year', 'DESC');

		$result = $builder->get()->getResultArray();
		return !empty($result) ? $result : false;
	}

	/**
	 * Guardar configuracion CPP/CPP2 (insert o update)
	 * @since 26/10/2026
	 */
	public function saveCppSettings(array $post): bool
	{
		$idCppSetting = $post['hddIdCppSetting'] ?? '';

		$data = [
			'year'                  => (int)$post['year'],
			'basic_exemption'       => (float)$post['basic_exemption'],
			'contribution_rate'     => (float)$post['contribution_rate'],
			'max_contribution'      => (float)$post['max_contribution'],
			'ympe'                  => (float)$post['ympe'],
			'yampe'                 => (float)$post['yampe'],
			'cpp2_rate'             => (float)$post['cpp2_rate'],
			'cpp2_max_contribution' => (float)$post['cpp2_max_contribution'],
			'fk_id_user'            => session()->get('id'),
			'date_issue'            => date('Y-m-d H:i:s'),
		];

		if (empty($idCppSetting)) {
			return $this->db->table('payroll_cpp_settings')->insert($data);
		}

		return $this->db->table('payroll_cpp_settings')->where('id_cpp_setting', $idCppSetting)->update($data);
	}

	/**
	 * Configuracion EI por ano
	 * @since 27/10/2026
	 */
	public function get_ei_settings(array $arrData = [])
	{
		$builder = $this->db->table('payroll_ei_settings E');
		$builder->select("E.*, CONCAT(U.first_name, ' ', U.last_name) name");
		$builder->join('user U', 'U.id_user = E.fk_id_user', 'LEFT');

		if (array_key_exists('idEiSetting', $arrData)) {
			$builder->where('E.id_ei_setting', $arrData['idEiSetting']);
		}
		if (array_key_exists('year', $arrData)) {
			$builder->where('E.year', $arrData['year']);
		}
		$builder->orderBy('E.year', 'DESC');

		$result = $builder->get()->getResultArray();
		return !empty($result) ? $result : false;
	}

	/**
	 * Guardar configuracion EI (insert o update)
	 * @since 27/10/2026
	 */
	/**
	 * Tablas de impuestos (Federal / Provincial) cargadas, con el rango de pago que cubren
	 * @since 27/10/2026
	 */
	public function get_tax_tables(array $arrData = [])
	{
		$builder = $this->db->table('payroll_tax_tables T');
		$builder->select("T.*, CONCAT(U.first_name, ' ', U.last_name) name, MIN(R.pay_from) min_pay, MAX(R.pay_less_than) max_pay");
		$builder->join('user U', 'U.id_user = T.fk_id_user', 'LEFT');
		$builder->join('payroll_tax_table_rows R', 'R.fk_id_tax_table = T.id_tax_table', 'LEFT');

		if (array_key_exists('year', $arrData)) {
			$builder->where('T.year', $arrData['year']);
		}
		if (array_key_exists('tableType', $arrData)) {
			$builder->where('T.table_type', $arrData['tableType']);
		}
		$builder->groupBy('T.id_tax_table');
		$builder->orderBy('T.year', 'DESC');
		$builder->orderBy('T.table_type', 'ASC');

		$result = $builder->get()->getResultArray();
		return !empty($result) ? $result : false;
	}

	/**
	 * Fila de la tabla de impuestos donde cae el pago: pay_from <= gross < pay_less_than
	 * @since 27/10/2026
	 * @return array|null
	 */
	public function get_tax_table_row(int $year, string $tableType, float $gross)
	{
		return $this->db->table('payroll_tax_table_rows R')
			->select('R.*')
			->join('payroll_tax_tables T', 'T.id_tax_table = R.fk_id_tax_table', 'INNER')
			->where('T.year', $year)
			->where('T.table_type', $tableType)
			->where('R.pay_from <=', $gross)
			->where('R.pay_less_than >', $gross)
			->get()->getRowArray();
	}

	/**
	 * Guardar tabla de impuestos de un ano: reemplaza la anterior del mismo tipo
	 * @since 27/10/2026
	 */
	public function saveTaxTable(int $year, string $tableType, string $fileName, array $rows): bool
	{
		$this->db->transStart();

		$this->db->table('payroll_tax_tables')->where('year', $year)->where('table_type', $tableType)->delete();
		$this->db->table('payroll_tax_tables')->insert([
			'year'       => $year,
			'table_type' => $tableType,
			'file_name'  => $fileName,
			'total_rows' => count($rows),
			'fk_id_user' => session()->get('id'),
			'date_issue' => date('Y-m-d H:i:s'),
		]);
		$idTaxTable = $this->db->insertID();

		foreach (array_chunk($rows, 200) as $chunk) {
			$this->db->table('payroll_tax_table_rows')->insertBatch(array_map(fn($row) => ['fk_id_tax_table' => $idTaxTable] + $row, $chunk));
		}

		$this->db->transComplete();
		return $this->db->transStatus();
	}

	public function saveEiSettings(array $post): bool
	{
		$idEiSetting = $post['hddIdEiSetting'] ?? '';

		$data = [
			'year'                   => (int)$post['year'],
			'max_insurable_earnings' => (float)$post['max_insurable_earnings'],
			'rate'                   => (float)$post['rate'],
			'fk_id_user'             => session()->get('id'),
			'date_issue'             => date('Y-m-d H:i:s'),
		];

		if (empty($idEiSetting)) {
			return $this->db->table('payroll_ei_settings')->insert($data);
		}

		return $this->db->table('payroll_ei_settings')->where('id_ei_setting', $idEiSetting)->update($data);
	}

	/**
	 * Save paystub
	 * @since 21/2/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function savePaystub(array $post): bool
	{
		$idUser            = session()->get('id');
		$idPaytsub         = $post['hddIdPaytsub'] ?? '';
		$costRegularSalary = (float)($post['hddCostRegularSalary'] ?? 0);
		$costOvertime      = (float)($post['hddCostOvertime'] ?? 0);
		$costVacation      = (float)($post['hddCostVacation'] ?? 0);
		$totalIncome       = $costRegularSalary + $costOvertime;
		$grossCalculated   = $totalIncome + $costVacation;
		// Gross Salary confirmado con QuickBooks (lo envia save_paystub); sin el se usa el calculado
		$gross_salary      = isset($post['gross_salary']) ? (float)$post['gross_salary'] : $grossCalculated;
		$ee_cpp            = (float)($post['ee_cpp'] ?? 0);
		$er_cpp            = $ee_cpp;
		$ee_ei             = (float)($post['ee_ei'] ?? 0);
		$ee_cpp2           = (float)($post['ee_cpp2'] ?? 0);
		$er_cpp2           = $ee_cpp2;
		$er_ei             = round($ee_ei * 1.4, 2);
		$tax               = (float)($post['tax'] ?? 0);
		$gwl_deductions    = (float)($post['gwl_deductions'] ?? 0);
		$ee_total_taxes    = $ee_cpp + $ee_cpp2 + $ee_ei + $tax;
		$remittance        = $ee_cpp + $er_cpp + $ee_cpp2 + $er_cpp2 + $ee_ei + $er_ei + $tax;
		$net_pay           = $gross_salary - $ee_total_taxes - $gwl_deductions;
		$btnGenerate       = $post['btnGenerate'] ?? null;
		$valueCommit       = isset($btnGenerate) ? 1 : 2;

		$data = [
			'employee_rate_paystub'        => $post['hddEmployeeRate'] ?? null,
			'employee_type_paystub'        => $post['hddEmployeeType'] ?? null,
			'total_worked_hours'           => $post['hddTotalWorkedHours'] ?? 0,
			'total_regular_hours'          => $post['hddRegularHours'] ?? 0,
			'total_overtime_hours'         => $post['hddOvertimeHours'] ?? 0,
			'cost_regular_salary'          => $costRegularSalary,
			'cost_over_time'               => $costOvertime,
			'total_income'                 => $totalIncome,
			'cost_vacation_regular_salary' => $costVacation,
			'gross_salary'                 => $gross_salary,
			'gross_salary_calculated'      => $grossCalculated,
			'quickbooks_check'             => (int)($post['quickbooks_check'] ?? 0),
			'ee_cpp'                       => $ee_cpp,
			'er_cpp'                       => $er_cpp,
			'ee_cpp2'                      => $ee_cpp2,
			'er_cpp2'                      => $er_cpp2,
			'ee_ei'                        => $ee_ei,
			'er_ei'                        => $er_ei,
			'tax'                          => $tax,
			'claim_code'                   => isset($post['claim_code']) ? (int)$post['claim_code'] : null,
			'federal_tax'                  => (float)($post['federal_tax'] ?? 0),
			'provincial_tax'               => (float)($post['provincial_tax'] ?? 0),
			'ee_total_taxes'               => $ee_total_taxes,
			'gwl_deductions'               => $gwl_deductions,
			'remittance'                   => $remittance,
			'net_pay'                      => $net_pay,
			'commit'                       => $valueCommit,
		];

		if (empty($idPaytsub)) {
			$data['paystub_date_issue']       = date('Y-m-d');
			$data['paystub_fk_id_user']       = $idUser;
			$data['fk_id_period']             = $post['hddIdPeriod'] ?? null;
			$data['fk_id_employee']           = $post['hddIdUser'] ?? null;
			$data['actual_bank_time_balance'] = $post['hddBankTimeBalance'] ?? null;
			return $this->db->table('payroll_paystub')->insert($data);
		} else {
			return $this->db->table('payroll_paystub')->where('id_paystub', $idPaytsub)->update($data);
		}
	}

	/**
	 * Update task status to paid
	 * @since 27/2/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function updateTaskStatus(array $post): bool
	{
		$idEmployee = $post['hddIdUser'] ?? null;
		$idWeak1    = $post['hddIdWeakPeriod1'] ?? null;
		$idWeak2    = $post['hddIdWeakPeriod2'] ?? null;

		return $this->db->table('task')
			->where('fk_id_user', $idEmployee)
			->whereIn('fk_id_weak_period', [$idWeak1, $idWeak2])
			->update(['period_status' => 2]);
	}

	/**
	 * Update payroll totals by year
	 * @since 27/2/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function updatePayrollTotalYearly(array $post, $arrData): bool
	{
		$idTotalYearly     = $post['hddIdTotalYearly'] ?? '';
		$workedHours       = (float)($post['hddTotalWorkedHours'] ?? 0);
		$regularHours      = (float)($post['hddRegularHours'] ?? 0);
		$overtimeHours     = (float)($post['hddOvertimeHours'] ?? 0);
		$costRegularSalary = (float)($post['hddCostRegularSalary'] ?? 0);
		$costOvertime      = (float)($post['hddCostOvertime'] ?? 0);
		$costVacation      = (float)($post['hddCostVacation'] ?? 0);
		$totalIncome       = $costRegularSalary + $costOvertime;
		$gross_salary      = isset($post['gross_salary']) ? (float)$post['gross_salary'] : $totalIncome + $costVacation;
		$ee_cpp            = (float)($post['ee_cpp'] ?? 0);
		$er_cpp            = $ee_cpp;
		$ee_ei             = (float)($post['ee_ei'] ?? 0);
		$ee_cpp2           = (float)($post['ee_cpp2'] ?? 0);
		$er_cpp2           = $ee_cpp2;
		$er_ei             = round($ee_ei * 1.4, 2);
		$tax               = (float)($post['tax'] ?? 0);
		$gwl_deductions    = (float)($post['gwl_deductions'] ?? 0);
		$ee_total_taxes    = $ee_cpp + $ee_cpp2 + $ee_ei + $tax;
		$remittance        = $ee_cpp + $er_cpp + $ee_cpp2 + $er_cpp2 + $ee_ei + $er_ei + $tax;
		$net_pay           = $gross_salary - $ee_total_taxes - $gwl_deductions;

		if ($arrData) {
			$row  = $arrData[0];
			$data = [
				'total_year_worked_hours'                 => $workedHours + $row['total_year_worked_hours'],
				'total_year_regular_hours'                => $regularHours + $row['total_year_regular_hours'],
				'total_year_overtime_hours'               => $overtimeHours + $row['total_year_overtime_hours'],
				'total_year_cost_regular_salary'          => $costRegularSalary + $row['total_year_cost_regular_salary'],
				'total_year_cost_over_time'               => $costOvertime + $row['total_year_cost_over_time'],
				'total_year_cost_vacation_regular_salary' => $costVacation + $row['total_year_cost_vacation_regular_salary'],
				'total_year_gross_salary'                 => $gross_salary + $row['total_year_gross_salary'],
				'total_year_ee_cpp'                       => $ee_cpp + $row['total_year_ee_cpp'],
				'total_year_er_cpp'                       => $er_cpp + $row['total_year_er_cpp'],
				'total_year_ee_cpp2'                      => $ee_cpp2 + $row['total_year_ee_cpp2'],
				'total_year_er_cpp2'                      => $er_cpp2 + $row['total_year_er_cpp2'],
				'total_year_ee_ei'                        => $ee_ei + $row['total_year_ee_ei'],
				'total_year_er_ei'                        => $er_ei + $row['total_year_er_ei'],
				'total_year_tax'                          => $tax + $row['total_year_tax'],
				'total_year_ee_total_taxes'               => $ee_total_taxes + $row['total_year_ee_total_taxes'],
				'total_year_gwl_deductions'               => $gwl_deductions + $row['total_year_gwl_deductions'],
				'total_year_remittance'                   => $remittance + $row['total_year_remittance'],
				'total_year_net_pay'                      => $net_pay + $row['total_year_net_pay'],
			];
		} else {
			$data = [
				'total_year_worked_hours'                 => $workedHours,
				'total_year_regular_hours'                => $regularHours,
				'total_year_overtime_hours'               => $overtimeHours,
				'total_year_cost_regular_salary'          => $costRegularSalary,
				'total_year_cost_over_time'               => $costOvertime,
				'total_year_cost_vacation_regular_salary' => $costVacation,
				'total_year_gross_salary'                 => $gross_salary,
				'total_year_ee_cpp'                       => $ee_cpp,
				'total_year_er_cpp'                       => $er_cpp,
				'total_year_ee_cpp2'                      => $ee_cpp2,
				'total_year_er_cpp2'                      => $er_cpp2,
				'total_year_ee_ei'                        => $ee_ei,
				'total_year_er_ei'                        => $er_ei,
				'total_year_tax'                          => $tax,
				'total_year_ee_total_taxes'               => $ee_total_taxes,
				'total_year_gwl_deductions'               => $gwl_deductions,
				'total_year_remittance'                   => $remittance,
				'total_year_net_pay'                      => $net_pay,
			];
		}

		if (empty($idTotalYearly)) {
			$data['year']           = $post['hddYear'] ?? null;
			$data['fk_id_employee'] = $post['hddIdUser'] ?? null;
			return $this->db->table('payroll_total_yearly')->insert($data);
		} else {
			return $this->db->table('payroll_total_yearly')->where('id_total_yearly', $idTotalYearly)->update($data);
		}
	}

	/**
	 * In-progress service order
	 * @since 1/7/2023
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function get_in_progress_service_order()
	{
		$result = $this->db->table('service_order S')
			->select('T.*')
			->join('service_order_time T', 'T.fk_id_service_order = S.id_service_order', 'left')
			->where('S.service_status', 'in_progress_so')
			->where('S.fk_id_assign_to', 1)
			->get()->getRowArray();

		return $result ?: false;
	}

	/**
	 * Update SERVICE ORDER TIME
	 * @since 1/7/2023
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function updateServiceOrderTime(array $infoServiceOrder, string $path): bool
	{
		$date = date('Y-m-d G:i:s');

		if ($path === 'start') {
			$data = ['time_date' => $date];
		} else {
			$minutes = abs(strtotime($infoServiceOrder['time_date']) - strtotime($date)) / 60;
			$hours   = round($minutes / 60, 2) + $infoServiceOrder['time'];
			$data    = ['time' => $hours];
		}

		return $this->db->table('service_order_time')
			->where('id_time', $infoServiceOrder['id_time'])
			->update($data);
	}

	/**
	 * Update TASK with WO job codes and hours
	 * @since 17/02/2025
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function updateTaskWithWO(array $post): bool
	{
		$idTask = $post['hddIdentificador'] ?? null;

		$data = [
			'fk_id_job'           => $post['jobName'] ?? null,
			'hours_start_project' => $post['hours_first_project'] ?? 0,
			'fk_id_job_finish'    => $post['jobNameFinish'] ?? null,
			'hours_end_project'   => $post['hours_last_project'] ?? 0,
		];

		$this->db->table('task')->where('id_task', $idTask)->update($data);

		$task = $this->get_taskbyid((int)$idTask);

		if ($task && $task['wo_start_project']) {
			$this->db->table('workorder_personal')
				->where('fk_id_workorder', $task['wo_start_project'])
				->where('fk_id_user', $task['fk_id_user'])
				->update(['hours' => $data['hours_start_project']]);
		}

		if ($task && $task['wo_end_project']) {
			$this->db->table('workorder_personal')
				->where('fk_id_workorder', $task['wo_end_project'])
				->where('fk_id_user', $task['fk_id_user'])
				->update(['hours' => $data['hours_end_project']]);
		}

		return true;
	}
}
