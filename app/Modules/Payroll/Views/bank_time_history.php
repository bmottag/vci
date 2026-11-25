<?php if ($canEdit) { ?>
<script>
$(function(){

	$(".btn-bank-time").click(function () {
		$.ajax ({
			type: 'POST',
			url: base_url + 'payroll/cargarModalBankTime',
			data: {'idUser': $(this).data('user')},
			cache: false,
			success: function (data) {
				$('#tablaDatos').html(data);
			}
		});
	});

});
</script>
<?php } ?>

<?php
	$employeeName = trim($user['first_name'] . ' ' . $user['last_name']);
	$fmt = fn($n) => number_format((float)$n, 3);

	// Tipo de movimiento: manual (sin periodo) o del paystub del periodo
	$movementType = function (array $m) {
		if ($m['fk_id_period'] == 0) {
			return (float)$m['time_out'] > 0
				? ['label-warning', 'Manual · Removed']
				: ['label-primary', 'Manual · Added'];
		}
		return (float)$m['time_out'] > 0
			? ['label-danger', 'Paystub · Used']
			: ['label-success', 'Paystub · To bank'];
	};

	$cards = [
		['Added manually',      '+' . $fmt($totals['manual_in']),  'text-primary', 'fa-pencil'],
		['Added from paystubs', '+' . $fmt($totals['period_in']),  'text-success', 'fa-arrow-down'],
		['Used in paystubs',    '-' . $fmt($totals['period_out']), 'text-danger',  'fa-arrow-up'],
		['Removed manually',    '-' . $fmt($totals['manual_out']), 'text-warning', 'fa-eraser'],
	];
?>

<div id="page-wrapper">
	<br>
	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<?php if ($canEdit) { ?>
						<a class="btn btn-primary btn-xs" href="<?php echo base_url('payroll/bankTime'); ?>">
							<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Bank Time list
						</a>
					<?php } else { ?>
						<a class="btn btn-primary btn-xs" href="<?php echo base_url(session()->get('dashboardURL')); ?>">
							<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Go to Dashboard
						</a>
					<?php } ?>
					<i class="fa fa-university fa-fw"></i> <b>BANK TIME - <?php echo esc(strtoupper($employeeName)); ?></b>
				</div>
				<div class="panel-body">

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

					<?php if ($user['bank_time'] != 1) { ?>
						<div class="alert alert-warning">
							<?php echo $canEdit
								? 'This employee is not using bank time. Activate it in <a href="' . base_url('admin/employeeSettings') . '">Employee Settings</a> to add hours.'
								: 'You are not using bank time.'; ?>
						</div>
					<?php } ?>

					<div class="row">
						<div class="col-md-4">
							<div class="panel panel-info">
								<div class="panel-body text-center">
									<div class="text-muted text-uppercase"><small>Current balance</small></div>
									<div style="font-size:40px;font-weight:bold;line-height:1.2" class="text-primary">
										<?php echo $fmt($balance); ?> <small>h</small>
									</div>
									<?php if ($canEdit && $user['bank_time'] == 1) { ?>
										<button type="button" class="btn btn-primary btn-sm btn-bank-time" data-toggle="modal" data-target="#modal" data-user="<?php echo $user['id_user']; ?>">
											<i class="fa fa-plus-circle"></i> Add / Remove hours
										</button>
									<?php } ?>
								</div>
							</div>
						</div>
						<div class="col-md-8">
							<div class="row">
							<?php foreach ($cards as [$label, $value, $class, $icon]) { ?>
								<div class="col-xs-6">
									<div class="well well-sm text-center">
										<div class="text-muted text-uppercase"><small><i class="fa <?php echo $icon; ?>"></i> <?php echo $label; ?></small></div>
										<div style="font-size:20px;font-weight:bold" class="<?php echo $class; ?>"><?php echo $value; ?></div>
									</div>
								</div>
							<?php } ?>
							</div>
						</div>
					</div>

					<p class="text-muted"><small>
						<i class="fa fa-info-circle"></i>
						Each period up to <b><?php echo \App\Libraries\BankTimeCalculator::PAID_HOURS; ?> h</b> are paid as regular hours (no overtime):
						extra hours go to the bank and missing hours are taken from it when the paystub is generated.
					</small></p>

					<?php if (!$movements) { ?>
						<div class="alert alert-default text-center text-muted">There are no bank time movements yet.</div>
					<?php } else { ?>
						<div class="table-responsive">
						<table width="100%" class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th class="text-center">Date</th>
									<th class="text-center">Movement</th>
									<th class="text-center">Period</th>
									<th class="text-right">Added (h)</th>
									<th class="text-right">Removed (h)</th>
									<th class="text-right">Balance (h)</th>
									<th>Note</th>
									<th class="text-center">Done by</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($movements as $m):
								[$labelClass, $labelText] = $movementType($m);
							?>
								<tr>
									<td class="text-center" style="white-space:nowrap"><small><?php echo date('M j, Y', strtotime($m['date_issue'])); ?><br><span class="text-muted"><?php echo date('g:i a', strtotime($m['date_issue'])); ?></span></small></td>
									<td class="text-center"><span class="label <?php echo $labelClass; ?>"><?php echo $labelText; ?></span></td>
									<td class="text-center"><small><?php echo $m['fk_id_period'] == 0 ? '<span class="text-muted">—</span>' : esc($m['period'] ?? ''); ?></small></td>
									<td class="text-right text-success"><?php echo (float)$m['time_in'] > 0 ? '+' . $fmt($m['time_in']) : ''; ?></td>
									<td class="text-right text-danger"><?php echo (float)$m['time_out'] > 0 ? '-' . $fmt($m['time_out']) : ''; ?></td>
									<td class="text-right"><b><?php echo $fmt($m['balance']); ?></b></td>
									<td><small><?php echo nl2br(esc($m['observation'])); ?></small></td>
									<td class="text-center"><small><?php echo esc($m['done_by']); ?></small></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if ($canEdit) { ?>
<!--INICIO Modal bank time -->
<div class="modal fade text-center" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatos">

		</div>
	</div>
</div>
<!--FIN Modal bank time -->
<?php } ?>
