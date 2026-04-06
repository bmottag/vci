<?php
/**
 * Componente de captura de firma reutilizable.
 *
 * Variables esperadas:
 * - $imageUrl       => ruta de la firma existente (opcional)
 * - $formAction     => URL a la que se enviará la firma
 * - $hiddenName     => nombre del campo oculto que recibirá la firma (default: 'image')
 * - $width, $height => tamaño del canvas (opcional, $width = '100%', $height = '30vh')
 */

$hiddenName = $hiddenName ?? 'image';
$width  = $width ?? '100%';
$height = $height ?? '30vh';
?>

<div class="signature-panel">
    <div class="alert alert-info">
        The signature is personal and must be authorized with user credentials.
    </div>

    <div class="text-center mb-2">
        <?php if(!empty($imageUrl)): ?>
            <!-- Ver firma existente -->
            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#signatureViewModal">
                <span class="glyphicon glyphicon-eye-open"></span> View Signature
            </button>

            <div id="signatureViewModal" class="modal fade" role="dialog">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">×</button>
                            <h4 class="modal-title">Current Signature</h4>
                        </div>
                        <div class="modal-body text-center">
                            <img src="<?= base_url($imageUrl) ?>" class="img-rounded" alt="Signature" style="max-width:100%; height:auto;">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Botón para abrir modal de captura -->
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#signaturePadModal">
            <span class="glyphicon glyphicon-edit"></span> Sign
        </button>
    </div>
</div>

<!-- Modal de captura -->
<div id="signaturePadModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="signatureForm" method="post" action="<?= $formAction ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h4 class="modal-title">Sign here</h4>
                </div>
                <div class="modal-body text-center">
                    <canvas id="signatureCanvas" style="border:1px solid #ccc; width:<?= $width ?>; height:<?= $height ?>;"></canvas>
                    <input type="hidden" name="<?= $hiddenName ?>" id="signatureInput">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" id="clearSignature">Clear</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS: SignaturePad -->
<script src="<?= base_url('assets/signature_pad/js/signature_pad.js') ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var canvas = document.getElementById('signatureCanvas');
    var signaturePad = new SignaturePad(canvas);

    // Evitar scroll táctil mientras se firma
    canvas.style.touchAction = "none";

    function resizeCanvas() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear();
    }

    // Redimensionar solo cuando el modal está visible
    $('#signaturePadModal').on('shown.bs.modal', function () {
        resizeCanvas();
    });

    // Limpiar canvas
    document.getElementById('clearSignature').addEventListener('click', function(){
        signaturePad.clear();
    });

    // Antes de enviar el formulario
    document.getElementById('signatureForm').addEventListener('submit', function(e){
        if(signaturePad.isEmpty()){
            e.preventDefault();
            alert('Please provide a signature first.');
        } else {
            document.getElementById('signatureInput').value = signaturePad.toDataURL();
        }
    });
});
</script>