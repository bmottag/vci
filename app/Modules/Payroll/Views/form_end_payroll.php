<script type="text/javascript" src="<?php echo base_url("assets/js/validate/payroll/payrollFinish.js?v=1.0.1"); ?>"></script>

<script>
	$(document).ready(function() {
		$('.js-example-basic-single').select2();
	});
</script>

<!-- Agrega esto en el <head> o antes del cierre de </body> -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
	let map;
	let marker;

	function initMap() {

		if (!navigator.geolocation) {
			alert("Geolocalización no soportada");
			return;
		}

		document.getElementById("viewaddress").value = "Loading...";
		document.getElementById("btnSubmit").disabled = true;

		navigator.geolocation.getCurrentPosition(
			showPosition,
			showError,
			{
				enableHighAccuracy: true,
				timeout: 20000,
				maximumAge: 0
			}
		);
	}

	function showPosition(position) {

		const lat = position.coords.latitude;
		const lng = position.coords.longitude;

		// inputs
		document.getElementById("latitud").value = lat;
		document.getElementById("longitud").value = lng;

		// mapa
		if (map) map.remove();

		map = L.map('map').setView([lat, lng], 14);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap'
		}).addTo(map);

		marker = L.marker([lat, lng]).addTo(map);

		// CI4 reverse geocode
		fetch(`<?= base_url('payroll/reverse-geocode') ?>?lat=${lat}&lon=${lng}`)
			.then(res => {

				if (!res.ok) {
					throw new Error("HTTP error " + res.status);
				}

				return res.json();
			})
			.then(data => {

				const address =
					data && data.display_name
						? data.display_name
						: `${lat}, ${lng}`;

				document.getElementById("viewaddress").value = address;
				document.getElementById("address").value = address;

			})
			.catch(err => {

				console.error(err);

				const fallback = `${lat}, ${lng}`;

				document.getElementById("viewaddress").value = fallback;
				document.getElementById("address").value = fallback;
			})
			.finally(() => {

				// habilitar submit siempre
				document.getElementById("btnSubmit").disabled = false;
			});
	}

	function showError(error) {

		console.error(error);

		alert("No se pudo obtener ubicación");

		document.getElementById("viewaddress").value = "Ubicación no disponible";
		document.getElementById("btnSubmit").disabled = true;
	}

	window.addEventListener('load', initMap);
</script>

<style>
	#map {
		width: 100%;
		height: 180px;
		border-radius: 4px;
		border: 1px solid #ddd;
	}

	.payroll-end .select2-container {
		width: 100% !important;
	}

	.payroll-end .shift-info {
		word-wrap: break-word;
	}

	@media (max-width: 767px) {
		.payroll-end .panel-body {
			padding: 10px;
		}

		.payroll-end .control-label {
			margin-bottom: 4px;
		}

		/* 16px evita el zoom automatico de iOS al enfocar campos */
		.payroll-end .form-control,
		.payroll-end .select2-container .select2-selection__rendered {
			font-size: 16px;
		}

		.payroll-end .alert {
			padding: 10px;
			font-size: 13px;
		}

		.payroll-end #btnSubmit {
			padding: 12px;
			font-size: 18px;
		}
	}
</style>

