<script type="text/javascript" src="<?= base_url("assets/js/validate/payroll/payrollStart.js?v=1.0.0"); ?>"></script>

<script>
	$(document).ready(function() {
		$('.js-example-basic-single').select2();
	});
</script>

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

<div id="page-wrapper">
	<br>

	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<i class="fa fa-book"></i> <strong>RECORD TASK(S) - PAYROLL</strong>
					<br><small>Time Stamp - Start</small>
				</div>

				<div class="panel-body">

					<form name="form" id="form" class="form-horizontal" method="post" action="<?= base_url("payroll/savePayroll"); ?>">

						<!-- Task -->
						<input type="hidden" id="hddTask" name="hddTask" value="1" />

						<div class="form-group">
							<label class="col-sm-4 control-label" for="address">Address:</label>

							<div class="col-sm-4">
								<input id="viewaddress" name="viewaddress" class="form-control" type="text" readonly>

								<input id="latitud" name="latitud" type="hidden">
								<input id="longitud" name="longitud" type="hidden">
								<input id="address" name="address" type="hidden">
							</div>

							<div class="col-sm-1">
								<a class="btn btn-success btn-circle" href="<?= base_url('payroll/add_payroll'); ?>">
									<i class="fa fa-refresh"></i>
								</a>
							</div>
						</div>

						<div class="form-group">
							<div class="row" align="center">
								<div style="width:80%;" align="center">
									<div id="map" style="width: 100%; height: 150px"></div>
								</div>
							</div>
						</div>

						<input id="programming" name="programming" type="hidden" value="<?= $programming; ?>">

						<div class="form-group">
							<label class="col-sm-4 control-label" for="jobName">Job Code/Name:</label>

							<div class="col-sm-5">
								<select name="jobName" id="jobName" class="form-control js-example-basic-single">
									<option value=''>Select...</option>

									<?php for ($i = 0; $i < count($jobs); $i++) { ?>
										<option value="<?= $jobs[$i]["id_job"]; ?>"
											<?php if ($job_programming == $jobs[$i]["id_job"]) echo "selected"; ?>>
											<?= $jobs[$i]["job_description"]; ?>
										</option>
									<?php } ?>

								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-6 control-label small">
								I certify to be clean for the last 8 hours...
							</label>

							<div class="col-sm-3">
								<select name="certify" id="certify" class="form-control" required>
									<option value="">Select...</option>
									<option value="1">Yes</option>
									<option value="2">No</option>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-6 control-label small">
								I certify to be well-rested...
							</label>

							<div class="col-sm-3">
								<select name="slept_certify" id="slept_certify" class="form-control" required>
									<option value="">Select...</option>
									<option value="1">Yes</option>
									<option value="2">No</option>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-4 control-label">Task/Report Description:</label>

							<div class="col-sm-5">
								<textarea id="taskDescription" name="taskDescription" class="form-control" rows="3"></textarea>
							</div>
						</div>

						<div class="row" align="center">
							<div style="width:50%;" align="center">
								<button type="submit" id="btnSubmit" name="btnSubmit" class="btn btn-primary">
									Submit <span class="glyphicon glyphicon-log-in"></span>
								</button>
							</div>
						</div>

					</form>

				</div>
			</div>
		</div>
	</div>
</div>