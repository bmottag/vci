<script>
$(function(){

	$(".btn-tax-table").click(function () {
		$.ajax ({
			type: 'POST',
			url: base_url + 'payroll/cargarModalTaxTable',
			data: {'year': $(this).data('year'), 'tableType': $(this).data('type')},
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
					<i class="fa fa-table fa-fw"></i> <b>TAX DEDUCTION TABLES (SEMI-MONTHLY, 24 PAY PERIODS)</b>
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
						Upload the CRA Federal and Provincial (Alberta) tax deduction tables for each year as CSV, with the columns
						<code>From, Less than, CC 0, CC 1, ... CC 10</code> (one row per pay range, like the PDF). Uploading a file again replaces that table.
						<br><small>
							Paystub TAX = Federal tax + Provincial tax, taken from the row where the Gross Salary falls and the column of the claim code (CC 1 by default).
							Empty cells are 0; <code>*</code> means it does not apply.
						</small>
					</div>

					<table width="100%" class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th class="text-center">Year</th>
								<th>Table</th>
								<th class="text-center">Status</th>
								<th>File</th>
								<th class="text-right">Rows</th>
								<th class="text-right">Pay range</th>
								<th class="text-center">Uploaded by</th>
								<?php if ($canEdit) { ?><th class="text-center">Upload</th><?php } ?>
							</tr>
						</thead>
						<tbody>
						<?php for ($year = (int)date('Y') + 1; $year >= 2026; $year--) { ?>
							<?php foreach (['federal' => 'Federal', 'provincial' => 'Provincial (Alberta)'] as $type => $label) {
								$table = $tables[$year][$type] ?? null;
							?>
								<tr>
									<?php if ($type == 'federal') { ?>
										<td class="text-center" rowspan="2" style="vertical-align:middle"><b><?php echo $year; ?></b></td>
									<?php } ?>
									<td><?php echo $label; ?></td>
									<td class="text-center">
										<?php if ($table) { ?>
											<span class="label label-success">Loaded</span>
										<?php } else { ?>
											<span class="label label-warning">Missing</span>
										<?php } ?>
									</td>
									<td><small><?php echo $table ? esc($table['file_name']) : ''; ?></small></td>
									<td class="text-right"><?php echo $table ? $table['total_rows'] : ''; ?></td>
									<td class="text-right"><?php echo $table ? '$ ' . number_format($table['min_pay'], 2) . ' – $ ' . number_format($table['max_pay'], 2) : ''; ?></td>
									<td class="text-center"><small><?php echo $table ? esc($table['name'] ?? '') . '<br>' . $table['date_issue'] : ''; ?></small></td>
									<?php if ($canEdit) { ?>
										<td class="text-center">
											<button type="button" class="btn btn-<?php echo $table ? 'default' : 'primary'; ?> btn-xs btn-tax-table" data-toggle="modal" data-target="#modal" data-year="<?php echo $year; ?>" data-type="<?php echo $type; ?>">
												<span class="glyphicon glyphicon-upload" aria-hidden="true"></span> <?php echo $table ? 'Replace' : 'Upload'; ?>
											</button>
										</td>
									<?php } ?>
								</tr>
							<?php } ?>
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!--INICIO Modal tabla de impuestos -->
<div class="modal fade text-center" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatos">

		</div>
	</div>
</div>
<!--FIN Modal tabla de impuestos -->
<script type="text/javascript" src="<?php echo base_url("assets/js/validate/payroll/taxTableModal.js?v=1.0"); ?>"></script>
