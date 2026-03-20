$( document ).ready( function () {


	function calcularSuma() {
		let apartamentos = parseInt($("#apartamentos").val()) || 0;
		let locales = parseInt($("#locales").val()) || 0;
		$("#hhpp").val(apartamentos + locales);
	}

	$("#apartamentos, #locales").on("blur", calcularSuma);
	
			
	$("#consecutivo_predio").bloquearTexto().maxlength(12);
	$("#pisos").bloquearTexto().maxlength(5);
	$("#apartamentos").bloquearTexto().maxlength(5);
	$("#locales").bloquearTexto().maxlength(5);

	$( "#form" ).validate( {
		rules: {
			fecha: 				{ required: true },
			proyecto: 				{ required: true },
			consecutivo_predio: 	{ required: true, maxlength: 12 },
			tipo_calle:				{ required: true },
			numero_calle: 			{ required: true, maxlength: 12 },
			placa_inicio: 			{ required: true, maxlength: 10 },
			placa_fin: 				{ required: true, maxlength: 10 },
			pisos:					{ required: true, maxlength: 5 },
			apartamentos:			{ required: true, maxlength: 5 },
			locales: 				{ required: true, maxlength: 5 },
			coordenadas: 			{ required: true }
		},
		errorElement: "em",
		errorPlacement: function ( error, element ) {
			// Add the `help-block` class to the error element
			error.addClass( "help-block" );
			error.insertAfter( element );

		},
		highlight: function ( element, errorClass, validClass ) {
			$( element ).parents( ".col-sm-5" ).addClass( "has-error" ).removeClass( "has-success" );
			$( element ).parents( ".col-sm-2" ).addClass( "has-error" ).removeClass( "has-success" );
		},
		unhighlight: function (element, errorClass, validClass) {
			$( element ).parents( ".col-sm-5" ).addClass( "has-success" ).removeClass( "has-error" );
			$( element ).parents( ".col-sm-2" ).addClass( "has-success" ).removeClass( "has-error" );
		},
		submitHandler: function (form) {
			return true;
		}
	});
	
	$("#btnSubmit").click(function(){		
	
		if ($("#form").valid() == true){
		
				//Activa icono guardando
				$('#btnSubmit').attr('disabled','-1');
				$("#div_guardado").css("display", "none");
				$("#div_error").css("display", "none");
				$("#div_msj").css("display", "none");
				$("#div_cargando").css("display", "inline");

				var formData = new FormData($("#form")[0]);

				$.ajax({
					type: "POST",	
					url: base_url + "admin/save_predios",	
					data: formData,
					dataType: "json",
					processData: false,
					contentType: false,
					cache: false,
					
					success: function(data){
                                            
						if( data.result == "error" )
						{
							//alert(data.mensaje);
							$("#div_cargando").css("display", "none");
							$('#btnSubmit').removeAttr('disabled');							
							$("#div_error").css("display", "inline");
							$("#span_msj").html(data.mensaje);
							return false;
						} 

						if( data.result )//true
						{	                                                        
							$("#div_cargando").css("display", "none");
							$("#div_guardado").css("display", "inline");
							$('#btnSubmit').removeAttr('disabled');

							var url = base_url + "admin/predios";
							$(location).attr("href", url);
						}
						else
						{
							alert('Error. Reload the web page.');
							$("#div_cargando").css("display", "none");
							$("#div_error").css("display", "inline");
							$('#btnSubmit').removeAttr('disabled');
						}	
					},
					error: function(result) {
						alert('Error. Reload the web page.');
						$("#div_cargando").css("display", "none");
						$("#div_error").css("display", "inline");
						$('#btnSubmit').removeAttr('disabled');
					}
					
				});	
		
		}//if			
	});

});