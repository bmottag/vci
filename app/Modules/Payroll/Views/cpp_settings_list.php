<script>
$(function(){

	$(".btn-cpp-settings").click(function () {
		var oID = $(this).attr("id");
		$.ajax ({
			type: 'POST',
			url: base_url + 'payroll/cargarModalCppSettings',
			data: {'idCppSetting': oID},
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
					<i class="fa fa-percent fa-fw"></i> <b>CPP CONTRIBUTION RATES, MAXIMUMS AND EXEMPTIONS</b>
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
						Enter the CRA values for each year (usually in November for the next year). When a new year is saved, its 24 payroll periods are generated automatically.
						<br><small>
							EE CPP = MIN( (Gross - Basic exemption / 24) x Rate, Maximum - CPP YTD ).
							EE CPP2 = MIN( (Gross between YMPE and YAMPE, YTD) x CPP2 rate, CPP2 maximum - CPP2 YTD ).
							ER = EE.
						</small>
					</div>

					<?php if ($canEdit) { ?>
						<button type="button" class="btn btn-primary btn-cpp-settings" data-toggle="modal" data-target="#modal" id="x">
							<span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add year
						</button>
						<br><br>
					<?php } ?>

					<?php if (!$info) { ?>
						<div class="alert alert-warning">There is no CPP configuration yet.</div>
					<?php } else { ?>
						<table width="100%" class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th class="text-center">Year</th>
									<th class="text-right">Basic exemption</th>
									<th class="text-right">Rate (%)</th>
									<th class="text-right">Maximum contribution</th>
									<th class="text-right">YMPE</th>
									<th class="text-right">YAMPE</th>
									<th class="text-right">CPP2 rate (%)</th>
									<th class="text-right">CPP2 maximum</th>
									<th class="text-center">Updated by</th>
									<?php if ($canEdit) { ?><th class="text-center">Edit</th><?php } ?>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($info as $lista): ?>
								<tr>
									<td class="text-center"><b><?php echo $lista['year']; ?></b></td>
									<td class="text-right">$ <?php echo number_format($lista['basic_exemption'], 2); ?></td>
									<td class="text-right"><?php echo (float)$lista['contribution_rate']; ?></td>
									<td class="text-right">$ <?php echo number_format($lista['max_contribution'], 2); ?></td>
									<td class="text-right">$ <?php echo number_format($lista['ympe'], 2); ?></td>
									<td class="text-right">$ <?php echo number_format($lista['yampe'], 2); ?></td>
									<td class="text-right"><?php echo (float)$lista['cpp2_rate']; ?></td>
									<td class="text-right">$ <?php echo number_format($lista['cpp2_max_contribution'], 2); ?></td>
									<td class="text-center"><small><?php echo esc($lista['name'] ?? ''); ?><br><?php echo $lista['date_issue']; ?></small></td>
									<?php if ($canEdit) { ?>
										<td class="text-center">
											<button type="button" class="btn btn-primary btn-xs btn-cpp-settings" data-toggle="modal" data-target="#modal" id="<?php echo $lista['id_cpp_setting']; ?>" title="Edit">
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

<!--INICIO Modal configuracion CPP -->
<div class="modal fade text-center" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content" id="tablaDatos">

		</div>
	</div>
</div>
<!--FIN Modal configuracion CPP -->
