$( document ).ready( function () {

	var balance = parseFloat($("#hddBalance").val()) || 0;

	$( "#formBankTime" ).validate( {
		rules: {
			hours: {
				// al quitar horas no se puede dejar el saldo negativo
				max: function () {
					return $("input[name='movement']:checked").val() === "subtract" ? balance : 9999;
				}
			}
		},
		messages: {
			hours: {
				max: function () {
					return $("input[name='movement']:checked").val() === "subtract"
						? "You cannot remove more than the current balance (" + balance.toFixed(3) + " h)."
						: "Enter a valid number of hours.";
				}
			}
		},
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

	// Vista previa del nuevo saldo
	function updateNewBalance() {
		var hours = parseFloat($("#hours").val()) || 0;
		var subtract = $("input[name='movement']:checked").val() === "subtract";
		var newBalance = subtract ? balance - hours : balance + hours;
		$("#newBalance")
			.text(newBalance.toFixed(3) + " h")
			.toggleClass("text-danger", newBalance < 0)
			.toggleClass("text-primary", newBalance >= 0);
	}

	// Al corregir un campo se quita su error y el mensaje del servidor
	$("#formBankTime").on("input change", "input, textarea", function(){
		updateNewBalance();
		if ($("#hours").val() !== "") {
			$("#hours").valid();
		}
		$("#div_error_bank").hide();
	});

	$("#btnSubmitBankTime").click(function(){

		if ($("#formBankTime").valid() != true) {
			return;
		}

		$('#btnSubmitBankTime').prop('disabled', true);
		$("#div_error_bank").hide();
		$("#div_load_bank").show();

		$.ajax({
			type: "POST",
			url: base_url + "payroll/save_bank_time",
			data: $("#formBankTime").serialize(),
			dataType: "json",
			cache: false,

			success: function(data){

				$("#div_load_bank").hide();
				$('#btnSubmitBankTime').prop('disabled', false);

				if (data.status === "success") {
					window.location.reload();
					return;
				}

				$("#div_error_bank").show();
				$("#span_msj_bank").html(data.message ? data.message : 'Error. Reload the web page.');
			},
			error: function() {
				$("#div_load_bank").hide();
				$('#btnSubmitBankTime').prop('disabled', false);
				$("#div_error_bank").show();
				$("#span_msj_bank").html('Error. Reload the web page.');
			}
		});
	});

});
