<?php
	$money = fn($v) => number_format((float)$v, 2);
	// Ej: Thursday, January 01, 2026
	$fmt = fn($date) => date('l, F d, Y', strtotime($date));
	$periodName = fn($p, $n) => 'Period ' . $n . ' - ' . $fmt($p['date_start']) . ' - ' . $fmt($p['date_finish']);

	$url = function ($period) use ($year, $includeDrafts) {
		$params = ['year' => $year, 'period' => $period];
		if ($includeDrafts) {
			$params['drafts'] = 1;
		}
		return base_url('payroll/remittance?' . http_build_query($params));
	};

	$periodNumber = [];
	foreach ($periods as $i => $p) {
		$periodNumber[$p['id_period']] = $i + 1;
	}

	$columns = [
		'gross_salary'   => 'Gross Salary',
		'ee_cpp'         => 'EE CPP',
		'ee_cpp2'        => 'EE CPP2',
		'er_cpp'         => 'ER CPP',
		'er_cpp2'        => 'ER CPP2',
		'ee_ei'          => 'EE EI',
		'er_ei'          => 'ER EI',
		'tax'            => 'TAX',
		'gwl_deductions' => 'Canada Life',
		'remittance'     => 'Remittance',
		'net_pay'        => 'Net Pay',
	];

	$moneyCells = function ($row, $tag = 'td') use ($columns, $money) {
		$html = '';
		foreach ($columns as $c => $label) {
			$class = $c == 'remittance' ? 'text-right info' : 'text-right';
			$html .= "<$tag class='$class'>" . $money($row[$c]) . "</$tag>";
		}
		return $html;
	};
?>
<script>
$(function(){
	function go(year, period) {
		var params = {'year': year, 'period': period};
		if ($("#drafts").is(":checked")) {
			params.drafts = 1;
		}
		window.location.href = base_url + 'payroll/remittance?' + $.param(params);
	}

	$("#yearSelect").change(function () {
		go($(this).val(), '');
	});
	$("#periodSelect, #drafts").change(function () {
		go($("#yearSelect").val(), $("#periodSelect").val());
	});
});
</script>

