<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('consultas');
Auth::guardarPagina(__FILE__);
$pageTitle = 'Consulta Asignaciones';
require BASE_PATH . '/app/views/layouts/encabezado.php';
require_once BASE_PATH . '/app/views/layouts/table_filters.php';
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/consultas/asignaciones.php');
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <h2>Consulta de Asignaciones</h2>
    </div>
    <?php renderTableFilters([
      'search_label' => 'Buscar asignaciones', 'search_placeholder' => 'Empleado, equipo, código o área', 'table_id' => 'tablaAsg',
      'filters' => [
        ['name' => 'estado_asignacion', 'label' => 'Estado', 'options' => ['activa' => 'Activa', 'cerrada' => 'Devuelta']],
        ['name' => 'resultado_equipo', 'label' => 'Resultado del equipo', 'options' => [1 => 'Disponible', 3 => 'En mantenimiento', 4 => 'Perdido o robado', 5 => 'Dado de baja']],
        ['name' => 'fecha_desde', 'label' => 'Asignada desde', 'type' => 'date'],
        ['name' => 'fecha_hasta', 'label' => 'Asignada hasta', 'type' => 'date'],
      ],
    ]); ?>
    <div id="datos" aria-live="polite"></div>
  </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
