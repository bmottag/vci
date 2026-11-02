$( document ).ready( function () {

	$( "#formEi" ).validate( {
		errorElement: "em",
		errorPlacement: function ( error, element ) {
			error.addClass( "help-block" );
			error.insertAfter( element );
		},
		highlight: function ( element, errorClass, validClass ) {
			$( element ).closest( ".form-group" ).addClass( "has-error" ).removeClass( "has-success" );
		},
		unhighlight: function (element, errorClass, validClass) {
			$( element ).closest( ".form-group" ).addClass( "has-success" ).removeClass( "has-error" );
		},
		submitHandler: function (form) {
			return true;
		}
	});

	// Al corregir un campo se quita su error y el mensaje del servidor
	$("#formEi").on("input change", "input, select", function(){
		$(this).valid();
		$("#div_error_ei").hide();
	});

	$("#btnSubmitEi").click(function(){

		if ($("#formEi").valid() != true) {
			return;
		}

		$('#btnSubmitEi').prop('disabled', true);
		$("#div_error_ei").hide();
		$("#div_load_ei").show();

		$.ajax({
			type: "POST",
			url: base_url + "payroll/save_ei_settings",
			data: $("#formEi").serialize(),
			dataType: "json",
			cache: false,

			success: function(data){

				$("#div_load_ei").hide();
				$('#btnSubmitEi').prop('disabled', false);

				if (data.status === "success") {
					window.location.reload();
					return;
				}

				$("#div_error_ei").show();
				$("#span_msj_ei").html(data.message ? data.message : 'Error. Reload the web page.');
			},
			error: function() {
				$("#div_load_ei").hide();
				$('#btnSubmitEi').prop('disabled', false);
				$("#div_error_ei").show();
				$("#span_msj_ei").html('Error. Reload the web page.');
			}
		});
	});

});
