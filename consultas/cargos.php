<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('consultas');
Auth::guardarPagina(__FILE__);
$pageTitle = 'Consulta Cargos';
require BASE_PATH . '/app/views/layouts/encabezado.php';
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
$(document).ready(function(){ ajaxLoad('<?= BASE_URL ?>/app/ajax/consultas/cargos.php'); });
$(document).on('keyup','#buscar',function(){ ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/consultas/cargos.php',$(this).val()); });
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <h2>Consulta de Cargos</h2>
    </div>
    <div class="form-group">
      <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
      <br><div id="datos"></div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
