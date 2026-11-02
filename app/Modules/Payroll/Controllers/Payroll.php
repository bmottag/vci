<?php
namespace App\Modules\Payroll\Controllers;

use App\Controllers\BaseController;
use App\Modules\Payroll\Models\PayrollModel;
use App\Models\GeneralModel;
use App\Libraries\PdfBuilder;
use App\Libraries\CppCalculator;
use App\Libraries\EiCalculator;
use App\Libraries\TaxTableCalculator;
use InvalidArgumentException;

class Payroll extends BaseController
{
	protected $payrollModel;
	protected $generalModel;

	public function __construct()
	{
		$this->payrollModel = new PayrollModel();
		$this->generalModel = new GeneralModel();
	}

	/**
	 * Form Add Payroll
	 * @since 09/11/2016
	 * @author BMOTTAG
	 * @review 01/05/2026 - new CI4 version
	 */
	public function add_payroll($id = 'x')
	{
		$idUser = $this->session->get('id');

		$sql = "SELECT p.fk_id_job, p.id_programming
				FROM programming_worker pw
				JOIN programming p ON pw.fk_id_programming = p.id_programming
				WHERE pw.fk_id_programming_user = $idUser
				AND DATE(p.date_programming) = CURDATE()";

		$query = db_connect()->query($sql);

		$data['job_programming'] = null;
		$data['programming']     = null;

		if ($row = $query->getRowArray()) {
			$data['job_programming'] = $row['fk_id_job'];
			$data['programming']     = $row['id_programming'];
		}

		$data['jobs'] = $this->generalModel->get_basic_search([
			'table'  => 'param_jobs',
			'order'  => 'job_description',
			'column' => 'state',
			'id'     => 1,
		]);

		$data['record'] = $this->generalModel->get_task([
			'idEmployee' => $idUser,
			'limit'      => 1,
		]);

		$view = 'App\Modules\Payroll\Views\form_add_payroll';

		if ($data['record'] && (empty($data['record'][0]['finish']) || $data['record'][0]['finish'] == '0000-00-00 00:00:00')) {
			$data['programming'] = $data['record'][0]['fk_id_programming'];
			$data['start']       = $data['record'][0]['start'];
			$view = 'App\Modules\Payroll\Views\form_end_payroll';
		}

		return $this->render($view, $data);
	}

	/**
	 * Save payroll
	 * @since 09/11/2016
	 * @author BMOTTAG
	 * @review 01/05/2026 - new CI4 version
	 */
	public function savePayroll()
	{
		$hour = date('G:i');
		$post = $this->request->getPost();

		if ($this->payrollModel->savePayroll($post)) {
			$infoServiceOrder = $this->payrollModel->get_in_progress_service_order();
			if ($infoServiceOrder) {
				$this->payrollModel->updateServiceOrderTime($infoServiceOrder, 'start');
			}
			session()->setFlashdata('retornoExito', 'have a nice shift, you started at ' . $hour . '.');
		} else {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url($this->session->get('dashboardURL')));
	}

	/**
	 * Update finish time payroll
	 * @since 11/11/2016
	 * @review 2/02/2022
	 * @author BMOTTAG
	 * @review 01/05/2026 - new CI4 version
	 */
	public function updatePayroll()
	{
		$post    = $this->request->getPost();
		$idTask  = $post['hddIdentificador'];
		$start   = $post['hddStart'];
		$finish  = date('Y-m-d G:i:s');
		$hour    = date('G:i');

		if ($this->payrollModel->updateWorkingTimePayroll($start, $finish, $post)) {
			$this->save_period($idTask);

			$infoServiceOrder = $this->payrollModel->get_in_progress_service_order();
			if ($infoServiceOrder) {
				$this->payrollModel->updateServiceOrderTime($infoServiceOrder, 'finish');
			}
			session()->setFlashdata('retornoExito', 'have a good night, you finished at ' . $hour . '.');
		} else {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> bad at math.');
		}

		return redirect()->to(base_url($this->session->get('dashboardURL')));
	}

	/**
	 * Cargo modal - formulario para editar las horas de los empleados
	 * @since 2/2/2018
	 * @review 05/05/2026 - new CI4 version
	 */
	public function cargarModalHours()
	{
		$idTask = $this->request->getPost('idTask');
		$data   = [
			'information' => $this->payrollModel->get_taskbyid((int)$idTask),
		];

		return $this->response
			->setContentType('text/html')
			->setBody(view('App\Modules\Payroll\Views\modal_hours_worker', $data));
	}

