<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('consultas');
Auth::guardarPagina();
$db = Database::getInstance();
$proveedoresFiltro = array_column(
    $db->consulta('SELECT idproveedor AS valor,nombre AS etiqueta FROM proveedores ORDER BY nombre'),
    'etiqueta',
    'valor'
);
$pageTitle = 'Consulta de Mantenimientos';
require BASE_PATH . '/app/views/layouts/encabezado.php';
require_once BASE_PATH . '/app/views/layouts/table_filters.php';
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/consultas/mantenimientos.php');</script>
<div class="wrapper"><div class="container-fluid">
  <div class="page-header"><div class="module-header-copy"><h2>Historial de mantenimientos</h2><p>Consulta de intervenciones abiertas y cerradas por equipo.</p></div></div>
  <?php renderTableFilters([
    'search_label' => 'Buscar mantenimientos', 'search_placeholder' => 'Código, serie, equipo, problema o proveedor', 'table_id' => 'tablaConsultaMantenimientos',
    'filters' => [
      ['name' => 'tipo', 'label' => 'Tipo', 'options' => MantenimientoEstado::tipos()],
      ['name' => 'estado', 'label' => 'Estado', 'options' => MantenimientoEstado::estados()],
      ['name' => 'idproveedor', 'label' => 'Proveedor', 'options' => $proveedoresFiltro],
      ['name' => 'fecha_desde', 'label' => 'Ingreso desde', 'type' => 'date'],
      ['name' => 'fecha_hasta', 'label' => 'Ingreso hasta', 'type' => 'date'],
    ],
  ]); ?>
  <div id="datos" aria-live="polite"></div>
</div></div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