<div id="page-wrapper" class="payroll-end">

	<br>

	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fa fa-book"></i> <strong>RECORD TASK(S) - PAYROLL</strong>
					<br><small>Time Stamp - Finish</small>
				</div>
				<div class="panel-body">

					<form name="form" id="form" class="form-horizontal" method="post" action="<?php echo base_url("payroll/updatePayroll"); ?>">
						<input type="hidden" id="hddIdentificador" name="hddIdentificador" value="<?php echo $record[0]["id_task"]; ?>" />
						<input type="hidden" id="hddStart" name="hddStart" value="<?php echo $start; ?>" />

						<div class="row">
							<div class="col-sm-8 col-sm-offset-2">
								<div class="alert alert-info shift-info">
									<strong>Job Code/Name: </strong><?php echo $record[0]["job_start"] ?><br>
									<strong>Employee: </strong><?php echo $record[0]["first_name"] . " " . $record[0]["last_name"] ?><br>
									<strong>Date & Time In: </strong><?php echo $record[0]["start"] ?>
								</div>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-4 control-label" for="viewaddress">Address:</label>
							<div class="col-sm-5">
								<div class="input-group">
									<input id="viewaddress" name="viewaddress" class="form-control" type="text" readonly>
									<span class="input-group-btn">
										<a class="btn btn-success" href="<?php echo base_url('payroll/add_payroll'); ?>" title="Refresh location">
											<i class="fa fa-refresh"></i>
										</a>
									</span>
								</div>
								<input id="latitud" name="latitud" type="hidden">
								<input id="longitud" name="longitud" type="hidden">
								<input id="address" name="address" type="hidden">
							</div>
						</div>

						<div class="form-group">
							<div class="col-sm-5 col-sm-offset-4">
								<div id="map"></div>
							</div>
						</div>

						<input id="programming" name="programming" type="hidden" value="<?php echo $programming; ?>">
						<input id="job_programming" name="job_programming" type="hidden" value="<?php echo $job_programming; ?>">
						<input id="job_start" name="job_start" type="hidden" value="<?php echo $record[0]["fk_id_job"] ?>">
						<input id="job_start_name" name="job_start_name" type="hidden" value="<?php echo $record[0]["job_start"] ?>">
						<div class="form-group">
							<label class="col-sm-4 control-label" for="jobName">Job Code/Name:</label>
							<div class="col-sm-5">
								<select name="jobName" id="jobName" class="form-control js-example-basic-single">
									<option value=''>Select...</option>
									<?php for ($i = 0; $i < count($jobs); $i++) { ?>
										<option value="<?php echo $jobs[$i]["id_job"]; ?>"><?php echo $jobs[$i]["job_description"]; ?></option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group" id="div_timeFirstJob" style="display: none;">
							<label class="col-sm-4 control-label" for="timeFirstJob">How many hours did you work for <?php echo $record[0]["job_start"] ?>: </label>
							<div class="col-sm-5">
								<input id="timeFirstJob" name="hours_first_project" class="form-control" type="number" min="0" placeholder="Hours" />
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-4 control-label" for="observation">Observation:</label>
							<div class="col-sm-5">
								<textarea id="observation" name="observation" class="form-control" rows="3"></textarea>
							</div>
						</div>

						<?php if ($record[0]["signature"]) { ?>
							<div class="form-group">
								<div class="col-sm-5 col-sm-offset-4">
									<button type="button" class="btn btn-default btn-block" data-toggle="modal" data-target="#myModal">
										<span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span> View Signature
									</button>
								</div>
							</div>

							<div id="myModal" class="modal fade" role="dialog">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal">×</button>
											<h4 class="modal-title">Employee Signature</h4>
										</div>
										<div class="modal-body text-center"><img src="<?php echo base_url($record[0]["signature"]); ?>" class="img-rounded img-responsive center-block" alt="Management/Safety Advisor Signature" width="304" height="236" /> </div>
										<div class="modal-footer">
											<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>

						<div class="row">
							<div class="col-sm-8 col-sm-offset-2">
								<div class="alert alert-danger">
									By submiting this form, I certify that the unit is fit to be used for the next day.
									Otherwise write an observation.
								</div>

								<div class="alert alert-danger">
									Al enviar este formulario, certifico que la unidad esta en condiciones de ser utilizada para el día siguiente.
									De otra manera escriba una observación.
								</div>
							</div>
						</div>

						<div class="form-group">
							<div class="col-sm-5 col-sm-offset-4">
								<button type="submit" id="btnSubmit" name="btnSubmit" class="btn btn-primary btn-block">
									Submit <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
