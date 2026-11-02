<script>
$(function(){

	$(".btn-ei-settings").click(function () {
		var oID = $(this).attr("id");
		$.ajax ({
			type: 'POST',
			url: base_url + 'payroll/cargarModalEiSettings',
			data: {'idEiSetting': oID},
			cache: false,
			success: function (data) {
				$('#tablaDatos').html(data);
			}
		});
	});

});
</script>

<div id="page-wrapper">
	<br>
	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<i class="fa fa-percent fa-fw"></i> <b>FEDERAL EI PREMIUM RATES AND MAXIMUMS</b>
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
						<strong>Note:</strong>
						Enter the CRA values for each year (usually in November for the next year).
						<br><small>
							Maximum annual employee premium = Maximum insurable earnings x Rate.
							The employer premium is 1.4 times the employee premium.
						</small>
					</div>

					<?php if ($canEdit) { ?>
						<button type="button" class="btn btn-primary btn-ei-settings" data-toggle="modal" data-target="#modal" id="x">
							<span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add year
						</button>
						<br><br>
					<?php } ?>

					<?php if (!$info) { ?>
						<div class="alert alert-warning">There is no EI configuration yet.</div>
					<?php } else { ?>
						<table width="100%" class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th class="text-center">Year</th>
									<th class="text-right">Maximum annual insurable earnings</th>
									<th class="text-right">Rate (%)</th>
									<th class="text-right">Maximum annual employee premium</th>
									<th class="text-right">Maximum annual employer premium</th>
									<th class="text-center">Updated by</th>
									<?php if ($canEdit) { ?><th class="text-center">Edit</th><?php } ?>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($info as $lista):
								$maxEmployee = round($lista['max_insurable_earnings'] * $lista['rate'] / 100, 2);
								$maxEmployer = round($maxEmployee * 1.4, 2);
							?>
								<tr>
									<td class="text-center"><b><?php echo $lista['year']; ?></b></td>
									<td class="text-right">$ <?php echo number_format($lista['max_insurable_earnings'], 2); ?></td>
									<td class="text-right"><?php echo (float)$lista['rate']; ?></td>
									<td class="text-right">$ <?php echo number_format($maxEmployee, 2); ?></td>
									<td class="text-right">$ <?php echo number_format($maxEmployer, 2); ?></td>
									<td class="text-center"><small><?php echo esc($lista['name'] ?? ''); ?><br><?php echo $lista['date_issue']; ?></small></td>
									<?php if ($canEdit) { ?>
										<td class="text-center">
											<button type="button" class="btn btn-primary btn-xs btn-ei-settings" data-toggle="modal" data-target="#modal" id="<?php echo $lista['id_ei_setting']; ?>" title="Edit">
												<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
											</button>
										</td>
									<?php } ?>
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

<!--INICIO Modal configuracion EI -->
<div class="modal fade text-center" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatos">

		</div>
	</div>
</div>
<!--FIN Modal configuracion EI -->
<script type="text/javascript" src="<?php echo base_url("assets/js/validate/payroll/eiSettingsModal.js?v=1.0"); ?>"></script>
