$( document ).ready( function () {

	$( "#formCpp" ).validate( {
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
	$("#formCpp").on("input change", "input, select", function(){
		$(this).valid();
		$("#div_error_cpp").hide();
	});

	$("#btnSubmitCpp").click(function(){

		if ($("#formCpp").valid() != true) {
			return;
		}

		$('#btnSubmitCpp').prop('disabled', true);
		$("#div_error_cpp").hide();
		$("#div_load_cpp").show();

		$.ajax({
			type: "POST",
			url: base_url + "payroll/save_cpp_settings",
			data: $("#formCpp").serialize(),
			dataType: "json",
			cache: false,

			success: function(data){

				$("#div_load_cpp").hide();
				$('#btnSubmitCpp').prop('disabled', false);

				if (data.status === "success") {
					window.location.reload();
					return;
				}

				$("#div_error_cpp").show();
				$("#span_msj_cpp").html(data.message ? data.message : 'Error. Reload the web page.');
			},
			error: function() {
				$("#div_load_cpp").hide();
				$('#btnSubmitCpp').prop('disabled', false);
				$("#div_error_cpp").show();
				$("#span_msj_cpp").html('Error. Reload the web page.');
			}
		});
	});

});
