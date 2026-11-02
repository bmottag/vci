<?php
	$fieldsEi = [
		'max_insurable_earnings' => ['Maximum annual insurable earnings ($)', '68900'],
		'rate'                   => ['Rate (%)', '1.63'],
	];
?>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title" id="exampleModalLabel">EI CONFIGURATION</h4>
</div>

<div class="modal-body">
	<form name="formEi" id="formEi" role="form" method="post">
		<input type="hidden" id="hddIdEiSetting" name="hddIdEiSetting" value="<?php echo $information ? $information['id_ei_setting'] : ''; ?>"/>

		<div class="row">
			<div class="col-sm-4">
				<div class="form-group text-left">
					<label class="control-label" for="year">Year: *</label>
					<select name="year" id="year" class="form-control" required>
						<option value="">Select...</option>
						<?php for ($y = 2026; $y <= (int)date('Y') + 1; $y++) { ?>
							<option value="<?php echo $y; ?>" <?php echo $information && $information['year'] == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
		</div>

		<hr>
		<h4 class="text-left">Federal EI premium rates and maximums
		<br><small><a href="https://www.canada.ca/en/revenue-agency/services/tax/businesses/topics/payroll/payroll-deductions-contributions/employment-insurance-ei/ei-premium-rates-maximums.html" target="_blank" rel="noopener noreferrer">EI link <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></a></small>
		</h4>

		<div class="row">
		<?php foreach ($fieldsEi as $name => [$label, $placeholder]) { ?>
			<div class="col-sm-6">
				<div class="form-group text-left">
					<label class="control-label" for="<?php echo $name; ?>"><?php echo $label; ?>: *</label>
					<input type="number" step="any" min="0" id="<?php echo $name; ?>" name="<?php echo $name; ?>" class="form-control"
						value="<?php echo $information ? (float)$information[$name] : ''; ?>" placeholder="<?php echo $placeholder; ?>" required>
				</div>
			</div>
		<?php } ?>
		</div>

		<div class="form-group">
			<div class="row" align="center">
				<div style="width:50%;" align="center">
					<button type="button" id="btnSubmitEi" name="btnSubmitEi" class="btn btn-primary">
						Save <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
					</button>
				</div>
			</div>
		</div>

		<div class="form-group">
			<div id="div_load_ei" style="display:none">
				<div class="progress progress-striped active">
					<div class="progress-bar" role="progressbar" style="width: 100%">Saving...</div>
				</div>
			</div>
			<div id="div_error_ei" style="display:none">
				<div class="alert alert-danger"><span class="glyphicon glyphicon-remove" id="span_msj_ei">&nbsp;</span></div>
			</div>
		</div>
	</form>
</div>
