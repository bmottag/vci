<script type="text/javascript" src="<?php echo base_url("assets/js/validate/payroll/cppSettingsModal.js?v=1.2"); ?>"></script>
<?php
	$fieldsCpp = [
		'basic_exemption'       => ['Basic exemption amount ($)', '3500'],
		'contribution_rate'     => ['Employee and employer contribution rate (%)', '5.95'],
		'max_contribution'      => ['Maximum annual employee and employer contribution ($)', ''],
	];
	$fieldsCpp2 = [
		'ympe'                  => ['YMPE - Maximum pensionable earnings ($)', ''],
		'yampe'                 => ['YAMPE - Additional maximum pensionable earnings ($)', ''],
		'cpp2_rate'             => ['CPP2 employee and employer contribution rate (%)', '4'],
		'cpp2_max_contribution' => ['CPP2 maximum annual employee and employer contribution ($)', ''],
	];
?>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title" id="exampleModalLabel">CPP CONFIGURATION</h4>
</div>

<div class="modal-body">
	<form name="formCpp" id="formCpp" role="form" method="post">
		<input type="hidden" id="hddIdCppSetting" name="hddIdCppSetting" value="<?php echo $information ? $information['id_cpp_setting'] : ''; ?>"/>

		<div class="row">
			<div class="col-sm-3">
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

		<?php foreach ([$fieldsCpp, $fieldsCpp2] as $section => $fields) { ?>
			<?php if ($section == 0) { ?>
				<hr>
				<h4 class="text-left">CPP contribution rates, maximums and exemptions
				<br><small><a href="https://www.canada.ca/en/revenue-agency/services/tax/businesses/topics/payroll/payroll-deductions-contributions/canada-pension-plan-cpp/cpp-contribution-rates-maximums-exemptions.html" target="_blank" rel="noopener noreferrer">CPP link <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></a></small>
				</h4>
			<?php } ?>

			<?php if ($section == 1) { ?>
				<hr>
				<h4 class="text-left">Second additional CPP contribution (CPP2) rates and maximums
				<br><small><a href="https://www.canada.ca/en/revenue-agency/services/tax/businesses/topics/payroll/calculating-deductions/making-deductions/second-additional-cpp-contribution-rates-maximums.html" target="_blank" rel="noopener noreferrer">CPP2 link <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></a></small>
				</h4>
			<?php } ?>
			<div class="row">
			<?php foreach ($fields as $name => [$label, $placeholder]) { ?>
				<div class="col-sm-6">
					<div class="form-group text-left">
						<label class="control-label" for="<?php echo $name; ?>"><?php echo $label; ?>: *</label>
						<input type="number" step="any" min="0" id="<?php echo $name; ?>" name="<?php echo $name; ?>" class="form-control"
							value="<?php echo $information ? (float)$information[$name] : ''; ?>" placeholder="<?php echo $placeholder; ?>" required>
					</div>
				</div>
			<?php } ?>
			</div>
		<?php } ?>

		<div class="form-group">
			<div class="row" align="center">
				<div style="width:50%;" align="center">
					<button type="button" id="btnSubmitCpp" name="btnSubmitCpp" class="btn btn-primary">
						Save <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
					</button>
				</div>
			</div>
		</div>

		<div class="form-group">
			<div id="div_load_cpp" style="display:none">
				<div class="progress progress-striped active">
					<div class="progress-bar" role="progressbar" style="width: 100%">Saving...</div>
				</div>
			</div>
			<div id="div_error_cpp" style="display:none">
				<div class="alert alert-danger"><span class="glyphicon glyphicon-remove" id="span_msj_cpp">&nbsp;</span></div>
			</div>
		</div>
	</form>
</div>
