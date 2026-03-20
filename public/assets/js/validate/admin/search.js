$( document ).ready( function () {
	
	jQuery.validator.addMethod("unCampo", function(value, element, param) {
		var proyecto = $('#proyecto').val();
		var empleado = $('#empleado').val();
		var ciudad = $('#ciudad').val();
		if ( proyecto == "" && empleado == "" && ciudad == "" ) {
			return false;
		}else{
			return true;
		}
	}, "Debe indicar al menos un campo.");

	$( "#formSearch" ).validate( {
		rules: {
			proyecto:		{ unCampo: true },
			empleado:		{ unCampo: true },
			ciudad:			{ unCampo: true }
		},
		errorElement: "em",
		errorPlacement: function ( error, element ) {
			// Add the `help-block` class to the error element
			error.addClass( "help-block" );
			error.insertAfter( element );

		},
		highlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-sm-12" ).addClass( "has-error" ).removeClass( "has-success" );
		},
		unhighlight: function (element, errorClass, validClass) {
			$( element ).parents( ".col-sm-12" ).addClass( "has-success" ).removeClass( "has-error" );
		},
		submitHandler: function (form) {
			return true;
		}
	});
	
	$("#btnSearch").click(function(){		
		if ($("#formSearch").valid() == true){
			var form = document.getElementById('form');
			form.submit();	
		}else
		{
			//alert('Error.');
		}
	});

});