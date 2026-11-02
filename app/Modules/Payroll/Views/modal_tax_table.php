<?php
	$labels = ['federal' => 'Federal', 'provincial' => 'Provincial (Alberta)'];
	$label  = $labels[$tableType] ?? '';
?>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title" id="exampleModalLabel">UPLOAD <?php echo strtoupper(esc($label)); ?> TAX TABLE <?php echo (int)$year; ?></h4>
</div>

<div class="modal-body">
	<form name="formTaxTable" id="formTaxTable" role="form" method="post" enctype="multipart/form-data">
		<input type="hidden" name="year" value="<?php echo (int)$year; ?>"/>
		<input type="hidden" name="tableType" value="<?php echo esc($tableType); ?>"/>

		<div class="form-group text-left">
			<label class="control-label" for="taxFile">CSV file: *</label>
			<input type="file" id="taxFile" name="taxFile" class="form-control" accept=".csv" required>
			<p class="help-block"><small>
				Columns: <code>From, Less than, CC 0, CC 1, ... CC 10</code>. A header row is optional.
				Example: <code>801,805,96.35,.40,,,,,,,,,</code>
			</small></p>
		</div>

		<div class="form-group">
			<button type="button" id="btnSubmitTaxTable" name="btnSubmitTaxTable" class="btn btn-primary">
				Upload <span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
			</button>
		</div>

		<div class="form-group">
			<div id="div_load_tax" style="display:none">
				<div class="progress progress-striped active">
					<div class="progress-bar" role="progressbar" style="width: 100%">Uploading...</div>
				</div>
			</div>
			<div id="div_error_tax" style="display:none">
				<div class="alert alert-danger"><span class="glyphicon glyphicon-remove" id="span_msj_tax">&nbsp;</span></div>
			</div>
		</div>
	</form>
</div>
