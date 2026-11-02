<script type="text/javascript" src="<?php echo base_url("assets/js/validate/payroll/periodModal.js?v=1.0"); ?>"></script>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title" id="exampleModalLabel">PERIOD WEEKS
	<br><small>Period: <?php echo $period ? date('l, F j, Y', strtotime($period['date_start'])) . ' - ' . date('l, F j, Y', strtotime($period['date_finish'])) : ''; ?></small>
	</h4>
</div>

<div class="modal-body">
	<?php if (!$period || $period['approved'] == 1) { ?>
		<div class="alert alert-warning">The period is approved or does not exist, the weeks cannot be edited.</div>
	<?php } else { ?>
	<form name="formPeriod" id="formPeriod" role="form" method="post">
		<input type="hidden" id="hddIdPeriod" name="hddIdPeriod" value="<?php echo $period['id_period']; ?>"/>
		<input type="hidden" id="hddDateStart" value="<?php echo $period['date_start']; ?>"/>
		<input type="hidden" id="hddDateFinish" value="<?php echo $period['date_finish']; ?>"/>

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group text-left">
					<label class="control-label">Week 1 start:</label>
					<input type="text" class="form-control" value="<?php echo $period['date_start']; ?>" disabled>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group text-left">
					<label class="control-label" for="weak_1_finish">Week 1 end: *</label>
					<input type="date" id="weak_1_finish" name="weak_1_finish" class="form-control"
						value="<?php echo $weeks[1]['date_weak_finish'] ?? ''; ?>"
						min="<?php echo $period['date_start']; ?>"
						max="<?php echo date('Y-m-d', strtotime('-1 day', strtotime($period['date_finish']))); ?>" required>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-6">
				<div class="form-group text-left">
					<label class="control-label">Week 2 start:</label>
					<input type="text" id="weak_2_start" class="form-control" value="<?php echo $weeks[2]['date_weak_start'] ?? ''; ?>" disabled>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group text-left">
					<label class="control-label">Week 2 end:</label>
					<input type="text" class="form-control" value="<?php echo $period['date_finish']; ?>" disabled>
				</div>
			</div>
		</div>

		<div class="form-group">
			<div class="row" align="center">
				<div style="width:50%;" align="center">
					<button type="button" id="btnSubmitPeriod" name="btnSubmitPeriod" class="btn btn-primary">
						Save <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
					</button>
				</div>
			</div>
		</div>

		<div class="form-group">
			<div id="div_load_period" style="display:none">
				<div class="progress progress-striped active">
					<div class="progress-bar" role="progressbar" style="width: 100%">Saving...</div>
				</div>
			</div>
			<div id="div_error_period" style="display:none">
				<div class="alert alert-danger"><span class="glyphicon glyphicon-remove" id="span_msj_period">&nbsp;</span></div>
			</div>
		</div>
	</form>
	<?php } ?>
</div>
