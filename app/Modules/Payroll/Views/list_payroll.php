<style>
	/* formula de los campos calculados del paystub (CPP, CPP2, EI) */
	.calc-formula { font-size: 11px; line-height: 1.35; margin: 3px 0 0; color: #8a8a8a; }
	.calc-formula:empty { display: none; }

	/* pasos del paystub: ① Gross Salary, ② Taxes & Deductions, ③ Result */
	.paystub-step > .panel-heading { background: #f5f7fa; color: #333; font-weight: 600; font-size: 14px; }
	.paystub-step .step-num { display: inline-block; width: 22px; height: 22px; line-height: 22px; border-radius: 50%; background: #337ab7; color: #fff; text-align: center; font-size: 12px; margin-right: 6px; }
	.deductions-table > thead > tr > th { border-bottom-width: 1px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #888; }
	.deductions-table > tbody > tr > td { vertical-align: top; }
	.deductions-table td:nth-child(2), .deductions-table td:nth-child(3) { width: 26%; }
	.deductions-table .calc-value { width: 100%; border: 0; background: transparent; text-align: right; font-size: 16px; font-weight: bold; color: #333; padding: 0; }
	.deductions-table .calc-value::placeholder { color: #bbb; font-weight: normal; }
	.result-summary > thead > tr > th { border-bottom-width: 1px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #888; }
	.result-summary .net-pay td { border-top: 2px solid #3c763d; font-size: 18px; font-weight: bold; color: #3c763d; }
	.remittance-box { background: #f5f7fa; border-left: 3px solid #337ab7; padding: 8px 10px; margin-bottom: 12px; }
	.detail-toggle { display: inline-block; font-size: 12px; }
	.detail-toggle .fa { transition: transform .2s; }
	.detail-toggle.collapsed .fa { transform: rotate(-90deg); }
	.detail-table { font-size: 12px; margin-bottom: 8px; }
	.detail-table > thead > tr > th { border-bottom-width: 1px; font-size: 11px; text-transform: uppercase; color: #888; }
	/* ② y ③ con la misma altura en pantallas grandes */
	@media (min-width: 1200px) {
		.paystub-eq { display: flex; clear: both; }
		.paystub-eq > [class*="col-"] { display: flex; flex-direction: column; }
		.paystub-eq .paystub-step { flex: 1; }
	}
</style>

<div id="page-wrapper">
	<br>

	<?php if (session()->getFlashdata('retornoExito')): ?>
		<div class="alert alert-success">
			<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
			<?= session()->getFlashdata('retornoExito') ?>
		</div>
	<?php endif; ?>

	<?php if (session()->getFlashdata('retornoError')): ?>
		<div class="alert alert-danger">
			<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
			<?= session()->getFlashdata('retornoError') ?>
		</div>
	<?php endif; ?>

	<?php
		if(!$info){
	?>
	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-violeta">
				<div class="panel-heading">
					<a class="btn btn-violeta btn-xs" href=" <?php echo base_url().'payroll/payrollSearchForm'; ?> "><span class="glyphicon glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Go back </a> 
					<i class="fa fa-clock-o fa-fw"></i> <b>PAYROLL REPORT </b>
					<br><b>Period Beginning: </b> <?php echo date('F j, Y', strtotime($infoPeriod[0]["date_start"])); ?>
					<br><b>Period Ending: </b> <?php echo date('F j, Y', strtotime($infoPeriod[0]["date_finish"])); ?>
				</div>

				<div class="panel-body">
					<div class="alert alert-danger">
						No data was found matching your criteria. 
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
		}else{

			function timeToSeconds($time) {
				$parts = explode(':', $time);
				return ($parts[0] * 3600) + ($parts[1] * 60);
			}
			
			function secondsToTime($seconds) {
				$hours = floor($seconds / 3600);
				$minutes = floor(($seconds / 60) % 60);
				return sprintf('%02d:%02d', $hours, $minutes);
			}
			
			function secondsToFractionHours($seconds) {
				$hours = floor($seconds / 3600);
				$minutes = floor(($seconds / 60) % 60);
				return $hours + ($minutes / 60);
			}

			foreach ($info as $lista):
				$arrParam = array("idUser" => $lista['fk_id_user']);
				$infoUser = $generalModel->get_user($arrParam);
				$employeeName = $infoUser[0]["first_name"] . ' ' . $infoUser[0]["last_name"];
				$employeeHourRate = $infoUser[0]["employee_rate"];
				$employeeType = $infoUser[0]["employee_type"];
				$bankTime = $infoUser[0]["bank_time"];
				$idPeriod = $infoPeriod[0]["id_period"];
				//buscar por usuario y por periodo las horas de cada semana
				$arrParam = array(
					"idUser" => $lista['fk_id_user'],
					"idPeriod" => $idPeriod,
					"weakNumber" => 1
				);
				$infoPayrollUser1 = $generalModel->get_task_by_period($arrParam);

				$arrParam = array(
					"idUser" => $lista['fk_id_user'],
					"idPeriod" => $idPeriod,
					"weakNumber" => 2
				);
				$infoPayrollUser2 = $generalModel->get_task_by_period($arrParam);

				//buscar por perido y por usuario si ya tiene guardado el paystub
				$arrParam = array(
					"idEmployee" => $lista['fk_id_user'],
					"idPeriod" => $idPeriod
				);
				$infoPaystub = $generalModel->get_paystub_by_period($arrParam);

				//si ya se guardo el paystub entonces tomo esos valores 
				if($infoPaystub){
					$employeeHourRate = $infoPaystub[0]["employee_rate_paystub"];
					$employeeType = $infoPaystub[0]["employee_type_paystub"];
				}

				//buscar el total de valores por año y usuario
				$arrParam = array(
					"idUser" => $lista['fk_id_user'],
					"year" => $infoPeriod[0]['year_period']
				);
				$infoTotalYear = $generalModel->get_total_yearly($arrParam);

				//si hay registro envio el id del registro
				$idTotalYear = $infoTotalYear?$infoTotalYear[0]['id_total_yearly']:'';

	?>
	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-violeta">
				<div class="panel-heading">
					<a class="btn btn-violeta btn-xs" href=" <?php echo base_url().'payroll/payrollSearchForm'; ?> "><span class="glyphicon glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Go back </a> 
					<i class="fa fa-clock-o fa-fw"></i> <b>PAYROLL REPORT </b>
					<br><b>Period Beginning: </b> <?php echo date('F j, Y', strtotime($infoPeriod[0]["date_start"])); ?>
					<br><b>Period Ending: </b> <?php echo date('F j, Y', strtotime($infoPeriod[0]["date_finish"])); ?>
				</div>

				<div class="panel-body">
					<div class="alert alert-default">
						<h2><i class="fa fa-user"></i> <b>Employee: </b> <?php echo $employeeName; ?>
							<br><small><b>Hour rate: </b>$ <?php echo $employeeHourRate; ?></small>
							<br><small><b>Type: </b><?php echo $type = $employeeType==1?'Field':'Admin'; ?></small>
							<br><small><b>Is using Bank Time: </b><?php echo $bankTime==1?'Active':'Inactive'; ?></small>
						</h2>
					</div>
					<table width="100%" class="table table-hover" id="dataTables">
						<thead>
							<tr>
								<th class='text-center'>Date & Time - Start</th>
								<th class='text-center'>Date & Time - Finish</th>
								<th class='text-right'>Time Worked <small>(HH:MM)<small></th>
								<th class='text-right'>Time Regular <small>(HH:MM)<small></th>
								<th class='text-right'>Daily Overtime <small>(HH:MM)<small></th>
							</tr>
						</thead>
						<tbody>							
						<?php
							$totalHours1 = 0;
							$totalHours2 = 0;
							$actualRegularHours1 = 0;
							$actualOvertimeHours1 = 0;
							$actualRegularHours2 = 0;
							$actualOvertimeHours2 = 0;
							$hoursTotalFraction1 = 0;
							$hoursTotalFraction2 = 0;
							//explicacion de como salen las horas regulares y overtime de cada semana (se muestra antes del CUT-OFF)
							$weekNotes = [];
							$weekNote = function ($week, $worked, $dailyOvertime, $weeklyOvertime, $regular, $overtime) {
								$f = fn($n) => number_format($n, 3);
								if ($weeklyOvertime > $dailyOvertime) {
									$rule = "the hours above 44 for the week ({$f($weeklyOvertime)}) are more than the daily overtime ({$f($dailyOvertime)}), so the weekly rule is used";
								} else {
									$rule = "the daily overtime ({$f($dailyOvertime)}) is equal to or more than the hours above 44 for the week ({$f($weeklyOvertime)}), so the daily totals are used";
								}
								return "<b>{$week}:</b> {$f($worked)} h worked; {$rule}: <b>{$f($regular)}</b> regular + <b>{$f($overtime)}</b> overtime.";
							};
							if($infoPayrollUser1)
							{
								echo "<tr><td class='text-left' colspan='6'>";
								echo "<p class='text-danger'><b>First Week: " . $infoPayrollUser1[0]["period_weak"] . "</b></p>";
								echo "</td></tr>";
								$totalRegular1 = 0;
								$totalOvertime1 = 0;
								$totalRegularWeek = 0;
								foreach ($infoPayrollUser1 as $lista):
									echo "<tr>";							
									echo "<td class='text-center'>" . $lista['start'] . "</td>";
									echo "<td class='text-center'>" . $lista['finish'] . "</td>";
									echo "<td class='text-right'>" . substr($lista['working_hours_new'], 0, 5) . "</td>";
									echo "<td class='text-right'>" . substr($lista['regular_hours_new'], 0, 5) . "</td>";
									echo "<td class='text-right'>" . substr($lista['overtime_hours_new'], 0, 5) . "</td>";
									echo "</tr>";
									if (!empty($lista['working_hours_new'])) {
										$totalHours1 += timeToSeconds($lista['working_hours_new']);
									}
									if (!empty($lista['regular_hours_new'])) {
										$totalRegular1 += timeToSeconds($lista['regular_hours_new']);
									}
									if (!empty($lista['overtime_hours_new'])) {
										$totalOvertime1 += timeToSeconds($lista['overtime_hours_new']);
									}
								endforeach;
								
								$hoursTotal1 = floor($totalHours1 / 3600);
								$minsTotal1 = floor(($totalHours1 / 60) % 60);

								$hoursRegular1 = floor($totalRegular1 / 3600);
								$minsRegular1 = floor(($totalRegular1 / 60) % 60);

								$hoursOvertime1 = floor($totalOvertime1 / 3600);
								$minsOvertime1 = floor(($totalOvertime1 / 60) % 60);								

								//los paso a fraccion de horas
								$hoursTotalFraction1 = secondsToFractionHours($totalHours1);
								$hoursRegularFraction1 = secondsToFractionHours($totalRegular1);
								$hoursOvertimeFraction1 = secondsToFractionHours($totalOvertime1);
								echo "<tr><td></td><td></td>";
								echo "<td class='text-right'><strong>" . sprintf('%02d:%02d', $hoursTotal1, $minsTotal1) . "</strong>(hh:mm)<br><strong>Total Weekly Hours:<br>" . round($hoursTotalFraction1, 3) . " hours</strong></td>";
								echo "<td class='text-right'><strong>" . sprintf('%02d:%02d', $hoursRegular1, $minsRegular1) . "</strong>(hh:mm)<br><strong>Total Daily Regular Hours Per Week:<br>" . round($hoursRegularFraction1, 3) . " hours</strong></td>";
								echo "<td class='text-right'><strong>" . sprintf('%02d:%02d', $hoursOvertime1, $minsOvertime1) . "</strong>(hh:mm)<br><strong>Total Daily Overtime Per Week:<br>" . round($hoursOvertimeFraction1, 3) . " hours</strong></td>";
								echo "</tr>";

								/** 
								 ** Reviso si se paso de 44 horas para la semana
								 **/
								$totalRegularWeek = $hoursTotalFraction1 > 44 ? 44 : $hoursTotalFraction1;
								$weeklyOvertime = $hoursTotalFraction1 > 44 ? $hoursTotalFraction1 - 44 : 0;

								echo "<tr><td></td><td></td><td></td>";
								echo "<td class='text-right'><strong>Total regular hours for the week:<br>" . round($totalRegularWeek, 3) . "</strong></td>";
								echo "<td class='text-right'><strong>Weekly Overtime Hours:<br>" . round($weeklyOvertime, 3) . "</strong></td>";
								echo "</tr>";

								$actualRegularHours1 = $weeklyOvertime > $hoursOvertimeFraction1 ? $totalRegularWeek : $hoursRegularFraction1;
								$actualOvertimeHours1 = $weeklyOvertime > $hoursOvertimeFraction1 ? $weeklyOvertime : $hoursOvertimeFraction1;
								$weekNotes[] = $weekNote('First week', $hoursTotalFraction1, $hoursOvertimeFraction1, $weeklyOvertime, $actualRegularHours1, $actualOvertimeHours1);
							
								echo "<tr><td></td><td></td><td></td>";
								echo "<td class='text-right'>";
								echo "<p class='text-primary'><b>Actual Regular Hours:<br> " . round($actualRegularHours1, 3) . "</b></p>";
								echo "</td>";
								echo "<td class='text-right'>";
								echo "<p class='text-primary'><b>Actual Overtime Hours:<br> " . round($actualOvertimeHours1, 3) . "</b></p>";
								echo "</td>";
								echo "</tr>";
								echo "<tr><td colspan='5'><br></td></tr>";
							}
							if($infoPayrollUser2)
							{
								echo "<tr><td class='text-left' colspan='6'>";
								echo "<p class='text-danger'><b>Second Week: " . $infoPayrollUser2[0]["period_weak"] . "</b></p>";
								echo "</td></tr>";
								$totalRegular2 = 0;
								$totalOvertime2 = 0;
								$totalRegularWeek = 0;
								$weeklyOvertime = 0;
								foreach ($infoPayrollUser2 as $lista):
									echo "<tr>";							
									echo "<td class='text-center'>" . $lista['start'] . "</td>";
									echo "<td class='text-center'>" . $lista['finish'] . "</td>";
									echo "<td class='text-right'>" . substr($lista['working_hours_new'], 0, 5) . "</td>";
									echo "<td class='text-right'>" . substr($lista['regular_hours_new'], 0, 5) . "</td>";
									echo "<td class='text-right'>" . substr($lista['overtime_hours_new'], 0, 5) . "</td>";
									echo "</tr>";
									if (!empty($lista['working_hours_new'])) {
										$totalHours2 += timeToSeconds($lista['working_hours_new']);
									}
									if (!empty($lista['regular_hours_new'])) {
										$totalRegular2 += timeToSeconds($lista['regular_hours_new']);
									}
									if (!empty($lista['overtime_hours_new'])) {
										$totalOvertime2 += timeToSeconds($lista['overtime_hours_new']);
									}
								endforeach;

								$hoursTotal2 = floor($totalHours2 / 3600);
								$minsTotal2 = floor(($totalHours2 / 60) % 60);

								$hoursRegular2 = floor($totalRegular2 / 3600);
								$minsRegular2 = floor(($totalRegular2 / 60) % 60);

								$hoursOvertime2 = floor($totalOvertime2 / 3600);
								$minsOvertime2 = floor(($totalOvertime2 / 60) % 60);								

								//los paso a fraccion de horas
								$hoursTotalFraction2 = secondsToFractionHours($totalHours2);
								$hoursRegularFraction2 = secondsToFractionHours($totalRegular2);
								$hoursOvertimeFraction2 = secondsToFractionHours($totalOvertime2);
								echo "<tr><td></td><td></td>";
								echo "<td class='text-right'><strong>" . sprintf('%02d:%02d', $hoursTotal2, $minsTotal2) . "</strong>(hh:mm)<br><strong>Total Weekly Hours:<br>" . round($hoursTotalFraction2, 3) . " hours</strong></td>";
								echo "<td class='text-right'><strong>" . sprintf('%02d:%02d', $hoursRegular2, $minsRegular2) . "</strong>(hh:mm)<br><strong>Total Daily Regular Hours Per Week:<br>" . round($hoursRegularFraction2, 3) . " hours</strong></td>";
								echo "<td class='text-right'><strong>" . sprintf('%02d:%02d', $hoursOvertime2, $minsOvertime2) . "</strong>(hh:mm)<br><strong>Total Daily Overtime Per Week:<br>" . round($hoursOvertimeFraction2, 3) . " hours</strong></td>";
								echo "</tr>";

								/** 
								 ** Reviso si se paso de 44 horas para la semana
								 **/
								$totalRegularWeek = $hoursTotalFraction2 > 44 ? 44 : $hoursTotalFraction2;
								$weeklyOvertime = $hoursTotalFraction2 > 44 ? $hoursTotalFraction2 - 44 : 0;

								echo "<tr><td></td><td></td><td></td>";
								echo "<td class='text-right'><strong>Total regular hours for the week:<br>" . round($totalRegularWeek, 3) . "</strong></td>";
								echo "<td class='text-right'><strong>Weekly Overtime Hours:<br>" . round($weeklyOvertime, 3) . "</strong></td>";
								echo "</tr>";

								$actualRegularHours2 = $weeklyOvertime > $hoursOvertimeFraction2 ? $totalRegularWeek : $hoursRegularFraction2;
								$actualOvertimeHours2 = $weeklyOvertime > $hoursOvertimeFraction2 ? $weeklyOvertime : $hoursOvertimeFraction2;
								$weekNotes[] = $weekNote('Second week', $hoursTotalFraction2, $hoursOvertimeFraction2, $weeklyOvertime, $actualRegularHours2, $actualOvertimeHours2);
							
								echo "<tr><td></td><td></td><td></td>";
								echo "<td class='text-right'>";
								echo "<p class='text-primary'><b>Actual Regular Hours:<br> " . round($actualRegularHours2, 3) . "</b></p>";
								echo "</td>";
								echo "<td class='text-right'>";
								echo "<p class='text-primary'><b>Actual Overtime Hours:<br> " . round($actualOvertimeHours2, 3) . "</b></p>";
								echo "</td>";
								echo "</tr>";
								echo "<tr><td colspan='5'><br></td></tr>";
							}
									$totalWorked = $hoursTotalFraction1 + $hoursTotalFraction2;
									$totalRegularHours = $actualRegularHours1 + $actualRegularHours2;
									$totalOvertimeHours = $actualOvertimeHours1 + $actualOvertimeHours2;
									$bankTimeBalance = 0;
									$bankTimeFlag = "";
									$toBankTime = 0;
									$takeFromBanktime = 0;
									$newBankTimeBalance = 0;

									// Resumen CUT-OFF alineado con las columnas Time Worked / Time Regular / Daily Overtime (solo diseno)
									// $cells: [[label, value], ...] una por columna
									$cutoffRow = function ($title, array $cells, $first = true) {
										$style = ($first ? 'border-top:2px solid #a94442;' : 'border-top:1px dashed #ebccd1;') . 'vertical-align:middle;color:#a94442;';
										$html  = "<tr style='background:#fdf7f7'>"
											. "<td colspan='2' class='text-right' style='{$style}'>{$title}</td>";
										foreach ($cells as [$label, $value]) {
											$html .= "<td class='text-right' style='{$style}'>"
												. "<div style='font-size:11px;text-transform:uppercase;letter-spacing:.5px'>{$label}</div>"
												. "<div style='font-size:18px;font-weight:bold'>{$value}</div>"
												. "</td>";
										}
										return $html . "</tr>";
									};
									// Fila con la explicacion de las horas del CUT-OFF; $lines: una linea por renglon
									$explainRow = function (array $lines) {
										return "<tr><td colspan='5' class='text-left' style='border-top:1px dashed #ebccd1;font-size:12px;line-height:1.6;color:#777;padding:8px 10px'>"
											. "<i class='fa fa-info-circle'></i> <b>How the cut-off hours were calculated</b><br>"
											. implode('<br>', $lines)
											. "</td></tr>";
									};
									$f3 = fn($n) => number_format($n, 3);

									if($bankTime == 2){
										echo $explainRow(array_merge($weekNotes, [
											"<b>Total regular hours</b> = " . $f3($actualRegularHours1) . " + " . $f3($actualRegularHours2) . " = <b>" . $f3($totalRegularHours) . "</b>"
											. " &nbsp;·&nbsp; <b>Total overtime hours</b> = " . $f3($actualOvertimeHours1) . " + " . $f3($actualOvertimeHours2) . " = <b>" . $f3($totalOvertimeHours) . "</b>",
										]));
										echo $cutoffRow("<i class='fa fa-scissors'></i> <b>CUT-OFF</b>", [
											['Total worked hours', round($totalWorked, 3)],
											['Total regular hours', round($totalRegularHours, 3)],
											['Total overtime hours', round($totalOvertimeHours, 3)],
										]);
									}elseif($bankTime == 1){

										//bank time info
										$arrParam = array(
											"idUser" => $lista['fk_id_user'],
											"limit" => 1
										);
										$infoBankTime = $generalModel->get_bank_time($arrParam);

										
										if($infoBankTime){
											$bankTimeBalance = $infoBankTime[0]["balance"];
										}
										//si ya se guardo el paystub entonces tomo esos valores 
										if($infoPaystub){
											$bankTimeBalance = $infoPaystub[0]["actual_bank_time_balance"];
										}

										if($totalWorked >= 88){
											$payHours = 88;
											$toBankTime = $totalWorked - 88;
											$newBankTimeBalance = $bankTimeBalance + $toBankTime;
											$bankTimeFlag = $totalWorked == 88 ? "":"add";
										}else{
											$payHours = $totalWorked;
											if($bankTimeBalance > 0){
												$timeNeeded = 88 - $payHours;
												$bankTimeFlag = "subtract";
												if($bankTimeBalance >= $timeNeeded){
													$newBankTimeBalance = $bankTimeBalance - $timeNeeded;
													$takeFromBanktime = $bankTimeBalance - $newBankTimeBalance;
													$payHours = $payHours + $takeFromBanktime;
												}else{
													$takeFromBanktime = $bankTimeBalance;
													$payHours = $payHours + $bankTimeBalance;
												}
											}
										}

										$totalRegularHours = $payHours;
										$totalOvertimeHours = 0;
										
										if($bankTimeFlag == "add"){
											$bankTimeMove = ['Add to Bank Time', '+' . round($toBankTime, 3)];
										}elseif($bankTimeFlag == "subtract"){
											$bankTimeMove = ['Subtract from Bank Time', '-' . round($takeFromBanktime, 3)];
										}else{
											$bankTimeMove = ['Bank Time movement', '0'];
										}

										//con Bank Time no se paga overtime: todo es regular hasta 88 h por periodo
										if($totalWorked > 88){
											$bankNote = $f3($totalWorked) . " h worked; up to 88 h are paid as regular hours and the extra <b>" . $f3($toBankTime) . "</b> h go to the bank.";
										}elseif($totalWorked == 88){
											$bankNote = "88 h worked, all paid as regular hours; nothing goes to the bank.";
										}elseif($takeFromBanktime > 0){
											$bankNote = $f3($totalWorked) . " h worked, " . $f3(88 - $totalWorked) . " h missing to reach 88 h; <b>" . $f3($takeFromBanktime) . "</b> h are taken from the bank (balance " . $f3($bankTimeBalance) . ").";
										}else{
											$bankNote = $f3($totalWorked) . " h worked, below 88 h and there is no bank balance to complete them.";
										}
										echo $explainRow([
											"<b>Bank Time:</b> overtime is not paid; the hours worked in the period are paid as regular hours up to 88 h.",
											$bankNote,
											"<b>Total regular hours</b> = <b>" . $f3($payHours) . "</b> &nbsp;·&nbsp; <b>Total overtime hours</b> = <b>0.000</b>",
										]);
										echo $cutoffRow("<i class='fa fa-scissors'></i> <b>CUT-OFF</b><br><small>with Bank Time</small>", [
											['Total worked hours', round($totalWorked, 3)],
											['Total regular hours', round($payHours, 3)],
											['Total overtime hours', round($totalOvertimeHours, 3)],
										]);
										echo $cutoffRow("<i class='fa fa-university'></i> <b>BANK TIME</b>", [
											['Actual balance', round($bankTimeBalance, 3)],
											$bankTimeMove,
											['New balance', round($newBankTimeBalance, 3)],
										], false);
									}

						?>
						</tbody>
					</table>

					<div class="row">
					<?php 
						$idUser = $lista['fk_id_user'];
						//Cost regular salary 
						$cost_regular_salary = $totalRegularHours * $employeeHourRate;
						//Cost overtime 
						$cost_overtime = $totalOvertimeHours * $employeeHourRate * 1.5;
						//Cost Vacation Regular Salary
						if($employeeType==1){
							$vacation_rate = 0.096;
						}else{
							$vacation_rate = 0.04;
						}
						$cost_vacation = $cost_regular_salary * $vacation_rate;
						//Gross salary
						$gross_salary = $cost_regular_salary + $cost_overtime + $cost_vacation;

						//confirmacion con QuickBooks: 1 = coincide con el calculado, 2 = se ingreso el valor de QuickBooks
						$quickbooksCheck = $infoPaystub ? (int)$infoPaystub[0]['quickbooks_check'] : 0;
						$quickbooksGross = $quickbooksCheck == 2 ? (float)$infoPaystub[0]['gross_salary'] : '';
						//Gross Salary con el que se calculan los taxes
						$grossForTaxes = $quickbooksCheck == 2 ? $quickbooksGross : $gross_salary;
						$paystubCommitted = $infoPaystub && $infoPaystub[0]["commit"] == 1;

						//EE CPP / EE CPP2 calculados con la configuracion del ano y el acumulado anual (ER = EE)
						//$formulas: formula con valores que se muestra debajo de cada campo calculado
						$cppCalc = null;
						$formulas = ['ee_cpp' => '', 'ee_cpp2' => '', 'ee_ei' => ''];
						if($cppSettings){
							$cppArgs = [
								$cppSettings,
								$grossForTaxes,
								$infoTotalYear ? (float)$infoTotalYear[0]['total_year_gross_salary'] : 0,
								$infoTotalYear ? (float)$infoTotalYear[0]['total_year_ee_cpp'] : 0,
								$infoTotalYear ? (float)$infoTotalYear[0]['total_year_ee_cpp2'] : 0,
							];
							$cppCalc = \App\Libraries\CppCalculator::calculate(...$cppArgs);
							$formulas = \App\Libraries\CppCalculator::explain(...$cppArgs) + $formulas;
						}
						//EE EI = Gross Salary x tasa del ano, con tope en la prima maxima anual (ER = EE x 1.4)
						$eiCalc = null;
						if($eiSettings){
							$eiArgs = [
								$eiSettings,
								$grossForTaxes,
								$infoTotalYear ? (float)$infoTotalYear[0]['total_year_ee_ei'] : 0,
							];
							$eiCalc = \App\Libraries\EiCalculator::calculate(...$eiArgs);
							$formulas['ee_ei'] = \App\Libraries\EiCalculator::explain(...$eiArgs);
						}
						//TAX = Federal + Provincial de las tablas de la CRA, con el claim code del paystub (CC1 por defecto)
						$claimCode = $infoPaystub && $infoPaystub[0]['claim_code'] !== null ? (int)$infoPaystub[0]['claim_code'] : \App\Libraries\TaxTableCalculator::DEFAULT_CLAIM_CODE;
						$taxCalc = ['federal_tax' => null, 'provincial_tax' => null, 'tax' => null, 'error' => null];
						$formulas += ['federal_tax' => '', 'provincial_tax' => ''];
						if($taxTablesReady){
							$federalRow = $payrollModel->get_tax_table_row((int)$infoPeriod[0]['year_period'], 'federal', (float)$grossForTaxes);
							$provincialRow = $payrollModel->get_tax_table_row((int)$infoPeriod[0]['year_period'], 'provincial', (float)$grossForTaxes);
							$taxCalc = \App\Libraries\TaxTableCalculator::calculate($federalRow, $provincialRow, (float)$grossForTaxes, $claimCode);
							$formulas['federal_tax'] = \App\Libraries\TaxTableCalculator::explain($federalRow, (float)$grossForTaxes, $claimCode);
							$formulas['provincial_tax'] = \App\Libraries\TaxTableCalculator::explain($provincialRow, (float)$grossForTaxes, $claimCode);
						}
						//si el paystub ya se genero se muestran los valores guardados, sin formulas (el acumulado ya los incluye)
						if($paystubCommitted){
							$cppCalc = ['ee_cpp' => $infoPaystub[0]['ee_cpp'], 'ee_cpp2' => $infoPaystub[0]['ee_cpp2']];
							$eiCalc = ['ee_ei' => $infoPaystub[0]['ee_ei'], 'er_ei' => $infoPaystub[0]['er_ei']];
							//paystubs anteriores a las tablas solo tienen el TAX total
							$taxCalc = $infoPaystub[0]['claim_code'] === null
								? ['federal_tax' => $infoPaystub[0]['tax'], 'provincial_tax' => null, 'error' => null]
								: ['federal_tax' => $infoPaystub[0]['federal_tax'], 'provincial_tax' => $infoPaystub[0]['provincial_tax'], 'error' => null];
							$formulas = ['ee_cpp' => '', 'ee_cpp2' => '', 'ee_ei' => '', 'federal_tax' => $infoPaystub[0]['claim_code'] === null ? 'Total income tax (saved before the tax tables)' : '', 'provincial_tax' => ''];
						}
						$money = fn($value) => $value === null ? '' : number_format((float)$value, 2, '.', '');

					?>
						<?php
							//valores para ③ Result
							$m = fn($n) => '$ ' . number_format((float)$n, 2);
							$ytd = $infoTotalYear ? $infoTotalYear[0] : [];
							$ytdVal = fn($key) => (float)($ytd[$key] ?? 0);
							//sin paystub guardado el YTD de pay incluye el periodo actual; con paystub ya esta en el acumulado
							$year_cost_regular_salary = $ytdVal('total_year_cost_regular_salary') + ($infoPaystub ? 0 : $cost_regular_salary);
							$year_cost_overtime = $ytdVal('total_year_cost_over_time') + ($infoPaystub ? 0 : $cost_overtime);
							$year_cost_vacation = $ytdVal('total_year_cost_vacation_regular_salary') + ($infoPaystub ? 0 : $cost_vacation);
						?>
						<!-- ① Calculo del Gross Salary y confirmacion con QuickBooks; los campos pertenecen al form paystub_<idUser> -->
						<div class="col-lg-12">
							<div class="panel panel-default paystub-step">
								<div class="panel-heading">
									<span class="step-num">1</span> Gross Salary
								</div>
								<div class="panel-body">
									<div class="row">
										<div class="col-lg-6">
											<table width="100%" class="table table-condensed">
												<thead>
													<tr>
														<th></th>
														<th class='text-center'>Hours</th>
														<th class='text-center'>Rate</th>
														<th class='text-right'>Cost</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td>Cost Regular Hours</td>
														<td class='text-center'><?php echo round($totalRegularHours, 3); ?></td>
														<td class='text-center'>$ <?php echo $employeeHourRate; ?></td>
														<td class='text-right'>$ <?php echo number_format($cost_regular_salary, 2); ?></td>
													</tr>
													<tr>
														<td>Cost Overtime Hours</td>
														<td class='text-center'><?php echo round($totalOvertimeHours, 3); ?></td>
														<td class='text-center'>$ <?php echo $employeeHourRate * 1.5; ?> <small class="text-muted">(x1.5)</small></td>
														<td class='text-right'>$ <?php echo number_format($cost_overtime, 2); ?></td>
													</tr>
													<tr>
														<td>Cost Vacation Reg. Salary</td>
														<td></td>
														<td class='text-center'><?php echo $vacation_rate * 100; ?>%</td>
														<td class='text-right'>$ <?php echo number_format($cost_vacation, 2); ?></td>
													</tr>
													<tr style="border-top:2px solid #337ab7">
														<td colspan="3"><strong>Gross Salary</strong></td>
														<td class='text-right'><strong style="font-size:16px">$ <?php echo number_format($gross_salary, 2); ?></strong></td>
													</tr>
												</tbody>
											</table>
										</div>

										<div class="col-lg-6">
											<label>Confirm the Gross Salary with QuickBooks: *</label>
											<div class="radio">
												<label>
													<input type="radio" name="quickbooks_check" class="quickbooks-check" value="1" form="paystub_<?php echo $idUser; ?>" data-user="<?php echo $idUser; ?>" data-gross="<?php echo round($gross_salary, 2); ?>" required <?php echo $quickbooksCheck == 1 ? 'checked' : ''; ?> <?php echo $paystubCommitted ? 'disabled' : ''; ?>>
													It is identical to QuickBooks: <strong>$ <?php echo number_format($gross_salary, 2); ?></strong>
												</label>
											</div>
											<div class="radio">
												<label>
													<input type="radio" name="quickbooks_check" class="quickbooks-check" value="2" form="paystub_<?php echo $idUser; ?>" data-user="<?php echo $idUser; ?>" <?php echo $quickbooksCheck == 2 ? 'checked' : ''; ?> <?php echo $paystubCommitted ? 'disabled' : ''; ?>>
													It is different, enter the QuickBooks value:
												</label>
											</div>
											<div class="input-group" style="max-width:250px">
												<span class="input-group-addon">$</span>
												<input type="number" step="any" min="0.01" name="quickbooks_gross_salary" id="quickbooks_gross_salary_<?php echo $idUser; ?>" form="paystub_<?php echo $idUser; ?>" class="form-control" placeholder="QuickBooks Gross Salary" value="<?php echo $quickbooksGross !== '' ? number_format($quickbooksGross, 2, '.', '') : ''; ?>" <?php echo $quickbooksCheck == 2 && !$paystubCommitted ? 'required' : 'disabled'; ?>>
											</div>

											<?php if ($quickbooksCheck) { ?>
												<div class="alert alert-<?php echo $quickbooksCheck == 1 ? 'success' : 'warning'; ?>" style="margin:10px 0 0">
													<strong>Gross Salary used for taxes: $ <?php echo number_format($grossForTaxes, 2); ?></strong>
													<?php if ($quickbooksCheck == 2) { ?>
														<br><small>QuickBooks value. Difference with the calculation: $ <?php echo number_format($grossForTaxes - $gross_salary, 2); ?></small>
													<?php } ?>
												</div>
											<?php } else { ?>
												<p class="text-muted" style="margin-top:10px"><small>The CPP, CPP2 and EI are calculated with this value when you click Apply.</small></p>
											<?php } ?>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- ② Taxes y deducciones: EE se envia en el form, ER es informativo (el servidor recalcula todo al guardar) -->
						<div class="paystub-eq">
						<div class="col-lg-7">
							<div class="panel panel-default paystub-step">
								<div class="panel-heading">
									<span class="step-num">2</span> Taxes &amp; Deductions
								</div>
								<div class="panel-body">

									<form  name="paystub_<?php echo $idUser ?>" id="paystub_<?php echo $idUser; ?>" method="post" action="<?php echo base_url("payroll/save_paystub"); ?>">

										<input type="hidden" id="hddIdPaytsub" name="hddIdPaytsub" value="<?php echo $infoPaystub?$infoPaystub[0]["id_paystub"]:"" ?>" />
										<input type="hidden" id="hddIdTotalYearly" name="hddIdTotalYearly" value="<?php echo $idTotalYear; ?>" />
										<input type="hidden" id="hddIdPeriod" name="hddIdPeriod" value="<?php echo $idPeriod; ?>" />
										<input type="hidden" id="hddYear" name="hddYear" value="<?php echo $infoPeriod[0]['year_period']; ?>" />
										<input type="hidden" id="hddIdWeakPeriod1" name="hddIdWeakPeriod1" value="<?php echo $infoWeakPeriod[0]['id_period_weak']; ?>" />
										<input type="hidden" id="hddIdWeakPeriod2" name="hddIdWeakPeriod2" value="<?php echo $infoWeakPeriod[1]['id_period_weak']; ?>" />
										<input type="hidden" id="hddIdUser" name="hddIdUser" value="<?php echo $idUser; ?>" />

										<input type="hidden" id="hddBankTime" name="hddBankTime" value="<?php echo $bankTime; ?>" />
										<input type="hidden" id="hddBankTimeFlag" name="hddBankTimeFlag" value="<?php echo $bankTimeFlag; ?>" />
										<input type="hidden" id="hddBankTimeBalance" name="hddBankTimeBalance" value="<?php echo $bankTimeBalance; ?>" />
										<input type="hidden" id="hddBankTimeAdd" name="hddBankTimeAdd" value="<?php echo $toBankTime; ?>" />
										<input type="hidden" id="hddBankTimeSubtract" name="hddBankTimeSubtract" value="<?php echo $takeFromBanktime; ?>" />
										<input type="hidden" id="hddBankTimeNewBalance" name="hddBankTimeNewBalance" value="<?php echo $newBankTimeBalance; ?>" />

										<input type="hidden" id="hddEmployeeRate" name="hddEmployeeRate" value="<?php echo $employeeHourRate; ?>" />
										<input type="hidden" id="hddEmployeeType" name="hddEmployeeType" value="<?php echo $employeeType; ?>" />

										<input type="hidden" id="hddTotalWorkedHours" name="hddTotalWorkedHours" value="<?php echo $totalWorked; ?>" />
										<input type="hidden" id="hddRegularHours" name="hddRegularHours" value="<?php echo $totalRegularHours; ?>" />
										<input type="hidden" id="hddOvertimeHours" name="hddOvertimeHours" value="<?php echo $totalOvertimeHours; ?>" />
										<input type="hidden" id="hddCostRegularSalary" name="hddCostRegularSalary" value="<?php echo $cost_regular_salary; ?>" />
										<input type="hidden" id="hddCostOvertime" name="hddCostOvertime" value="<?php echo $cost_overtime; ?>" />
										<input type="hidden" id="hddCostVacation" name="hddCostVacation" value="<?php echo $cost_vacation; ?>" />
										<input type="hidden" id="hddFGrossSalary" name="hddFGrossSalary" value="<?php echo $gross_salary; ?>" />

										<!-- campos ocultos para redireccionar al listado inicial buscado -->
										<input type="hidden" id="contractType" name="contractType" value="<?php echo $contractType; ?>" />
										<input type="hidden" id="period" name="period" value="<?php echo $idPeriod; ?>" />
										<input type="hidden" id="employee" name="employee" value="<?php echo $idEmployee; ?>" />

										<table class="table deductions-table">
											<thead>
												<tr>
													<th></th>
													<th class="text-right">Employee</th>
													<th class="text-right">Employer</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>
														<b>CPP</b> <small class="text-muted">(calculated)</small>
														<p class="help-block calc-formula formula-ee-cpp"><?php echo $formulas['ee_cpp']; ?></p>
													</td>
													<td class="text-right"><input type="text" id="ee_cpp" name="ee_cpp" value="<?php echo $cppCalc ? number_format($cppCalc['ee_cpp'], 2, '.', '') : ''; ?>" class="calc-value" placeholder="—" readonly ></td>
													<td class="text-right">
														<input type="text" value="<?php echo $cppCalc ? number_format($cppCalc['ee_cpp'], 2, '.', '') : ''; ?>" class="calc-value er-cpp" placeholder="—" readonly >
														<p class="help-block calc-formula"><?php echo $formulas['ee_cpp'] !== '' ? '= EE CPP' : ''; ?></p>
													</td>
												</tr>
												<tr>
													<td>
														<b>CPP2</b> <small class="text-muted">(calculated)</small>
														<p class="help-block calc-formula formula-ee-cpp2"><?php echo $formulas['ee_cpp2']; ?></p>
													</td>
													<td class="text-right"><input type="text" id="ee_cpp2" name="ee_cpp2" value="<?php echo $cppCalc ? number_format($cppCalc['ee_cpp2'], 2, '.', '') : ''; ?>" class="calc-value" placeholder="—" readonly ></td>
													<td class="text-right">
														<input type="text" value="<?php echo $cppCalc ? number_format($cppCalc['ee_cpp2'], 2, '.', '') : ''; ?>" class="calc-value er-cpp2" placeholder="—" readonly >
														<p class="help-block calc-formula"><?php echo $formulas['ee_cpp2'] !== '' ? '= EE CPP2' : ''; ?></p>
													</td>
												</tr>
												<tr>
													<td>
														<b>EI</b> <small class="text-muted">(calculated)</small>
														<p class="help-block calc-formula formula-ee-ei"><?php echo $formulas['ee_ei']; ?></p>
													</td>
													<td class="text-right"><input type="text" id="ee_ei" name="ee_ei" value="<?php echo $eiCalc ? number_format($eiCalc['ee_ei'], 2, '.', '') : ''; ?>" class="calc-value" placeholder="—" readonly ></td>
													<td class="text-right">
														<input type="text" value="<?php echo $eiCalc ? number_format($eiCalc['er_ei'], 2, '.', '') : ''; ?>" class="calc-value er-ei" placeholder="—" readonly >
														<p class="help-block calc-formula"><?php echo $formulas['ee_ei'] !== '' ? '= EE EI x 1.4' : ''; ?></p>
													</td>
												</tr>
												<tr>
													<td style="vertical-align:middle">
														<label class="control-label" for="claim_code_<?php echo $idUser; ?>" style="margin:0">Claim code:</label>
														<p class="help-block calc-formula">Column of the CRA tax tables (TD1). CC 1 by default.</p>
													</td>
													<td class="text-right">
														<select id="claim_code_<?php echo $idUser; ?>" name="claim_code" class="form-control input-sm claim-code" data-user="<?php echo $idUser; ?>" <?php echo $paystubCommitted ? 'disabled' : ''; ?>>
															<?php for ($cc = 0; $cc <= \App\Libraries\TaxTableCalculator::MAX_CLAIM_CODE; $cc++) { ?>
																<option value="<?php echo $cc; ?>" <?php echo $cc == $claimCode ? 'selected' : ''; ?>>CC <?php echo $cc; ?></option>
															<?php } ?>
														</select>
													</td>
													<td></td>
												</tr>
												<tr>
													<td>
														<b>Federal Tax</b> <small class="text-muted">(calculated)</small>
														<p class="help-block calc-formula formula-federal-tax"><?php echo $formulas['federal_tax']; ?></p>
													</td>
													<td class="text-right"><input type="text" name="federal_tax" value="<?php echo $money($taxCalc['federal_tax']); ?>" class="calc-value" placeholder="—" readonly ></td>
													<td></td>
												</tr>
												<tr>
													<td>
														<b>Provincial Tax</b> <small class="text-muted">(calculated)</small>
														<p class="help-block calc-formula formula-provincial-tax"><?php echo $formulas['provincial_tax']; ?></p>
													</td>
													<td class="text-right"><input type="text" name="provincial_tax" value="<?php echo $money($taxCalc['provincial_tax']); ?>" class="calc-value" placeholder="—" readonly ></td>
													<td></td>
												</tr>
												<tr>
													<td style="vertical-align:middle"><label class="control-label" for="gwl_deductions" style="margin:0">GWL Deductions: *</label></td>
													<td class="text-right"><input type="text" id="gwl_deductions" value="<?php echo $infoPaystub?$infoPaystub[0]['gwl_deductions']:""; ?>" name="gwl_deductions" class="form-control input-sm text-right" placeholder="GWL Deductions" required ></td>
													<td></td>
												</tr>
											</tbody>
										</table>

									<?php
										$deshabilitar = "";
										if($infoPaystub && $infoPaystub[0]["commit"] == 1){
											$deshabilitar = "disabled";
										}
										//el periodo debe estar aprobado para guardar paystubs
										if($infoPeriod[0]["approved"] != 1){
											$deshabilitar = "disabled";
											echo '<div class="alert alert-warning"><small>The period is pending approval.</small></div>';
										}
										//sin configuracion CPP del ano no se puede calcular el paystub
										if(!$cppSettings){
											$deshabilitar = "disabled";
											echo '<div class="alert alert-warning"><small>There is no CPP configuration for ' . $infoPeriod[0]['year_period'] . '.</small></div>';
										}
										//sin configuracion EI del ano tampoco
										if(!$eiSettings){
											$deshabilitar = "disabled";
											echo '<div class="alert alert-warning"><small>There is no EI configuration for ' . $infoPeriod[0]['year_period'] . '.</small></div>';
										}
										//sin tablas de impuestos del ano tampoco
										if(!$taxTablesReady){
											$deshabilitar = "disabled";
											echo '<div class="alert alert-warning"><small>There are no Federal and Provincial tax tables for ' . $infoPeriod[0]['year_period'] . '.</small></div>';
										}
									?>
										<!-- el pago fuera de la tabla o un claim code que no aplica (el servidor no deja guardar) -->
										<div class="alert alert-danger tax-error" style="<?php echo $taxCalc['error'] ? '' : 'display:none'; ?>"><small><?php echo esc($taxCalc['error'] ?? ''); ?></small></div>
										<div class="form-group" style="margin:0">
											<button type="submit" id="btnSubmit" name="btnSubmit" class="btn btn-sm btn-primary" value="apply" <?php echo $deshabilitar; ?> >
												Apply <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
											</button>
											<?php if($infoPaystub){ ?>
												<button type="submit" id="btnGenerate" name="btnGenerate" class="btn btn-sm btn-success" value="generate" <?php echo $deshabilitar; ?> >
													Generate Paystub <span class="fa fa-check-square-o" aria-hidden="true"></span>
												</button>
											<?php } ?>

											<?php if($infoPaystub && $infoPaystub[0]["commit"] == 1){ ?>
												<a class="btn btn-sm btn-default" href='<?php echo base_url('payroll/generaPaystubPDF/' . $infoPaystub[0]["id_paystub"] ); ?>' title="Download Paystub" target="_blank">
													<span class="fa fa-cloud-download" aria-hidden="true"></span> PDF
												</a>
											<?php } ?>
										</div>

									</form>
								</div>
							</div>
						</div>

						<!-- ③ Resultado del paystub guardado (Summary, Remittance) y detalle del pay stub -->
						<div class="col-lg-5">
							<div class="panel panel-default paystub-step">
								<div class="panel-heading">
									<span class="step-num">3</span> Result
									<?php if($infoPaystub){ ?>
										<span class="pull-right">
											<?php if($paystubCommitted){ ?>
												<span class="label label-success">Generated</span>
											<?php }else{ ?>
												<span class="label label-default">Draft - not generated</span>
											<?php } ?>
										</span>
									<?php } ?>
								</div>
								<div class="panel-body">
								<?php if($infoPaystub){ ?>
									<p class="text-muted small" style="margin-bottom:8px">
										<i class="fa fa-user"></i> Reviewed by <b><?php echo esc($infoPaystub[0]['name']); ?></b> · <?php echo $infoPaystub[0]['paystub_date_issue']; ?>
									</p>

									<table class="table table-condensed result-summary">
										<thead>
											<tr>
												<th></th>
												<th class="text-right">Current</th>
												<th class="text-right">YTD</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>Total Pay</td>
												<td class="text-right"><?php echo $m($infoPaystub[0]['gross_salary']); ?></td>
												<td class="text-right text-muted"><?php echo $m($ytdVal('total_year_gross_salary')); ?></td>
											</tr>
											<tr>
												<td>Taxes</td>
												<td class="text-right">− <?php echo $m($infoPaystub[0]['ee_total_taxes']); ?></td>
												<td class="text-right text-muted"><?php echo $m($ytdVal('total_year_ee_total_taxes')); ?></td>
											</tr>
											<tr>
												<td>Deductions</td>
												<td class="text-right">− <?php echo $m($infoPaystub[0]['gwl_deductions']); ?></td>
												<td class="text-right text-muted"><?php echo $m($ytdVal('total_year_gwl_deductions')); ?></td>
											</tr>
											<tr class="net-pay">
												<td>Net Pay</td>
												<td class="text-right"><?php echo $m($infoPaystub[0]['net_pay']); ?></td>
												<td></td>
											</tr>
										</tbody>
									</table>

									<div class="remittance-box">
										<div class="clearfix">
											<span class="pull-left"><b>Remittance</b></span>
											<span class="pull-right"><b><?php echo $m($infoPaystub[0]['remittance']); ?></b></span>
										</div>
										<div class="calc-formula">
											EE CPP + ER CPP + EE CPP2 + ER CPP2 + EE EI + ER EI + TAX<br>
											<?php echo implode(' + ', array_map(fn($k) => number_format((float)$infoPaystub[0][$k], 2), ['ee_cpp', 'er_cpp', 'ee_cpp2', 'er_cpp2', 'ee_ei', 'er_ei', 'tax'])); ?>
										</div>
									</div>
								<?php }else{ ?>
									<div class="text-center text-muted" style="padding:15px 10px">
										<i class="fa fa-file-text-o fa-2x"></i>
										<p style="margin-top:8px">Nothing saved yet.<br><small>Confirm the Gross Salary, enter TAX and GWL Deductions, and click <b>Apply</b> to see the result.</small></p>
									</div>
								<?php } ?>

									<a class="detail-toggle<?php echo $infoPaystub ? ' collapsed' : ''; ?>" data-toggle="collapse" href="#paystubDetail_<?php echo $idUser; ?>">
										<i class="fa fa-chevron-down"></i> Pay stub detail
									</a>
									<div class="collapse<?php echo $infoPaystub ? '' : ' in'; ?>" id="paystubDetail_<?php echo $idUser; ?>">
										<p class="text-muted small" style="margin:10px 0 5px">
											<?php echo esc($employeeName); ?> · <?php echo date('M j, Y', strtotime($infoPeriod[0]["date_start"])); ?> – <?php echo date('M j, Y', strtotime($infoPeriod[0]["date_finish"])); ?>
										</p>
										<table class="table table-condensed detail-table">
											<thead>
												<tr>
													<th>Pay</th>
													<th class='text-center'>Hours</th>
													<th class='text-center'>Rate</th>
													<th class='text-right'>Current</th>
													<th class='text-right'>YTD</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>Regular Pay</td>
													<td class='text-center'><?php echo round($totalRegularHours, 3); ?></td>
													<td class='text-center'><?php echo $employeeHourRate; ?></td>
													<td class='text-right'><?php echo $m($cost_regular_salary); ?></td>
													<td class='text-right'><?php echo $m($year_cost_regular_salary); ?></td>
												</tr>
												<tr>
													<td>Overtime Pay</td>
													<td class='text-center'><?php echo round($totalOvertimeHours, 3); ?></td>
													<td class='text-center'><?php echo $employeeHourRate * 1.5; ?></td>
													<td class='text-right'><?php echo $m($cost_overtime); ?></td>
													<td class='text-right'><?php echo $m($year_cost_overtime); ?></td>
												</tr>
												<tr>
													<td>Vacation Pay</td>
													<td></td>
													<td></td>
													<td class='text-right'><?php echo $m($cost_vacation); ?></td>
													<td class='text-right'><?php echo $m($year_cost_vacation); ?></td>
												</tr>
											</tbody>
										</table>
									<?php if($infoPaystub){ ?>
										<table class="table table-condensed detail-table">
											<thead>
												<tr>
													<th>Deductions</th>
													<th class='text-right'>Current</th>
													<th class='text-right'>YTD</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>GWL Deductions</td>
													<td class='text-right'><?php echo $m($infoPaystub[0]['gwl_deductions']); ?></td>
													<td class='text-right'><?php echo $m($ytdVal('total_year_gwl_deductions')); ?></td>
												</tr>
											</tbody>
										</table>
										<table class="table table-condensed detail-table">
											<thead>
												<tr>
													<th>Taxes</th>
													<th class='text-right'>Current</th>
													<th class='text-right'>YTD</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>Income Tax</td>
													<td class='text-right'><?php echo $m($infoPaystub[0]['tax']); ?></td>
													<td class='text-right'><?php echo $m($ytdVal('total_year_tax')); ?></td>
												</tr>
												<tr>
													<td>Employment Insurance</td>
													<td class='text-right'><?php echo $m($infoPaystub[0]['ee_ei']); ?></td>
													<td class='text-right'><?php echo $m($ytdVal('total_year_ee_ei')); ?></td>
												</tr>
												<tr>
													<td>Canada Pension Plan</td>
													<td class='text-right'><?php echo $m($infoPaystub[0]['ee_cpp']); ?></td>
													<td class='text-right'><?php echo $m($ytdVal('total_year_ee_cpp')); ?></td>
												</tr>
												<tr>
													<td>Canada Pension Plan 2</td>
													<td class='text-right'><?php echo $m($infoPaystub[0]['ee_cpp2']); ?></td>
													<td class='text-right'><?php echo $m($ytdVal('total_year_ee_cpp2')); ?></td>
												</tr>
											</tbody>
										</table>
									<?php } ?>
									</div>
								</div>
							</div>
						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
			endforeach;
	 	}	
	?>
</div>

<!--INICIO Modal cambio de hora-->
<div class="modal fade text-center" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">    
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatos">

		</div>
	</div>
</div>                       
<!--FIN Modal-->

<!-- Tables -->
<script>
$(document).ready(function() {
	$('#dataTables').DataTable({
		responsive: true,
		"ordering": false,
		"pageLength": 50
	});
});

// Confirmacion con QuickBooks: el valor solo se ingresa si es diferente al calculado.
// Va aparte porque DataTables falla con las filas colspan de la tabla de horas y detiene el ready anterior.
$(document).on('change', '.quickbooks-check', function () {
	var idUser = $(this).data('user');
	var different = $(this).val() == 2;
	$('#quickbooks_gross_salary_' + idUser)
		.prop('disabled', !different)
		.prop('required', different)
		.focus();
	recalculateCpp(idUser);
});

// Recalcula EE CPP / EE CPP2 / EE EI del form con el Gross Salary elegido (el servidor los vuelve a calcular al guardar)
var cppTimer = {};
$(document).on('input', '[id^=quickbooks_gross_salary_]', function () {
	var idUser = this.id.replace('quickbooks_gross_salary_', '');
	clearTimeout(cppTimer[idUser]);
	cppTimer[idUser] = setTimeout(function () { recalculateCpp(idUser); }, 400);
});

// Al cambiar el claim code se recalculan Federal y Provincial tax
$(document).on('change', '.claim-code', function () {
	recalculateCpp($(this).data('user'));
});

function recalculateCpp(idUser) {
	var form = $('#paystub_' + idUser);
	var check = $('.quickbooks-check[data-user=' + idUser + ']:checked');
	//sin confirmar QuickBooks se usa el Gross calculado
	var gross = check.val() == 2 ? $('#quickbooks_gross_salary_' + idUser).val() : $('.quickbooks-check[data-user=' + idUser + '][value=1]').data('gross');

	if (gross === '' || gross === undefined || isNaN(gross)) {
		return;
	}
	$.ajax({
		type: 'POST',
		url: base_url + 'payroll/calculateCpp',
		data: {'idUser': idUser, 'year': form.find('[name=hddYear]').val(), 'gross': gross, 'claimCode': form.find('[name=claim_code]').val()},
		dataType: 'json',
		cache: false,
		success: function (resp) {
			if (resp.status === 'success') {
				form.find('[name=ee_cpp]').val(resp.ee_cpp);
				form.find('.er-cpp').val(resp.ee_cpp);
				form.find('[name=ee_cpp2]').val(resp.ee_cpp2);
				form.find('.er-cpp2').val(resp.ee_cpp2);
				form.find('[name=ee_ei]').val(resp.ee_ei);
				form.find('.er-ei').val(resp.er_ei);
				form.find('.formula-ee-cpp').html(resp.formulas.ee_cpp);
				form.find('.formula-ee-cpp2').html(resp.formulas.ee_cpp2);
				form.find('.formula-ee-ei').html(resp.formulas.ee_ei);
				form.find('[name=federal_tax]').val(resp.federal_tax);
				form.find('[name=provincial_tax]').val(resp.provincial_tax);
				form.find('.formula-federal-tax').html(resp.formulas.federal_tax);
				form.find('.formula-provincial-tax').html(resp.formulas.provincial_tax);
				form.find('.tax-error').toggle(!!resp.tax_error).find('small').text(resp.tax_error || '');
			}
		}
	});
}
</script>