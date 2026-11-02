<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
$(function(){

	$(".btn-edit-period").click(function () {
		var oID = $(this).attr("id");
		$.ajax ({
			type: 'POST',
			url: base_url + 'payroll/cargarModalPeriod',
			data: {'idPeriod': oID},
			cache: false,
			success: function (data) {
				$('#tablaDatos').html(data);
			}
		});
	});

	function showError(message) {
		Swal.fire({
			title: "Error!",
			text: message ? message : "Error. Reload the web page.",
			icon: "error"
		});
	}

	function postAction(url, data, btn) {
		$(btn).prop('disabled', true);
		$("#div_load").show();
		$.ajax({
			type: "POST",
			url: base_url + url,
			data: data,
			dataType: "json",
			cache: false,
			success: function (resp) {
				if (resp.status === "success") {
					window.location.reload();
					return;
				}
				$("#div_load").hide();
				$(btn).prop('disabled', false);
				showError(resp.message);
			},
			error: function () {
				$("#div_load").hide();
				$(btn).prop('disabled', false);
				showError();
			}
		});
	}

	// Confirmacion con SweetAlert, igual que en el resto de la aplicacion
	function confirmAction(options, onConfirm) {
		Swal.fire({
			title: options.title,
			html: options.html,
			icon: options.icon || "question",
			showCancelButton: true,
			confirmButtonColor: options.confirmColor || "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: options.confirmText,
			cancelButtonText: "Cancel"
		}).then((result) => {
			if (result.isConfirmed) {
				onConfirm();
			}
		});
	}

	$(".btn-approve-period").click(function () {
		var btn = this;
		var approved = $(this).data("approved");
		var period = $(this).data("period");

		confirmAction(approved == 1 ? {
			title: "Approve period?",
			html: "<b>" + period + "</b><br><br>The weeks will be locked and paystubs can be saved for this period.",
			icon: "question",
			confirmText: "Yes, approve it!"
		} : {
			title: "Set back to pending?",
			html: "<b>" + period + "</b><br><br>The weeks can be edited again and paystubs cannot be saved until it is approved.",
			icon: "warning",
			confirmText: "Yes, set to pending"
		}, function () {
			postAction('payroll/approve_period', {'idPeriod': $(btn).attr("id"), 'approved': approved}, btn);
		});
	});

	$("#btnGenerate").click(function () {
		var btn = this;
		confirmAction(<?php echo $info ? "{
			title: 'Regenerate periods $year?',
			html: 'The periods of <b>$year</b> will be <b>deleted</b> and created again (24 semi-monthly periods).<br><br>Draft paystubs of $year will be deleted and the payroll records will be left without period until you assign them.',
			icon: 'warning',
			confirmColor: '#d33',
			confirmText: 'Yes, regenerate'
		}" : "{
			title: 'Generate periods $year?',
			html: 'The 24 semi-monthly periods of <b>$year</b> will be created (1-15 and 16-end of month).',
			icon: 'question',
			confirmText: 'Yes, generate'
		}"; ?>, function () {
			postAction('payroll/generate_year_periods', {'year': <?php echo $year; ?>}, btn);
		});
	});

	$("#btnAssign").click(function () {
		var btn = this;
		confirmAction({
			title: "Assign periods?",
			html: "Each payroll record of <b><?php echo $year; ?></b> will be assigned to the period of its start date.<br><br>Paid records are not changed.",
			icon: "question",
			confirmText: "Yes, assign"
		}, function () {
			postAction('payroll/assign_task_periods', {'year': <?php echo $year; ?>}, btn);
		});
	});

	$("#yearSelect").change(function () {
		window.location.href = base_url + 'payroll/periods/' + $(this).val();
	});

});
</script>

<?php
	// Ej: Saturday, January 10, 2026
	$fmt = fn($date) => $date ? date('l, F j, Y', strtotime($date)) : '';