<style>
	.remittance-table th, .remittance-table td { font-size: 12px; white-space: nowrap; padding: 4px 6px !important; }
	.remittance-table tfoot th { border-top: 2px solid #333; }
	.remittance-group td { background: #f5f5f5; font-weight: bold; }
</style>

<div id="page-wrapper">
	<br>
	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<i class="fa fa-university fa-fw"></i> <b>REMITTANCE - <?php echo $year; ?></b>
				</div>
				<div class="panel-body">

					<div class="row">
						<div class="col-sm-2">
							<label for="yearSelect">Year:</label>
							<select id="yearSelect" class="form-control">
								<?php for ($y = 2022; $y <= (int)date('Y') + 1; $y++) { ?>
									<option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="col-sm-6">
							<label for="periodSelect">Period:</label>
							<select id="periodSelect" class="form-control">
								<option value="yearly" <?php echo $period === 'yearly' ? 'selected' : ''; ?>>Yearly Summary</option>
								<?php foreach ($periods as $i => $p) { ?>
									<option value="<?php echo $p['id_period']; ?>" <?php echo $period == $p['id_period'] ? 'selected' : ''; ?>><?php echo esc($periodName($p, $i + 1)); ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="col-sm-4">
							<br>
							<div class="checkbox">
								<label>
									<input type="checkbox" id="drafts" <?php echo $includeDrafts ? 'checked' : ''; ?>> Include draft paystubs
								</label>
							</div>
						</div>
					</div>

					<div class="alert alert-info" style="margin-top: 15px;">
						<strong>Remittance</strong> = EE CPP + EE CPP2 + ER CPP + ER CPP2 + EE EI + ER EI + TAX.
						Only generated paystubs are included<?php echo $includeDrafts ? ' plus drafts (marked as <span class="label label-warning">Draft</span>)' : ''; ?>. Subcontractors are not included.
					</div>

					<?php if (!$periods) { ?>
						<div class="alert alert-danger">The year <?php echo $year; ?> has no payroll periods.</div>

					<?php } elseif ($period === 'yearly') { ?>

						<h4 class="text-center"><b>REMITTANCE - YEARLY SUMMARY <?php echo $year; ?></b></h4>
						<div class="table-responsive">
							<table class="table table-bordered table-hover remittance-table">
								<thead>
									<tr class="active">
										<th>Period</th>
										<th class="text-right">Employees</th>
										<?php foreach ($columns as $label) { ?><th class="text-right"><?php echo $label; ?></th><?php } ?>
										<th class="text-right">Accumulated Remittance</th>
									</tr>
								</thead>
								<tbody>
								<?php foreach ($rows as $row) { ?>
									<tr>
										<td>
											<a href="<?php echo $url($row['id_period']); ?>"><?php echo esc($periodName($row, $periodNumber[$row['id_period']])); ?></a>
											<?php if ($row['drafts'] > 0) { ?><span class="label label-warning"><?php echo $row['drafts']; ?> Draft</span><?php } ?>
										</td>
										<td class="text-right"><?php echo $row['employees']; ?></td>
										<?php echo $moneyCells($row); ?>
										<td class="text-right"><?php echo $row['employees'] > 0 ? $money($row['accumulated']) : ''; ?></td>
									</tr>
								<?php } ?>
								</tbody>
								<tfoot>
									<tr class="active">
										<th colspan="2">TOTAL <?php echo $year; ?></th>
										<?php echo $moneyCells($total, 'th'); ?>
										<th></th>
									</tr>
								</tfoot>
							</table>
						</div>

					<?php } else { ?>

						<div class="row">
							<div class="col-xs-3">
								<?php if ($prevPeriod) { ?>
									<a class="btn btn-default btn-sm" href="<?php echo $url($prevPeriod['id_period']); ?>"><span class="glyphicon glyphicon-chevron-left"></span> Previous period</a>
								<?php } ?>
							</div>
							<div class="col-xs-6 text-center">
								<h4 style="margin-top: 5px;"><b>REMITTANCE - PERIOD</b><br><small><?php echo esc($periodName($currentPeriod, $periodNumber[$currentPeriod['id_period']])); ?></small></h4>
							</div>
							<div class="col-xs-3 text-right">
								<a class="btn btn-default btn-sm" href="<?php echo $url('yearly'); ?>">Yearly Summary</a>
								<?php if ($nextPeriod) { ?>
									<a class="btn btn-default btn-sm" href="<?php echo $url($nextPeriod['id_period']); ?>">Next period <span class="glyphicon glyphicon-chevron-right"></span></a>
								<?php } ?>
							</div>
						</div>

						<?php if (!$rows) { ?>
							<div class="alert alert-warning">No paystubs were found for this period.</div>
						<?php } else { ?>

							<div class="table-responsive">
								<table class="table table-bordered table-hover remittance-table">
									<thead>
										<tr class="active">
											<th>Employee</th>
											<th class="text-right">% Vacation Paid</th>
											<?php foreach ($columns as $label) { ?><th class="text-right"><?php echo $label; ?></th><?php } ?>
										</tr>
									</thead>
									<tbody>
									<?php
										$currentType = null;
										foreach ($rows as $row) {
											if ($row['employee_type_paystub'] != $currentType) {
												$currentType = $row['employee_type_paystub'];
												echo "<tr class='remittance-group'><td colspan='" . (count($columns) + 2) . "'>" . ($currentType == 2 ? 'Admin' : 'Field') . "</td></tr>";
											}
									?>
										<tr>
											<td>
												<a href="<?php echo base_url('payroll/generaPaystubPDF/' . $row['id_paystub']); ?>" target="_blank"><?php echo esc($row['employee']); ?></a>
												<?php if ($row['commit'] == 2) { ?><span class="label label-warning">Draft</span><?php } ?>
											</td>
											<td class="text-right"><?php echo $row['employee_type_paystub'] == 1 ? '9.60%' : '4.00%'; ?></td>
											<?php echo $moneyCells($row); ?>
										</tr>
									<?php } ?>
									</tbody>
									<tfoot>
										<tr class="active">
											<th colspan="2">TOTAL</th>
											<?php echo $moneyCells($total, 'th'); ?>
										</tr>
									</tfoot>
								</table>
							</div>

							<div class="row">
								<div class="col-md-3">
									<div class="panel panel-success">
										<div class="panel-heading"><b>Summary</b></div>
										<table class="table table-condensed">
											<tr><td>CPP <small class="text-muted">(EE + ER, incl. CPP2)</small></td><td class="text-right">$ <?php echo $money($summary['cpp']); ?></td></tr>
											<tr><td>EI <small class="text-muted">(EE + ER)</small></td><td class="text-right">$ <?php echo $money($summary['ei']); ?></td></tr>
											<tr><td>Income Tax</td><td class="text-right">$ <?php echo $money($summary['tax']); ?></td></tr>
											<tr class="success"><th>Total</th><th class="text-right">$ <?php echo $money($summary['total']); ?></th></tr>
										</table>
									</div>
								</div>
								<div class="col-md-3">
									<div class="panel panel-info">
										<div class="panel-heading"><b>QuickBooks Comparison</b></div>
										<table class="table table-condensed">
											<tr><td>Employee Summary <small class="text-muted">(EE CPP + EE EI + Tax + Canada Life)</small></td><td class="text-right">$ <?php echo $money($summary['employee']); ?></td></tr>
											<tr><td>Employer Summary <small class="text-muted">(ER CPP + ER EI + Canada Life)</small></td><td class="text-right">$ <?php echo $money($summary['employer']); ?></td></tr>
										</table>
									</div>
								</div>
								<div class="col-md-2">
									<div class="panel panel-default">
										<div class="panel-heading"><b># Employees</b></div>
										<div class="panel-body text-center"><h3 style="margin: 0;"><?php echo $total['employees']; ?></h3>
											<?php if ($drafts > 0) { ?><small class="text-warning"><?php echo $drafts; ?> draft(s)</small><?php } ?>
										</div>
									</div>
								</div>
							</div>

							<h4><b>Gross Payroll</b> <small>Direct cost (Field) / Admin cost</small></h4>
							<div class="table-responsive">
								<table class="table table-bordered remittance-table">
									<thead>
										<tr class="active">
											<th></th>
											<th class="text-right">Employees</th>
											<?php foreach ($columns as $label) { ?><th class="text-right"><?php echo $label; ?></th><?php } ?>
										</tr>
									</thead>
									<tbody>
										<tr><td>Direct Cost</td><td class="text-right"><?php echo $totalDirect['employees']; ?></td><?php echo $moneyCells($totalDirect); ?></tr>
										<tr><td>Admin Cost</td><td class="text-right"><?php echo $totalAdmin['employees']; ?></td><?php echo $moneyCells($totalAdmin); ?></tr>
									</tbody>
									<tfoot>
										<tr class="active"><th>Total</th><th class="text-right"><?php echo $total['employees']; ?></th><?php echo $moneyCells($total, 'th'); ?></tr>
									</tfoot>
								</table>
							</div>

						<?php } ?>
					<?php } ?>

				</div>
			</div>
		</div>
	</div>
</div>
