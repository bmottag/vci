$( document ).ready( function () {

	// Muestra el inicio de la semana 2 (dia siguiente al fin de la semana 1)
	$("#weak_1_finish").change(function(){
		var value = $(this).val();
		if (!value) {
			$("#weak_2_start").val('');
			return;
		}
		var parts = value.split('-');
		var next = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
		next.setUTCDate(next.getUTCDate() + 1);
		$("#weak_2_start").val(next.toISOString().substring(0, 10));
	});

	$("#btnSubmitPeriod").click(function(){

		var value = $("#weak_1_finish").val();
		if (!value || value < $("#hddDateStart").val() || value >= $("#hddDateFinish").val()) {
			$("#div_error_period").show();
			$("#span_msj_period").html('Week 1 must end inside the period and before its last day.');
			return;
		}

		$('#btnSubmitPeriod').prop('disabled', true);
		$("#div_error_period").hide();
		$("#div_load_period").show();

		$.ajax({
			type: "POST",
			url: base_url + "payroll/save_period_weeks",
			data: $("#formPeriod").serialize(),
			dataType: "json",
			cache: false,

			success: function(data){

				$("#div_load_period").hide();
				$('#btnSubmitPeriod').prop('disabled', false);

				if (data.status === "success") {
					window.location.reload();
					return;
				}

				$("#div_error_period").show();
				$("#span_msj_period").html(data.message ? data.message : 'Error. Reload the web page.');
			},
			error: function() {
				$("#div_load_period").hide();
				$('#btnSubmitPeriod').prop('disabled', false);
				$("#div_error_period").show();
				$("#span_msj_period").html('Error. Reload the web page.');
			}
		});
	});

});