?>
<div id="page-wrapper">
	<br>
	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<i class="fa fa-calendar fa-fw"></i> <b>PAYROLL PERIODS - <?php echo $year; ?></b>
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
						<strong>Steps:</strong>
						1) Generate the 24 periods of the year (1-15 and 16-end of month).
						2) Review the two weeks of each period, edit where week 1 ends if needed, and approve the period.
						3) Assign the period to the payroll records.
						Paystubs can only be saved for approved periods.
					</div>

					<div class="row">
						<div class="col-sm-2">
							<label for="yearSelect">Year:</label>
							<select id="yearSelect" class="form-control">
								<?php for ($y = 2026; $y <= (int)date('Y') + 1; $y++) { ?>
									<option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
								<?php } ?>
							</select>
						</div>

						<div class="col-sm-5">
							<br>
							<?php if ($canGenerate) { ?>
								<button type="button" id="btnGenerate" class="btn btn-<?php echo $info ? 'danger' : 'primary'; ?>" <?php echo $generatedPaystubs > 0 ? 'disabled title="This year already has generated paystubs"' : ''; ?>>
									<span class="fa fa-refresh" aria-hidden="true"></span>
									<?php echo $info ? 'Regenerate' : 'Generate'; ?> periods <?php echo $year; ?>
								</button>
							<?php } ?>
							<?php if ($info) { ?>
								<button type="button" id="btnAssign" class="btn btn-success">
									<span class="fa fa-random" aria-hidden="true"></span> Assign periods to payroll records
								</button>
							<?php } ?>
						</div>

						<div class="col-sm-5">
							<br>
							<small>
								<b>Payroll records <?php echo $year; ?>:</b> <?php echo (int)$taskSummary['total']; ?>
								&nbsp;|&nbsp; <b class="<?php echo $taskSummary['unassigned'] > 0 ? 'text-danger' : ''; ?>">Without period:</b> <?php echo (int)$taskSummary['unassigned']; ?>
								&nbsp;|&nbsp; <b>Paid:</b> <?php echo (int)$taskSummary['paid']; ?>
								&nbsp;|&nbsp; <b>Generated paystubs:</b> <?php echo $generatedPaystubs; ?>
							</small>
						</div>
					</div>

					<div id="div_load" style="display:none">
						<br>
						<div class="progress progress-striped active">
							<div class="progress-bar" role="progressbar" style="width: 100%">Processing...</div>
						</div>
					</div>

					<br>
					<?php if (!$info) { ?>
						<div class="alert alert-warning">
							There are no periods for <?php echo $year; ?>.
						</div>
					<?php } else { ?>
						<table width="100%" class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th class="text-center">#</th>
									<th class="text-center">Period</th>
									<th class="text-center">Week 1</th>
									<th class="text-center">Week 2</th>
									<th class="text-center">Payroll records</th>
									<th class="text-center">Status</th>
									<th class="text-center">Actions</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($info as $i => $lista):
								$approved = $lista['approved'] == 1;
							?>
								<tr>
									<td class="text-center"><?php echo $i + 1; ?></td>
									<td class="text-center"><?php echo date('F j, Y', strtotime($lista['date_start'])) . '<br>' . date('F j, Y', strtotime($lista['date_finish'])); ?></td>
									<td class="text-center"><?php echo $fmt($lista['weak_1_start']) . '<br>' . $fmt($lista['weak_1_finish']); ?></td>
									<td class="text-center"><?php echo $fmt($lista['weak_2_start']) . '<br>' . $fmt($lista['weak_2_finish']); ?></td>
									<td class="text-center"><?php echo $lista['tasks']; ?></td>
									<td class="text-center">
										<?php if ($approved) { ?>
											<span class="label label-success">Approved</span>
											<br><small><?php echo esc($lista['approved_by_name'] ?? ''); ?> <?php echo $fmt($lista['approved_date']); ?></small>
										<?php } else { ?>
											<span class="label label-warning">Pending</span>
										<?php } ?>
									</td>
									<td class="text-center">
										<?php if (!$approved) { ?>
											<button type="button" class="btn btn-primary btn-xs btn-edit-period" data-toggle="modal" data-target="#modal" id="<?php echo $lista['id_period']; ?>" title="Edit weeks">
												<span class="glyphicon glyphicon-edit" aria-hidden="true"></span> Weeks
											</button>
											<button type="button" class="btn btn-success btn-xs btn-approve-period" id="<?php echo $lista['id_period']; ?>" data-period="<?php echo date('F j, Y', strtotime($lista['date_start'])) . ' - ' . date('F j, Y', strtotime($lista['date_finish'])); ?>" data-approved="1" title="Approve">
												<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Approve
											</button>
										<?php } else { ?>
											<button type="button" class="btn btn-default btn-xs btn-approve-period" id="<?php echo $lista['id_period']; ?>" data-period="<?php echo date('F j, Y', strtotime($lista['date_start'])) . ' - ' . date('F j, Y', strtotime($lista['date_finish'])); ?>" data-approved="0" <?php echo $lista['paystubs_generated'] > 0 ? 'disabled title="The period has generated paystubs"' : 'title="Set back to pending"'; ?>>
												<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span> Pending
											</button>
										<?php } ?>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<!--INICIO Modal para editar semanas del periodo -->
<div class="modal fade text-center" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatos">

		</div>
	</div>
</div>
<!--FIN Modal para editar semanas del periodo -->
