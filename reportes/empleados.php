<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('reportes');
Auth::guardarPagina();
$db = Database::getInstance();
$filtroAreas = array_column($db->consulta("SELECT idarea AS valor, descripcionarea AS etiqueta FROM areas ORDER BY descripcionarea"), 'etiqueta', 'valor');
$filtroCargos = array_column($db->consulta("SELECT idcargo AS valor, descripcioncargo AS etiqueta FROM cargos ORDER BY descripcioncargo"), 'etiqueta', 'valor');
$pageTitle = 'Reporte de Empleados';
require BASE_PATH . '/app/views/layouts/encabezado.php';
require_once BASE_PATH . '/app/views/layouts/table_filters.php';
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/reportes/empleados.php');
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header clearfix">
      <h2>Reporte de Empleados</h2>
      <button type="button" class="btn btn-success" onclick="descargarPDF()">
        <i class="fa fa-file-pdf"></i> Descargar PDF
      </button>
    </div>
    <?php renderTableFilters([
      'search_label' => 'Buscar empleados', 'search_placeholder' => 'Nombre, teléfono, correo, área o cargo', 'table_id' => 'datosE',
      'filters' => [
        ['name' => 'estado_empleado', 'label' => 'Estado', 'options' => [1 => 'Activo', 0 => 'Inactivo']],
        ['name' => 'idarea', 'label' => 'Área', 'options' => $filtroAreas],
        ['name' => 'idcargo', 'label' => 'Cargo', 'options' => $filtroCargos],
      ],
    ]); ?>
    <div id="datos" aria-live="polite"></div>
  </div>
</div>
<script>
function descargarPDF() {
    window.open('<?= BASE_URL ?>/reportes/descargar_empleados.php?' + tableFilterQueryString(), '_blank');
}
</script>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
