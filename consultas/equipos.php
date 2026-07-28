<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('consultas');
Auth::guardarPagina(__FILE__);
$db = Database::getInstance();
$filtroMarcas = array_column($db->consulta("SELECT idmarca AS valor, nombreMarca AS etiqueta FROM marca ORDER BY nombreMarca"), 'etiqueta', 'valor');
$filtroModelos = array_column($db->consulta("SELECT idmodelo AS valor, nombreModelo AS etiqueta FROM modelo ORDER BY nombreModelo"), 'etiqueta', 'valor');
$filtroTipos = array_column($db->consulta("SELECT DISTINCT tipo_equipo AS valor, tipo_equipo AS etiqueta FROM equipo WHERE tipo_equipo IS NOT NULL AND tipo_equipo<>'' ORDER BY tipo_equipo"), 'etiqueta', 'valor');
$pageTitle = 'Consulta Equipos';
require BASE_PATH . '/app/views/layouts/encabezado.php';
require_once BASE_PATH . '/app/views/layouts/table_filters.php';
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/consultas/equipos.php');
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <h2>Consulta de Equipos</h2>
    </div>
    <?php renderTableFilters([
      'search_label' => 'Buscar equipos', 'search_placeholder' => 'Código, serie, tipo, marca o modelo', 'table_id' => 'tablaConsEquipo',
      'filters' => [
        ['name' => 'estado_equipo', 'label' => 'Estado operativo', 'options' => [1 => 'Disponible', 2 => 'Asignado', 3 => 'En mantenimiento', 4 => 'Perdido o robado', 5 => 'Dado de baja']],
        ['name' => 'tipo_equipo', 'label' => 'Tipo', 'options' => $filtroTipos],
        ['name' => 'idmarca', 'label' => 'Marca', 'options' => $filtroMarcas],
        ['name' => 'idmodelo', 'label' => 'Modelo', 'options' => $filtroModelos],
        ['name' => 'activo', 'label' => 'Registro', 'options' => [1 => 'Activo', 0 => 'Inactivo']],
      ],
    ]); ?>
    <div id="datos" aria-live="polite"></div>
  </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
