$( document ).ready( function () {

	$( "#tablaDatos" ).on( "click", "#btnSubmitTaxTable", function(){

		var form = $("#formTaxTable");
		if (!$("#taxFile").val()) {
			$("#div_error_tax").show();
			$("#span_msj_tax").html('Select the CSV file.');
			return;
		}

		$('#btnSubmitTaxTable').prop('disabled', true);
		$("#div_error_tax").hide();
		$("#div_load_tax").show();

		$.ajax({
			type: "POST",
			url: base_url + "payroll/save_tax_table",
			data: new FormData(form[0]),
			processData: false,
			contentType: false,
			dataType: "json",
			cache: false,

			success: function(data){

				$("#div_load_tax").hide();
				$('#btnSubmitTaxTable').prop('disabled', false);

				if (data.status === "success") {
					window.location.reload();
					return;
				}

				$("#div_error_tax").show();
				$("#span_msj_tax").html(data.message ? data.message : 'Error. Reload the web page.');
			},
			error: function() {
				$("#div_load_tax").hide();
				$('#btnSubmitTaxTable').prop('disabled', false);
				$("#div_error_tax").show();
				$("#span_msj_tax").html('Error. Reload the web page.');
			}
		});
	});

	$( "#tablaDatos" ).on( "change", "#taxFile", function(){
		$("#div_error_tax").hide();
	});

});
