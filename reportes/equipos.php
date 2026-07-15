<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('reportes');
Auth::guardarPagina(__FILE__);
$pageTitle = 'Reporte de Equipos';
require BASE_PATH . '/app/views/layouts/encabezado.php';
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js"></script>
<script>
$(document).ready(function(){ ajaxLoad('<?= BASE_URL ?>/app/ajax/reportes/equipos.php'); });
$(document).on('keyup','#buscar',function(){ ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/reportes/equipos.php',$(this).val()); });
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header clearfix">
      <h2 class="pull-left">Reporte de Equipos</h2>
      <button type="button" class="btn btn-success pull-right" onclick="descargarPDF()">
        <i class="fa fa-file-pdf"></i> Descargar PDF
      </button>
    </div>
    <div class="form-group">
      <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
      <br><div id="datos"></div>
    </div>
  </div>
</div>
<script>
function descargarPDF() {
    var q = encodeURIComponent($('#buscar').val() || '');
    window.open('<?= BASE_URL ?>/reportes/descargar_equipos.php?buscar=' + q, '_blank');
}
</script>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
