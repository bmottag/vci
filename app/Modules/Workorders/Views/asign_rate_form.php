<script>
	$(function() {

		$(".btn-default").click(function() {
			var oID = $(this).attr("id");

			//Activa icono guardando
			if (window.confirm('Are you sure you want to load the general configuration data?')) {
				$(".btn-default").attr('disabled', '-1');
				$.ajax({
					type: 'POST',
					url: base_url + 'workorders/load_prices_wo',
					data: {
						'identificador': oID
					},
					cache: false,
					success: function(data) {

						if (data.status == "error") {
							alert(data.mensaje);
							$(".btn-default").removeAttr('disabled');
							return false;
						}

						if (data.status == "success") //true
						{
							$(".btn-default").removeAttr('disabled');
							window.location.href = base_url + "workorders/view_workorder/" + data.idWO
						} else {
							alert('Error. Reload the web page.');
							$(".btn-default").removeAttr('disabled');
						}
					},
					error: function(result) {
						alert('Error. Reload the web page.');
						$(".btn-default").removeAttr('disabled');
					}

				});
			}
		});

		$(".btn-amarello").click(function() {
			var oID = $(this).attr("id");

			//Activa icono guardando
			if (window.confirm('Are you sure you want to load the Markup?')) {
				$(".btn-amarello").attr('disabled', '-1');
				$.ajax({
					type: 'POST',
					url: base_url + 'workorders/load_markup_wo',
					data: {
						'identificador': oID
					},
					cache: false,
					success: function(data) {

						if (data.result == "error") {
							alert(data.mensaje);
							$(".btn-amarello").removeAttr('disabled');
							return false;
						}

						if (data.result) //true
						{
							$(".btn-amarello").removeAttr('disabled');

							var url = base_url + "workorders/view_workorder/" + data.idWO
							$(location).attr("href", url);
						} else {
							alert('Error. Reload the web page.');
							$(".btn-amarello").removeAttr('disabled');
						}
					},
					error: function(result) {
						alert('Error. Reload the web page.');
						$(".btn-amarello").removeAttr('disabled');
					}

				});
			}
		});

		$(".btn-calculate-expenses").click(function() {
			var oID = $(this).attr("id");

			//Activa icono guardando
			if (window.confirm('Do you want to recalculate expenses?')) {
				$(".btn-calculate-expenses").attr('disabled', '-1');
				$.ajax({
					type: 'POST',
					url: base_url + 'workorders/recalculate_expenses',
					data: {
						'identificador': oID
					},
					cache: false,
					success: function(data) {
						if (data.result == "error") {
							$(".btn-calculate-expenses").removeAttr('disabled');
							return false;
						}

						if (data.result) //true
						{
							$(".btn-calculate-expenses").removeAttr('disabled');

							var url = base_url + "workorders/view_workorder/" + data.idWO
							$(location).attr("href", url);
						} else {
							alert('Error. Reload the web page.');
							$(".btn-calculate-expenses").removeAttr('disabled');
						}
					},
					error: function(result) {
						alert('Error. Reload the web page.');
						$(".btn-calculate-expenses").removeAttr('disabled');
					}

				});
			}
		});

		$(".btn-warning").click(function() {
			var oID = $(this).attr("id");
			$.ajax({
				type: 'POST',
				url: base_url + 'workorders/cargarModalPersonal',
				data: {
					'idWorkorder': oID
				},
				cache: false,
				success: function(data) {
					$('#tablaDatos').html(data);
				}
			});
		});

		$(".btn-success").click(function() {
			var oID = $(this).attr("id");
			$.ajax({
				type: 'POST',
				url: base_url + 'workorders/cargarModalMaterials',
				data: {
					'idWorkorder': oID
				},
				cache: false,
				success: function(data) {
					$('#tablaDatosMaterial').html(data);
				}
			});
		});

		$(".btn-info").click(function() {
			var oID = $(this).attr("id");
			$.ajax({
				type: 'POST',
				url: base_url + 'workorders/cargarModalEquipment',
				data: {
					'idWorkorder': oID
				},
				cache: false,
				success: function(data) {
					$('#tablaDatosEquipment').html(data);
				}
			});
		});

		$(".btn-ocasional").click(function() {
			var oID = $(this).attr("id");
			$.ajax({
				type: 'POST',
				url: base_url + 'workorders/cargarModalOcasional',
				data: {
					'idWorkorder': oID
				},
				cache: false,
				success: function(data) {
					$('#tablaDatosOcasional').html(data);
				}
			});
		});

		$(".btn-violeta").click(function() {
			var oID = $(this).attr("id");
			$.ajax({
				type: 'POST',
				url: base_url + 'workorders/cargarModalReceipts',
				data: {
					'idWorkorder': oID
				},
				cache: false,
				success: function(data) {
					$('#tablaDatosReceipt').html(data);
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
					<a class="btn btn-gris btn-xs" href=" <?php echo base_url() . 'workorders/search/y'; ?> "><span class="glyphicon glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Go back </a>
					<i class="fa fa-money"></i> <strong>WORK ORDERS</strong>
				</div>
				<div class="panel-body">

					<ul class="nav nav-pills">
						<li><a href="<?php echo base_url('workorders/add_workorder/' . $information[0]["id_workorder"]) ?>">Edit</a>
						</li>
						<li class='active'><a href="<?php echo base_url('workorders/view_workorder/' . $information[0]["id_workorder"]) ?>">Asign rate</a>
						</li>
						<li><a href="<?php echo base_url('workorders/generaWorkOrderPDF/' . $information[0]["id_workorder"]) ?>" target="_blank">Download invoice</a>
						</li>
						<?php
						$userRol = session()->get("rol");
						if (($userRol == ID_ROL_SUPER_ADMIN || $userRol == ID_ROL_ENGINEER || $userRol == ID_ROL_WORKORDER) && $information[0]['state'] != 4) {
						?>
							<li><a href="<?php echo base_url('workorders/workorder_expenses/' . $information[0]["id_workorder"]) ?>">Workorder Expenses</a>
							</li>

							<?php if($workorderOcasional): ?>
								<li><a href="<?php echo base_url('workorders/subcontractor_invoices/' . $information[0]["id_workorder"]) ?>">Subcontractors Invoices</a></li>
							<?php endif; ?>
						<?php } ?>
					</ul>
					<br>

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

					<?php
					if ($information) {
						switch ($information[0]['state']) {
							case 0:
								$valor = 'On Field';
								$clase = "alert-danger";
								break;
							case 1:
								$valor = 'In Progress';
								$clase = "alert-warning";
								break;
							case 2:
								$valor = 'Revised';
								$clase = "alert-info";
								break;
							case 3:
								$valor = 'Send to the Client';
								$clase = "alert-success";
								break;
							case 4:
								$valor = 'Closed';
								$clase = "alert-danger";
								break;
							case 5:
								$valor = 'Accounting';
								$clase = "text-warning";
								$icono = "fa-list-alt";
								break;
						}
					?>
						<div class="row">
							<div class="col-lg-12">
								<div class="alert <?php echo $clase; ?>">
									<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
									This work order is <strong><?php echo $valor; ?></strong>
								</div>
							</div>
						</div>

					<?php } ?>

					<div class="row">
						<div class="col-lg-12">
							<div class="alert alert-info">
								<span class='fa fa-money' aria-hidden='true'></span>
								<strong>Work Order #: </strong><?php echo $information[0]["id_workorder"]; ?>
								<br><span class='fa fa-clock-o' aria-hidden='true'></span> <strong>Work Order Date: </strong><?php echo $information[0]["date"]; ?>
								<br><span class="fa fa-briefcase" aria-hidden="true"></span> <strong>Job Code/Name: </strong><?php echo $information[0]["job_description"]; ?>
								<?php if ($information[0]["notes"]) { ?>
									<br><strong>Job Code/Name - Notes: </strong><?php echo $information[0]["notes"]; ?>
								<?php } ?>
								<br><strong>Markup: </strong><?php echo $information[0]["markup"] . '%'; ?>
								<br><strong>Supervisor: </strong><?php echo $information[0]["name"]; ?>
								<br><strong>Observation: </strong><?php echo $information[0]["observation"]; ?>

								<br><br>
								<span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
								Update Rates from the following button. <small>(Update the rate field on PERSONAL, MATERIALS and EQUIPMENT) </small>
								<button type="button" id="<?php echo $information[0]["id_workorder"]; ?>" class='btn btn-default btn-xs' title="Update" <?php echo $deshabilitar; ?>>
									Update Rates <i class="fa fa-refresh"></i>
								</button>

								<?php if ($information[0]["markup"] > 0) { ?>
									<br><br>
									<span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
									Update Job Code/Name Markup button. <small>(Update the Markup field on MATERIALS, RECEIPT and OCASIONAL) </small>
									<button type="button" id="<?php echo $information[0]["id_workorder"]; ?>" class='btn btn-amarello btn-xs' title="Update" <?php echo $deshabilitar; ?>>
										Update Markup <i class="fa fa-refresh"></i>
									</button>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>

<!--INICIO Modal para PERSONAL -->
<div class="modal fade text-center" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatos">

		</div>
	</div>
</div>
<!--FIN Modal para PERSONAL -->

<!--INICIO Modal para MATERIAL -->
<div class="modal fade text-center" id="modalMaterials" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatosMaterial">

		</div>
	</div>
</div>
<!--FIN Modal para MATERIAL -->

<!--INICIO Modal para EQUIPMENT -->
<div class="modal fade text-center" id="modalEquipment" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatosEquipment">

		</div>
	</div>
</div>
<!--FIN Modal para EQUIPMENT -->

<!--INICIO Modal para OCASIONAL-->
<div class="modal fade text-center" id="modalOcasional" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatosOcasional">

		</div>
	</div>
</div>
<!--FIN Modal para OCASIONAL -->

<!--INICIO Modal para RECEIPT-->
<div class="modal fade text-center" id="modalReceipt" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content" id="tablaDatosReceipt">

		</div>
	</div>
</div>
<!--FIN Modal para RECEIPT -->