	/**
	 * Save payroll hours - used when admin is updating employee hours
	 * @since 2/2/2018
	 * @author BMOTTAG
	 * @review 05/05/2026 - new CI4 version
	 */
	public function savePayrollHour()
	{
		$post          = $this->request->getPost();
		$idTask        = $post['hddIdentificador'];
		$fechaAnterior = $post['hddfechaInicio'];
		$fechaStart    = $post['start_date'];

		$data = [
			'idRecord'    => $idTask,
			'datePayroll' => $fechaStart,
		];

		if ($this->payrollModel->savePayrollHour($post)) {
			$infoTask = $this->payrollModel->get_taskbyid((int)$idTask);
			$start    = $infoTask['start'];
			$finish   = $infoTask['finish'];

			if ($this->payrollModel->updateWorkingTimePayroll($start, $finish, $post, 1)) {
				if ($fechaAnterior != $fechaStart) {
					$this->save_period($idTask);
				}
				session()->setFlashdata('retornoExito', 'You have updated the payroll hour');
			} else {
				session()->setFlashdata('retornoError', '<strong>Error!!!</strong> bad at math.');
			}

			$data['status'] = 'success';
		} else {
			$data['status'] = 'error';
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	/**
	 * Busco a que periodo pertenece y guardo el id en la tabla task.
	 * Si no existe periodo para esa fecha el registro queda sin periodo (0)
	 * hasta que el administrador genere los periodos del ano y los asigne.
	 * @since 9/2/2022
	 * @author BMOTTAG
	 * @review 26/10/2026 - periodos semimensuales; ya no se generan periodos automaticamente
	 */
	protected function save_period($idTask)
	{
		$infoTask = $this->payrollModel->get_taskbyid((int)$idTask);
		if (!$infoTask) {
			return false;
		}

		$idWeakPeriod = $this->payrollModel->get_weak_period_by_date(date('Y-m-d', strtotime($infoTask['start'])));

		return $this->generalModel->updateRecord([
			'table'      => 'task',
			'primaryKey' => 'id_task',
			'id'         => $idTask,
			'column'     => 'fk_id_weak_period',
			'value'      => $idWeakPeriod ?: 0,
		]);
	}

	/**
	 * Administracion de periodos de payroll por ano
	 * @since 26/10/2026
	 */
	public function periods($year = null)
	{
		$year = (int)($year ?: date('Y'));

		$data['year']               = $year;
		$data['info']               = $this->payrollModel->get_periods_with_weeks($year);
		$data['taskSummary']        = $this->payrollModel->get_year_task_summary($year);
		$data['generatedPaystubs']  = $this->payrollModel->count_generated_paystubs_year($year);
		$data['canGenerate']        = session()->get('rol') == ID_ROL_SUPER_ADMIN;

		return $this->render('App\Modules\Payroll\Views\periods_list', $data);
	}

	/**
	 * Genera (o regenera) los 24 periodos del ano
	 * @since 26/10/2026
	 */
	public function generate_year_periods()
	{
		$year = (int)$this->request->getPost('year');

		if (session()->get('rol') != ID_ROL_SUPER_ADMIN) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Only the super admin can generate periods.']);
		}
		if ($year < 2026 || $year > (int)date('Y') + 1) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid year.']);
		}
		if ($this->payrollModel->count_generated_paystubs_year($year) > 0) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'This year already has generated paystubs, periods cannot be regenerated.']);
		}

		if ($this->payrollModel->generateYearPeriods($year)) {
			session()->setFlashdata('retornoExito', 'The 24 periods for ' . $year . ' were generated. Review the weeks, approve the periods and then assign the payroll records.');
			return $this->response->setJSON(['status' => 'success']);
		}

		session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		return $this->response->setJSON(['status' => 'error', 'message' => 'Error generating the periods.']);
	}

	/**
	 * Cargo modal - editar la mitad del periodo (fin de la semana 1)
	 * @since 26/10/2026
	 */
	public function cargarModalPeriod()
	{
		$idPeriod = (int)$this->request->getPost('idPeriod');
		$period   = $this->generalModel->get_period(['idPeriod' => $idPeriod]);
		$weeks    = $this->generalModel->get_weak_period(['idPeriod' => $idPeriod]);

		$data['period'] = $period ? $period[0] : null;
		$data['weeks']  = [];
		foreach ($weeks ?: [] as $week) {
			$data['weeks'][$week['weak_number']] = $week;
		}

		return $this->response
			->setContentType('text/html')
			->setBody(view('App\Modules\Payroll\Views\modal_period', $data));
	}

	/**
	 * Guarda la nueva mitad del periodo y reasigna los registros de ese periodo
	 * @since 26/10/2026
	 */
	public function save_period_weeks()
	{
		$idPeriod = (int)$this->request->getPost('hddIdPeriod');
		$weak1End = (string)$this->request->getPost('weak_1_finish');

		$period = $this->generalModel->get_period(['idPeriod' => $idPeriod]);
		if (!$period) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Period not found.']);
		}
		$period = $period[0];

		if ($period['approved'] == 1) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'The period is approved, set it back to pending to edit the weeks.']);
		}

		$date = \DateTime::createFromFormat('Y-m-d', $weak1End);
		if (!$date || $date->format('Y-m-d') !== $weak1End || $weak1End < $period['date_start'] || $weak1End >= $period['date_finish']) {
			return $this->response->setJSON([
				'status'  => 'error',
				'message' => 'Week 1 must end between ' . $period['date_start'] . ' and the day before ' . $period['date_finish'] . '.',
			]);
		}

		if ($this->payrollModel->updatePeriodWeeks($period, $weak1End)) {
			$this->payrollModel->assignTasksToPeriods((int)$period['year_period'], $idPeriod);
			session()->setFlashdata('retornoExito', 'Weeks updated for period ' . $period['period'] . '.');
			return $this->response->setJSON(['status' => 'success']);
		}

		session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		return $this->response->setJSON(['status' => 'error', 'message' => 'Error saving the weeks.']);
	}

	/**
	 * Aprobar o devolver a pendiente un periodo
	 * @since 26/10/2026
	 */
	public function approve_period()
	{
		$idPeriod = (int)$this->request->getPost('idPeriod');
		$approved = (int)$this->request->getPost('approved') === 1 ? 1 : 0;

		$period = $this->generalModel->get_period(['idPeriod' => $idPeriod]);
		if (!$period) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Period not found.']);
		}

		if ($approved === 0) {
			$paystubs = $this->generalModel->get_paystub_by_period(['idPeriod' => $idPeriod]) ?: [];
			if (in_array(1, array_map('intval', array_column($paystubs, 'commit')), true)) {
				return $this->response->setJSON(['status' => 'error', 'message' => 'The period has generated paystubs, it cannot go back to pending.']);
			}
		}

		if ($this->payrollModel->updatePeriodApproval($idPeriod, $approved)) {
			session()->setFlashdata('retornoExito', 'Period ' . $period[0]['period'] . ($approved ? ' approved.' : ' set back to pending.'));
			return $this->response->setJSON(['status' => 'success']);
		}

		session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		return $this->response->setJSON(['status' => 'error', 'message' => 'Error updating the period.']);
	}

	/**
	 * Asigna el periodo correcto a todos los registros de horas del ano
	 * @since 26/10/2026
	 */
	public function assign_task_periods()
	{
		$year = (int)$this->request->getPost('year');

		if (!$this->generalModel->get_period(['year_period' => $year])) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'There are no periods for ' . $year . '.']);
		}

		$updated = $this->payrollModel->assignTasksToPeriods($year);
		$summary = $this->payrollModel->get_year_task_summary($year);

		$msj = 'Payroll records ' . $year . ' reviewed: ' . (int)$summary['total'] . '. ';
		$msj .= $updated > 0
			? $updated . ' were moved to their correct period.'
			: 'All of them were already in their correct period.';
		if ((int)$summary['unassigned'] > 0) {
			$msj .= ' <strong>' . (int)$summary['unassigned'] . ' records have no period for their date.</strong>';
		}

		session()->setFlashdata('retornoExito', $msj);

		return $this->response->setJSON(['status' => 'success']);
	}

	/**
	 * Payroll form search
	 * @since 10/02/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function payrollSearchForm($contractType = 'x', $idPeriod = 'x', $idEmployee = '')
	{
		$actualYear       = date('Y');
		$data['infoPeriod'] = $this->generalModel->get_period(['year_period' => $actualYear]);

		$view = 'App\Modules\Payroll\Views\form_search';

		if (($contractType !== 'x' && $idPeriod !== 'x') || $this->request->is('post') ) {
			if ($idPeriod !== 'x') {
				$data['contractType'] = $contractType;
				$data['idPeriod']     = $idPeriod;
				$data['idEmployee']   = $idEmployee;
			}

			if ($this->request->getPost('period')) {
				$data['contractType'] = $this->request->getPost('contractType');
				$data['idPeriod']     = $this->request->getPost('period');
				$data['idEmployee']   = $this->request->getPost('employee');
			}

			$data['infoPeriod']     = $this->generalModel->get_period(['idPeriod' => $data['idPeriod']]);
			$data['infoWeakPeriod'] = $this->generalModel->get_weak_period(['idPeriod' => $data['idPeriod']]);

			$data['info'] = $this->generalModel->get_users_by_period([
				'idPeriod'              => $data['idPeriod'],
				'idEmployee'            => $data['idEmployee'],
				'employee_subcontractor' => $data['contractType'],
			]);

			// Las vistas list_payroll* consultan el modelo por cada empleado (logica heredada de CI3)
			$data['generalModel'] = $this->generalModel;

			$cppSettings         = $data['infoPeriod'] ? $this->payrollModel->get_cpp_settings(['year' => $data['infoPeriod'][0]['year_period']]) : false;
			$data['cppSettings'] = $cppSettings ? $cppSettings[0] : null;
			$eiSettings          = $data['infoPeriod'] ? $this->payrollModel->get_ei_settings(['year' => $data['infoPeriod'][0]['year_period']]) : false;
			$data['eiSettings']  = $eiSettings ? $eiSettings[0] : null;
			$taxTables           = $data['infoPeriod'] ? $this->payrollModel->get_tax_tables(['year' => $data['infoPeriod'][0]['year_period']]) : false;
			$data['taxTablesReady'] = $taxTables && count($taxTables) == 2;
			// list_payroll busca la fila de las tablas de impuestos por empleado
			$data['payrollModel'] = $this->payrollModel;

			$view = $data['contractType'] == 2
				? 'App\Modules\Payroll\Views\list_payroll'
				: 'App\Modules\Payroll\Views\list_payroll_subcontractor';
		}

		$data['dashboardURL'] = $this->session->get('dashboardURL');

		return $this->renderTopOnly($view, $data);
	}

	/**
	 * Save paystub
	 * @since 21/2/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function save_paystub()
	{
		$post         = $this->request->getPost();
		$contractType = $post['contractType'];
		$idPeriod     = $post['period'];
		$idEmployee   = $post['employee'];
		$userBankTime = $post['hddBankTime'];
		$bankTimeFlag = $post['hddBankTimeFlag'];
		$btnGenerate  = $post['btnGenerate'] ?? null;

		$infoPeriod = $this->generalModel->get_period(['idPeriod' => $idPeriod]);
		if (!$infoPeriod || $infoPeriod[0]['approved'] != 1) {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> The period must be approved before saving paystubs.');
			return redirect()->to(base_url('payroll/payrollSearchForm/' . $contractType . '/' . $idPeriod . '/' . $idEmployee));
		}

		// Empleados: EE CPP, EE CPP2 y EE EI se calculan en el servidor con la configuracion del ano
		if ($contractType == 2) {
			// Gross Salary confirmado con QuickBooks: 1 = coincide con el calculado, 2 = se ingresa el valor de QuickBooks
			$grossCalculated = round((float)($post['hddCostRegularSalary'] ?? 0) + (float)($post['hddCostOvertime'] ?? 0) + (float)($post['hddCostVacation'] ?? 0), 2);
			$quickbooksCheck = (int)($post['quickbooks_check'] ?? 0);
			$quickbooksGross = $post['quickbooks_gross_salary'] ?? '';

			if ($quickbooksCheck == 1) {
				$post['gross_salary'] = $grossCalculated;
			} elseif ($quickbooksCheck == 2 && is_numeric($quickbooksGross) && $quickbooksGross > 0) {
				$post['gross_salary'] = round((float)$quickbooksGross, 2);
			} else {
				session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Confirm the Gross Salary with QuickBooks, or enter the QuickBooks value.');
				return redirect()->to(base_url('payroll/payrollSearchForm/' . $contractType . '/' . $idPeriod . '/' . $idEmployee));
			}
			$post['quickbooks_check'] = $quickbooksCheck;

			$cpp = $this->calculate_cpp($post, (int)$infoPeriod[0]['year_period']);
			if ($cpp === false) {
				session()->setFlashdata('retornoError', '<strong>Error!!!</strong> There is no CPP configuration for ' . $infoPeriod[0]['year_period'] . '.');
				return redirect()->to(base_url('payroll/payrollSearchForm/' . $contractType . '/' . $idPeriod . '/' . $idEmployee));
			}
			$post['ee_cpp']  = $cpp['ee_cpp'];
			$post['ee_cpp2'] = $cpp['ee_cpp2'];

			$ei = $this->calculate_ei($post, (int)$infoPeriod[0]['year_period']);
			if ($ei === false) {
				session()->setFlashdata('retornoError', '<strong>Error!!!</strong> There is no EI configuration for ' . $infoPeriod[0]['year_period'] . '.');
				return redirect()->to(base_url('payroll/payrollSearchForm/' . $contractType . '/' . $idPeriod . '/' . $idEmployee));
			}
			$post['ee_ei'] = $ei['ee_ei'];

			// TAX = Federal + Provincial de las tablas de la CRA con el claim code elegido (CC1 por defecto)
			$claimCode = (int)($post['claim_code'] ?? TaxTableCalculator::DEFAULT_CLAIM_CODE);
			$taxes     = $this->calculate_tax((float)$post['gross_salary'], (int)$infoPeriod[0]['year_period'], $claimCode);
			if ($taxes['error']) {
				session()->setFlashdata('retornoError', '<strong>Error!!!</strong> ' . $taxes['error']);
				return redirect()->to(base_url('payroll/payrollSearchForm/' . $contractType . '/' . $idPeriod . '/' . $idEmployee));
			}
			$post['claim_code']     = $claimCode;
			$post['federal_tax']    = $taxes['federal_tax'];
			$post['provincial_tax'] = $taxes['provincial_tax'];
			$post['tax']            = $taxes['tax'];
		}

		if ($this->payrollModel->savePaystub($post)) {
			session()->setFlashdata('retornoExito', 'You have saved the Paystub!!');

			if (isset($btnGenerate)) {
				if ($userBankTime == 1 && $bankTimeFlag != '') {
					$this->generalModel->saveBankTimeBalance([
						'idPeriod'            => $idPeriod,
						'idEmployee'          => $post['hddIdUser'],
						'bankTimeAdd'         => $post['hddBankTimeAdd'],
						'bankTimeSubtract'    => $post['hddBankTimeSubtract'],
						'bankNewBalance'      => $post['hddBankTimeNewBalance'],
						'observation'         => 'New Paystub',
					]);
				}

				$this->payrollModel->updateTaskStatus($post);

				$infoTotalYear = $this->generalModel->get_total_yearly([
					'idUser' => $post['hddIdUser'],
					'year'   => $post['hddYear'],
				]);
				$this->payrollModel->updatePayrollTotalYearly($post, $infoTotalYear);
			}
		} else {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return redirect()->to(base_url('payroll/payrollSearchForm/' . $contractType . '/' . $idPeriod . '/' . $idEmployee));
	}

	/**
	 * EE CPP / EE CPP2 / EE EI para un Gross Salary (AJAX), al cambiar el valor confirmado con QuickBooks
	 * @since 27/10/2026
	 */
	public function calculateCpp()
	{
		$gross = $this->request->getPost('gross');
		if (!is_numeric($gross) || $gross < 0) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid Gross Salary.']);
		}

		$post = [
			'gross_salary' => round((float)$gross, 2),
			'hddIdUser'    => $this->request->getPost('idUser'),
		];
		$year = (int)$this->request->getPost('year');

		$cpp = $this->calculate_cpp($post, $year);
		if ($cpp === false) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'There is no CPP configuration for this year.']);
		}
		$ei = $this->calculate_ei($post, $year);
		if ($ei === false) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'There is no EI configuration for this year.']);
		}
		$taxes = $this->calculate_tax($post['gross_salary'], $year, (int)($this->request->getPost('claimCode') ?? TaxTableCalculator::DEFAULT_CLAIM_CODE));
		$money = fn($value) => $value === null ? '' : number_format($value, 2, '.', '');

		return $this->response->setJSON([
			'status'  => 'success',
			'ee_cpp'  => number_format($cpp['ee_cpp'], 2, '.', ''),
			'ee_cpp2' => number_format($cpp['ee_cpp2'], 2, '.', ''),
			'ee_ei'   => number_format($ei['ee_ei'], 2, '.', ''),
			'er_ei'   => number_format($ei['er_ei'], 2, '.', ''),
			'federal_tax'    => $money($taxes['federal_tax']),
			'provincial_tax' => $money($taxes['provincial_tax']),
			'tax_error'      => $taxes['error'],
			'formulas' => [
				'ee_cpp'         => $cpp['formulas']['ee_cpp'],
				'ee_cpp2'        => $cpp['formulas']['ee_cpp2'],
				'ee_ei'          => $ei['formula'],
				'federal_tax'    => $taxes['formulas']['federal_tax'],
				'provincial_tax' => $taxes['formulas']['provincial_tax'],
			],
		]);
	}

	/**
	 * Federal y Provincial tax del paystub con las tablas de la CRA del ano
	 * @since 27/10/2026
	 * @return array{federal_tax: ?float, provincial_tax: ?float, tax: ?float, error: ?string, formulas: array}
	 */
	protected function calculate_tax(float $gross, int $year, int $claimCode): array
	{
		$federalRow    = $this->payrollModel->get_tax_table_row($year, 'federal', $gross);
		$provincialRow = $this->payrollModel->get_tax_table_row($year, 'provincial', $gross);

		$result = TaxTableCalculator::calculate($federalRow, $provincialRow, $gross, $claimCode);
		$tables = $this->payrollModel->get_tax_tables(['year' => $year]);
		if (!$tables || count($tables) < 2) {
			$result['error'] = 'There are no Federal and Provincial tax tables for ' . $year . '.';
		}
		$result['formulas'] = [
			'federal_tax'    => TaxTableCalculator::explain($federalRow, $gross, $claimCode),
			'provincial_tax' => TaxTableCalculator::explain($provincialRow, $gross, $claimCode),
		];
		return $result;
	}

	/**
	 * Calcula EE EI del paystub con la configuracion del ano y el EI acumulado del empleado
	 * @since 27/10/2026
	 * @return array|false
	 */
	protected function calculate_ei(array $post, int $year)
	{
		$settings = $this->payrollModel->get_ei_settings(['year' => $year]);
		if (!$settings) {
			return false;
		}

		$totalYear = $this->generalModel->get_total_yearly(['idUser' => $post['hddIdUser'] ?? '', 'year' => $year]);

		$args = [
			$settings[0],
			(float)$post['gross_salary'],
			$totalYear ? (float)$totalYear[0]['total_year_ee_ei'] : 0,
		];

		return EiCalculator::calculate(...$args) + ['formula' => EiCalculator::explain(...$args)];
	}

	/**
	 * Calcula EE CPP / EE CPP2 del paystub con la configuracion del ano y el acumulado anual del empleado
	 * @since 26/10/2026
	 * @return array|false
	 */
	protected function calculate_cpp(array $post, int $year)
	{
		$settings = $this->payrollModel->get_cpp_settings(['year' => $year]);
		if (!$settings) {
			return false;
		}

		$gross = isset($post['gross_salary'])
			? (float)$post['gross_salary']
			: (float)($post['hddCostRegularSalary'] ?? 0) + (float)($post['hddCostOvertime'] ?? 0) + (float)($post['hddCostVacation'] ?? 0);

		$totalYear = $this->generalModel->get_total_yearly(['idUser' => $post['hddIdUser'] ?? '', 'year' => $year]);
		$totalYear = $totalYear ? $totalYear[0] : [];

		$args = [
			$settings[0],
			$gross,
			(float)($totalYear['total_year_gross_salary'] ?? 0),
			(float)($totalYear['total_year_ee_cpp'] ?? 0),
			(float)($totalYear['total_year_ee_cpp2'] ?? 0),
		];

		return CppCalculator::calculate(...$args) + ['formulas' => CppCalculator::explain(...$args)];
	}

	/**
	 * Configuracion CPP / CPP2 por ano
	 * @since 26/10/2026
	 */
	public function cppSettings()
	{
		$data['info']    = $this->payrollModel->get_cpp_settings();
		$data['canEdit'] = session()->get('rol') == ID_ROL_SUPER_ADMIN;

		return $this->render('App\Modules\Payroll\Views\cpp_settings_list', $data);
	}

	/**
	 * Cargo modal - formulario configuracion CPP
	 * @since 26/10/2026
	 */
	public function cargarModalCppSettings()
	{
		$idCppSetting        = $this->request->getPost('idCppSetting');
		$data['information'] = false;

		if ($idCppSetting !== null && $idCppSetting !== 'x') {
			$info                = $this->payrollModel->get_cpp_settings(['idCppSetting' => (int)$idCppSetting]);
			$data['information'] = $info ? $info[0] : false;
		}

		return $this->response
			->setContentType('text/html')
			->setBody(view('App\Modules\Payroll\Views\modal_cpp_settings', $data));
	}

	/**
	 * Guardar configuracion CPP; si el ano no tiene periodos se generan los 24
	 * @since 26/10/2026
	 */
	public function save_cpp_settings()
	{
		$post         = $this->request->getPost();
		$idCppSetting = $post['hddIdCppSetting'] ?? '';
		$year         = (int)($post['year'] ?? 0);

		if (session()->get('rol') != ID_ROL_SUPER_ADMIN) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Only the super admin can change the CPP configuration.']);
		}
		if ($year < 2026 || $year > (int)date('Y') + 1) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid year.']);
		}

		$fields = ['basic_exemption', 'contribution_rate', 'max_contribution', 'ympe', 'yampe', 'cpp2_rate', 'cpp2_max_contribution'];
		foreach ($fields as $field) {
			if (!isset($post[$field]) || !is_numeric($post[$field]) || (float)$post[$field] < 0) {
				return $this->response->setJSON(['status' => 'error', 'message' => 'All values are required and must be positive numbers.']);
			}
		}
		if ((float)$post['contribution_rate'] > 100 || (float)$post['cpp2_rate'] > 100) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'The rates are percentages, e.g. 5.95']);
		}
		if ((float)$post['yampe'] <= (float)$post['ympe']) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'The YAMPE must be greater than the YMPE.']);
		}

		$existing = $this->payrollModel->get_cpp_settings(['year' => $year]);
		if ($existing && $existing[0]['id_cpp_setting'] != $idCppSetting) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'There is already a configuration for ' . $year . '.']);
		}

		if (!$this->payrollModel->saveCppSettings($post)) {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
			return $this->response->setJSON(['status' => 'error', 'message' => 'Error saving the configuration.']);
		}

		$msj = 'CPP configuration for ' . $year . ' saved.';
		if (!$this->generalModel->get_period(['year_period' => $year])) {
			if ($this->payrollModel->generateYearPeriods($year)) {
				$msj .= ' The 24 periods for ' . $year . ' were generated, review and approve them in Payroll Periods.';
			} else {
				$msj .= ' Error generating the periods, generate them from Payroll Periods.';
			}
		}

		session()->setFlashdata('retornoExito', $msj);
		return $this->response->setJSON(['status' => 'success']);
	}

	/**
	 * Configuracion EI por ano
	 * @since 27/10/2026
	 */
	public function eiSettings()
	{
		$data['info']    = $this->payrollModel->get_ei_settings();
		$data['canEdit'] = session()->get('rol') == ID_ROL_SUPER_ADMIN;

		return $this->render('App\Modules\Payroll\Views\ei_settings_list', $data);
	}

	/**
	 * Cargo modal - formulario configuracion EI
	 * @since 27/10/2026
	 */
	public function cargarModalEiSettings()
	{
		$idEiSetting         = $this->request->getPost('idEiSetting');
		$data['information'] = false;

		if ($idEiSetting !== null && $idEiSetting !== 'x') {
			$info                = $this->payrollModel->get_ei_settings(['idEiSetting' => (int)$idEiSetting]);
			$data['information'] = $info ? $info[0] : false;
		}

		return $this->response
			->setContentType('text/html')
			->setBody(view('App\Modules\Payroll\Views\modal_ei_settings', $data));
	}

	/**
	 * Guardar configuracion EI
	 * @since 27/10/2026
	 */
	public function save_ei_settings()
	{
		$post        = $this->request->getPost();
		$idEiSetting = $post['hddIdEiSetting'] ?? '';
		$year        = (int)($post['year'] ?? 0);

		if (session()->get('rol') != ID_ROL_SUPER_ADMIN) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Only the super admin can change the EI configuration.']);
		}
		if ($year < 2026 || $year > (int)date('Y') + 1) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid year.']);
		}

		foreach (['max_insurable_earnings', 'rate'] as $field) {
			if (!isset($post[$field]) || !is_numeric($post[$field]) || (float)$post[$field] <= 0) {
				return $this->response->setJSON(['status' => 'error', 'message' => 'All values are required and must be positive numbers.']);
			}
		}
		if ((float)$post['rate'] > 100) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'The rate is a percentage, e.g. 1.63']);
		}

		$existing = $this->payrollModel->get_ei_settings(['year' => $year]);
		if ($existing && $existing[0]['id_ei_setting'] != $idEiSetting) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'There is already a configuration for ' . $year . '.']);
		}

		if (!$this->payrollModel->saveEiSettings($post)) {
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
			return $this->response->setJSON(['status' => 'error', 'message' => 'Error saving the configuration.']);
		}

		session()->setFlashdata('retornoExito', 'EI configuration for ' . $year . ' saved.');
		return $this->response->setJSON(['status' => 'success']);
	}

	/**
	 * Tablas de impuestos de la CRA (Federal y Provincial) por ano
	 * @since 27/10/2026
	 */
	public function taxTables()
	{
		$data['canEdit'] = session()->get('rol') == ID_ROL_SUPER_ADMIN;

		// agrupadas por ano: [ano => ['federal' => fila, 'provincial' => fila]]
		$data['tables'] = [];
		foreach ($this->payrollModel->get_tax_tables() ?: [] as $table) {
			$data['tables'][$table['year']][$table['table_type']] = $table;
		}

		return $this->render('App\Modules\Payroll\Views\tax_tables_list', $data);
	}

	/**
	 * Cargo modal - subir tabla de impuestos
	 * @since 27/10/2026
	 */
	public function cargarModalTaxTable()
	{
		$data['year']      = $this->request->getPost('year');
		$data['tableType'] = $this->request->getPost('tableType');

		return $this->response
			->setContentType('text/html')
			->setBody(view('App\Modules\Payroll\Views\modal_tax_table', $data));
	}

	/**
	 * Subir el CSV de una tabla de impuestos; reemplaza la del mismo ano y tipo
	 * @since 27/10/2026
	 */
	public function save_tax_table()
	{
		$year      = (int)$this->request->getPost('year');
		$tableType = $this->request->getPost('tableType');
		$file      = $this->request->getFile('taxFile');

		if (session()->get('rol') != ID_ROL_SUPER_ADMIN) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Only the super admin can upload tax tables.']);
		}
		if ($year < 2026 || $year > (int)date('Y') + 1) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid year.']);
		}
		if (!in_array($tableType, ['federal', 'provincial'], true)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid table type.']);
		}
		if (!$file || !$file->isValid() || strtolower($file->getClientExtension()) !== 'csv') {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Select a valid CSV file.']);
		}
		if ($file->getSize() > 2 * 1024 * 1024) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'The file is too big (2 MB maximum).']);
		}

		try {
			$rows = TaxTableCalculator::parseCsv(file_get_contents($file->getTempName()));
		} catch (InvalidArgumentException $e) {
			return $this->response->setJSON(['status' => 'error', 'message' => esc($e->getMessage())]);
		}

		if (!$this->payrollModel->saveTaxTable($year, $tableType, $file->getClientName(), $rows)) {
			return $this->response->setJSON(['status' => 'error', 'message' => 'Error saving the tax table.']);
		}

		$last = end($rows);
		session()->setFlashdata('retornoExito', ucfirst($tableType) . ' tax table ' . $year . ' loaded: ' . count($rows) . ' rows, pay from 0 to ' . number_format($last['pay_less_than'], 2) . '.');
		return $this->response->setJSON(['status' => 'success']);
	}

	/**
	 * Payroll form search - review paystubs
	 * @since 28/02/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function reviewPaystubs()
	{
		$data['workersList'] = $this->generalModel->get_user(['state' => 1]);

		if ($this->request->is('post')) {
			$data['year']       = $this->request->getPost('year');
			$data['idEmployee'] = $this->request->getPost('employee');

			$data['info'] = $this->generalModel->get_paystub_by_period([
				'year'       => $data['year'],
				'idEmployee' => $data['idEmployee'],
			]);
		}

		$data['dashboardURL'] = $this->session->get('dashboardURL');

		return $this->renderTopOnly('App\Modules\Payroll\Views\form_search_paystubs', $data);
	}

	/**
	 * Payroll form search - review total yearly
	 * @since 28/02/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function reviewYearly()
	{
		$data['workersList'] = $this->generalModel->get_user(['state' => 1]);

		if ($this->request->is('post')) {
			$data['year']       = $this->request->getPost('year');
			$data['idEmployee'] = $this->request->getPost('employee');

			$data['info'] = $this->generalModel->get_total_yearly([
				'year'   => $data['year'],
				'idUser' => $data['idEmployee'],
			]);
		}

		$data['dashboardURL'] = $this->session->get('dashboardURL');

		return $this->renderTopOnly('App\Modules\Payroll\Views\form_search_total_yearly', $data);
	}

	/**
	 * Payroll form search - time sheet
	 * @since 10/04/2022
	 * @author BMOTTAG
	 * @review 01/05/2026 - new CI4 version
	 */
	public function payrollSearchTimeSheet($idPeriod = 'x', $idEmployee = '')
	{
		$data['workersList'] = $this->generalModel->get_user(['state' => 1]);

		$view = 'App\Modules\Payroll\Views\form_search_time_sheet';

		if ($this->request->getPost('from')) {
			$data['idEmployee'] = $this->request->getPost('employee');

			$from         = $this->request->getPost('from');
			$to           = $this->request->getPost('to');
			$data['from'] = formatear_fecha($from);
			$data['to']   = formatear_fecha($to);

			$data['to'] = date('Y-m-d', strtotime('+1 day', strtotime($data['to'])));

			$allTasks = $this->generalModel->get_payroll_full([
				'from' => $data['from'],
				'to' => $data['to'],
				'idEmployee' => $data['idEmployee'],
			]);

			$grouped = [];
			foreach ($allTasks as $task) {
				$uid = $task['fk_id_user'];
				if (!isset($grouped[$uid])) {
					$grouped[$uid] = [
						'fk_id_user'    => $uid,
						'employee_name' => $task['employee_name'],
						'tasks'         => [],
					];
				}
				$grouped[$uid]['tasks'][] = $task;
			}
			$data['info'] = array_values($grouped);

			$view = 'App\Modules\Payroll\Views\list_payroll_time_sheet';
		}

		$data['dashboardURL'] = $this->session->get('dashboardURL');

		return $this->renderTopOnly($view, $data);
	}

	/**
	 * Form Payroll Check - used by cron
	 * @since 12/04/2022
	 * @author BMOTTAG
	 * @review 01/05/2026 - new CI4 version
	 */
	public function payroll_check()
	{
		$records = $this->generalModel->get_payroll_check();

		foreach ($records as $data) {
			$fechaStart   = date('Y-m-d G:i:s', strtotime($data['start']));
			$fechaActual  = date('Y-m-d G:i:s');
			$hours        = abs((strtotime($fechaStart) - strtotime($fechaActual)) / 3600);
			$hours        = round($hours);

			if ($hours > 18) {
				$this->updatePayrollAutomatically($data['id_task'], $data['start']);
			} elseif ($hours > 14) {
				$this->sendSMSWorkerTask($data['fk_id_user']);
			}
		}
	}

	/**
	 * Close finish time payroll - automatically
	 * @since 13/04/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function updatePayrollAutomatically($idTask, $start)
	{
		$finish = date('Y-m-d G:i:s');
		$this->payrollModel->updateWorkingTimePayrollCheck($start, $finish, (int)$idTask);
		$this->save_period($idTask);

		return true;
	}

	/**
	 * Send text message to employee when they haven't checked out
	 * @since 13/4/2022
	 * @author BMOTTAG
	 * @review 15/05/2026 - new CI4 version
	 */
	public function sendSMSWorkerTask($idUser)
	{
		$userInfo = $this->generalModel->get_user(['idUser' => $idUser]);

		if (!$userInfo || empty($userInfo[0]['movil'])) {
			return false;
		}

		$mensaje  = "VCI TIME SHEET";
		$mensaje .= "\n" . $userInfo[0]['first_name'] . ' ' . $userInfo[0]['last_name'];
		$mensaje .= "\nThis message is to remind you that you have been working more than 14 hours, it is possible that you forgot to check out, if it is the case please login the system and check out.";

		$smsService = new \App\Libraries\SmsService();

		try {
			$smsService->send('+1' . $userInfo[0]['movil'], $mensaje);
		} catch (\Exception $e) {
			log_message('error', 'payroll_check SMS error for user ' . $idUser . ': ' . $e->getMessage());
		}

		return true;
	}

	/**
	 * Generate PAYSTUB PDF
	 * @param int $idPaytsub
	 * @since 24/12/2022
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function generaPaystubPDF($idPaytsub)
	{
		$data['infoPaystub'] = $this->generalModel->get_paystub_by_period(['idPaytsub' => $idPaytsub]);

		$builder = new PdfBuilder();
		$pdf     = $builder->create('Paystub');

		$html = view('App\Modules\Payroll\Views\report_paystub', $data);
		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->lastPage();

		if (ob_get_length()) {
			ob_end_clean();
		}

		return $this->response
			->setHeader('Content-Type', 'application/pdf')
			->setBody($pdf->Output('paystub_' . $idPaytsub . '.pdf', 'I'));
	}

	/**
	 * Employee list by contract type (AJAX)
	 * @since 11/9/2022
	 * @author BMOTTAG
	 * @review 01/05/2026 - new CI4 version
	 */
	public function employeeList()
	{
		$identificador = $this->request->getPost('identificador');
		$lista         = $this->generalModel->get_user([
			'state'                  => 1,
			'employee_subcontractor' => $identificador,
		]);

		$html = "<option value=''>Select...</option>";
		if ($lista) {
			foreach ($lista as $fila) {
				$html .= "<option value='" . esc($fila['id_user']) . "'>" . esc($fila['first_name']) . ' ' . esc($fila['last_name']) . '</option>';
			}
		}

		return $this->response
			->setContentType('text/html; charset=utf-8')
			->setBody($html);
	}

	/**
	 * Period list by year (AJAX)
	 * @since 23/12/2022
	 * @author BMOTTAG
	 * @review 01/05/2026 - new CI4 version
	 */
	public function periodList()
	{
		$identificador = $this->request->getPost('identificador');
		$lista         = $this->generalModel->get_period(['year_period' => $identificador]);

		$html = "<option value=''>Select...</option>";
		if ($lista) {
			foreach ($lista as $fila) {
				$period = date('F j, Y', strtotime($fila['date_start'])) . ' - ' . date('F j, Y', strtotime($fila['date_finish']));
				$html  .= "<option value='" . esc($fila['id_period']) . "'>" . $period . '</option>';
			}
		}

		return $this->response
			->setContentType('text/html; charset=utf-8')
			->setBody($html);
	}

	/**
	 * Cargo modal - formulario para editar las horas por job code
	 * @since 2/2/2018
	 * @review 30/04/2026 - new CI4 version
	 */
	public function cargarModalJobCode()
	{
		$idTask = $this->request->getPost('idTask');

		$jobs        = $this->generalModel->get_basic_search([
			'table'  => 'param_jobs',
			'order'  => 'job_description',
			'column' => 'state',
			'id'     => 1,
		]);
		$information = $this->payrollModel->get_taskbyid((int)$idTask);

		$data = [
			'jobs'        => $jobs,
			'information' => $information,
			'hours_start' => $this->separarHorasMinutosEntero($information['hours_start_project']),
			'hours_end'   => $this->separarHorasMinutosEntero($information['hours_end_project']),
		];

		return $this->response
			->setContentType('text/html')
			->setBody(view('App\Modules\Payroll\Views\modal_job_code', $data));
	}

	/**
	 * Separa las horas y los minutos de un valor decimal
	 * @since 2/2/2018
	 * @review 30/04/2026 - new CI4 version
	 */
	protected function separarHorasMinutosEntero($horas_decimal): array
	{
		$horas           = intval($horas_decimal);
		$minutos_decimal = $horas_decimal - $horas;
		$minutos         = round($minutos_decimal * 60);

		return ['horas' => $horas, 'minutos' => $minutos];
	}

	/**
	 * Update task with WO job codes and hours
	 * @since 2/2/2018
	 * @author BMOTTAG
	 * @review 30/04/2026 - new CI4 version
	 */
	public function updateTaskWithWO()
	{
		$post   = $this->request->getPost();
		$idTask = $post['hddIdentificador'];

		$data = ['idRecord' => $idTask];

		if ($this->payrollModel->updateTaskWithWO($post)) {
			$data['result'] = true;
			session()->setFlashdata('retornoExito', 'You have updated the payroll hour');
		} else {
			$data['result'] = 'error';
			session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
		}

		return $this->response->setJSON($data);
	}

	public function reverseGeocode()
	{
		$lat = $this->request->getGet('lat');
		$lon = $this->request->getGet('lon');

		$data = reverse_geocode($lat, $lon);

		return $this->response->setJSON($data);
	}
}
