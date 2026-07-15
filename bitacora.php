<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('seguridad');
Auth::guardarPagina(__FILE__);
$pageTitle = 'Bitácora de Auditoría';
require BASE_PATH . '/app/views/layouts/encabezado.php';
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js"></script>
<script>
$(document).ready(function () { ajaxLoad('<?= BASE_URL ?>/app/ajax/seguridad/bitacora.php'); });
$(document).on('keyup', '#buscar', function () {
    ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/seguridad/bitacora.php', $(this).val());
});
</script>
<div class="wrapper">
    <div class="container-fluid">
        <div class="page-header clearfix">
            <h2 class="pull-left">Bitácora de Auditoría</h2>
        </div>
        <div class="form-group">
            <input type="text" id="buscar" class="form-control" placeholder="Buscar por usuario, acción o módulo...">
            <br><div id="datos"></div>
        </div>
    </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
