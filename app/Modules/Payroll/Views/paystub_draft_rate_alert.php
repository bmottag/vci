<?php
/**
 * Aviso del paystub en borrador cuando el rate o el tipo del empleado cambiaron despues de dar Apply.
 * Se incluye desde list_payroll y list_payroll_subcontractor (usa sus variables del foreach).
 */
if (!$draftChanged) {
	return;
}
$draftRate = $infoPaystub[0]["employee_rate_paystub"];
$draftType = $infoPaystub[0]["employee_type_paystub"] == 1 ? 'Field' : 'Admin';
$nowType   = $currentType == 1 ? 'Field' : 'Admin';
?>
<?php if ($usingCurrent) { ?>
	<div class="alert alert-info" style="margin:8px 0 0">
		<i class="fa fa-info-circle"></i> Using the employee's current values: rate <b>$ <?php echo esc($currentHourRate); ?></b>, type <b><?php echo $nowType; ?></b>.
		The draft was saved with rate <b>$ <?php echo esc($draftRate); ?></b>, type <b><?php echo $draftType; ?></b>.
		Click <b>Apply</b> to save the paystub with the current values.
		<a class="btn btn-xs btn-default" href="<?php echo $pageUrl; ?>">Keep draft values</a>
	</div>
<?php } else { ?>
	<div class="alert alert-warning" style="margin:8px 0 0">
		<i class="fa fa-exclamation-triangle"></i> This draft was saved with rate <b>$ <?php echo esc($draftRate); ?></b>, type <b><?php echo $draftType; ?></b>.
		The employee's current values are rate <b>$ <?php echo esc($currentHourRate); ?></b>, type <b><?php echo $nowType; ?></b>.
		<a class="btn btn-xs btn-warning" href="<?php echo $pageUrl . '?useCurrent=' . $lista['fk_id_user']; ?>">Use current values</a>
	</div>
<?php } ?>
