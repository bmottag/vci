<script type="text/javascript" src="<?php echo base_url("assets/js/validate/payroll/bankTimeModal.js?v=1.0"); ?>"></script>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title" id="exampleModalLabel">BANK TIME - ADD / REMOVE HOURS
		<br><small>Employee: <?php echo esc($user['first_name'] . ' ' . $user['last_name']); ?></small>
	</h4>
</div>

<div class="modal-body">
	<form name="formBankTime" id="formBankTime" role="form" method="post">
		<input type="hidden" id="hddIdUser" name="hddIdUser" value="<?php echo $user['id_user']; ?>"/>
		<input type="hidden" id="hddBalance" value="<?php echo round($balance, 3); ?>"/>

		<div class="well well-sm">
			<div class="row">
				<div class="col-xs-6">
					<small class="text-muted text-uppercase">Current balance</small>
					<div style="font-size:20px;font-weight:bold"><?php echo number_format($balance, 3); ?> h</div>
				</div>
				<div class="col-xs-6">
					<small class="text-muted text-uppercase">New balance</small>
					<div style="font-size:20px;font-weight:bold" class="text-primary" id="newBalance"><?php echo number_format($balance, 3); ?> h</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group text-left">
					<label class="control-label">Movement: *</label>
					<div class="radio" style="margin-top:0">
						<label><input type="radio" name="movement" value="add" checked> Add hours to the bank</label>
					</div>
					<div class="radio">
						<label><input type="radio" name="movement" value="subtract" <?php echo $balance > 0 ? '' : 'disabled'; ?>> Remove hours from the bank</label>
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group text-left">
					<label class="control-label" for="hours">Hours: *</label>
					<input type="number" step="any" min="0.01" id="hours" name="hours" class="form-control" placeholder="e.g. 40.5" required>
					<p class="help-block"><small>Use decimals: 0.25 = 15 min, 0.5 = 30 min, 0.75 = 45 min</small></p>
				</div>
			</div>
		</div>

		<div class="form-group text-left">
			<label class="control-label" for="observation">Note: *</label>
			<textarea id="observation" name="observation" class="form-control" rows="2" maxlength="500" placeholder="e.g. Starting balance carried over from 2025" required></textarea>
		</div>

		<div class="form-group">
			<div class="row" align="center">
				<div style="width:50%;" align="center">
					<button type="button" id="btnSubmitBankTime" name="btnSubmitBankTime" class="btn btn-primary">
						Save <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
					</button>
				</div>
			</div>
		</div>

		<div class="form-group">
			<div id="div_load_bank" style="display:none">
				<div class="progress progress-striped active">
					<div class="progress-bar" role="progressbar" style="width: 100%">Saving...</div>
				</div>
			</div>
			<div id="div_error_bank" style="display:none">
				<div class="alert alert-danger"><span class="glyphicon glyphicon-remove" id="span_msj_bank">&nbsp;</span></div>
			</div>
		</div>
	</form>
</div>
