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

<?php
	$activeUsers = array_filter($info, fn($row) => $row['bank_time'] == 1);
	$totalHours  = array_sum(array_map(fn($row) => (float)$row['balance'], $info));
?>

<div id="page-wrapper">
	<br>
	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<i class="fa fa-university fa-fw"></i> <b>BANK TIME</b>
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

					<div class="alert alert-info">
						<strong>How it works:</strong>
						employees using bank time do not get overtime. In each period they are paid up to
						<b><?php echo \App\Libraries\BankTimeCalculator::PAID_HOURS; ?> h</b> as regular hours;
						the extra hours go to the bank, and if they work less, the missing hours are taken from the bank.
						<br><small>
							The bank changes when a paystub is <b>generated</b> (drafts do not change it).
							Use <b>Add / Remove hours</b> to load the starting balance or to make corrections.
							Bank time is activated per employee in <a href="<?php echo base_url('admin/employeeSettings'); ?>">Employee Settings</a>.
						</small>
					</div>

					<div class="row">
						<div class="col-sm-6">
							<div class="well well-sm text-center">
								<div class="text-muted text-uppercase"><small>Employees using bank time</small></div>
								<div style="font-size:26px;font-weight:bold"><?php echo count($activeUsers); ?></div>
							</div>
						</div>
						<div class="col-sm-6">
							<div class="well well-sm text-center">
								<div class="text-muted text-uppercase"><small>Total hours in the bank</small></div>
								<div style="font-size:26px;font-weight:bold"><?php echo number_format($totalHours, 3); ?> h</div>
							</div>
						</div>
					</div>

					<?php if (!$info) { ?>
						<div class="alert alert-warning">No employee is using bank time.</div>
					<?php } else { ?>
						<div class="table-responsive">
						<table width="100%" class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>Employee</th>
									<th class="text-center">Bank Time</th>
									<th class="text-right">Balance (h)</th>
									<th class="text-center">Last movement</th>
									<th class="text-center">Actions</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($info as $row):
								$balance = (float)$row['balance'];
							?>
								<tr>
									<td>
										<b><?php echo esc($row['employee']); ?></b>
										<?php if ($row['state'] == 2) { ?><br><span class="label label-default">Inactive employee</span><?php } ?>
									</td>
									<td class="text-center">
										<?php if ($row['bank_time'] == 1) { ?>
											<span class="label label-success">Active</span>
										<?php } else { ?>
											<span class="label label-default">Not using it</span>
										<?php } ?>
									</td>
									<td class="text-right" style="font-size:16px">
										<b class="<?php echo $balance > 0 ? 'text-primary' : 'text-muted'; ?>"><?php echo number_format($balance, 3); ?></b>
									</td>
									<td class="text-center">
										<?php if ($row['last_movement']) {
											$in  = (float)$row['time_in'];
											$out = (float)$row['time_out'];
										?>
											<small>
												<?php echo date('M j, Y', strtotime($row['last_movement'])); ?><br>
												<span class="text-muted"><?php echo $row['fk_id_period'] == 0 ? 'Manual' : 'Paystub'; ?></span>
												<?php if ($in > 0) { ?><b class="text-success">+<?php echo number_format($in, 3); ?></b><?php } ?>
												<?php if ($out > 0) { ?><b class="text-danger">-<?php echo number_format($out, 3); ?></b><?php } ?>
											</small>
										<?php } else { ?>
											<small class="text-muted">No movements yet</small>
										<?php } ?>
									</td>
									<td class="text-center" style="white-space:nowrap">
										<a class="btn btn-default btn-xs" href="<?php echo base_url('payroll/bankTimeHistory/' . $row['id_user']); ?>">
											<i class="fa fa-history"></i> History
										</a>
										<?php if ($row['bank_time'] == 1) { ?>
											<button type="button" class="btn btn-primary btn-xs btn-bank-time" data-toggle="modal" data-target="#modal" data-user="<?php echo $row['id_user']; ?>">
												<i class="fa fa-plus-circle"></i> Add / Remove hours
											</button>
										<?php } ?>
									</td>
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

<!--INICIO Modal bank time -->
<div class="modal fade text-center" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatos">

		</div>
	</div>
</div>
<!--FIN Modal bank time -->
