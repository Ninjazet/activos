<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('seguridad');
Auth::guardarPagina();
$db = Database::getInstance();
$filtroAcciones = array_column(
    $db->consulta("SELECT DISTINCT accion AS valor, accion AS etiqueta FROM bitacora WHERE accion IS NOT NULL AND accion<>'' ORDER BY accion"),
    'etiqueta',
    'valor'
);
$filtroModulos = array_column(
    $db->consulta("SELECT DISTINCT modulo AS valor, modulo AS etiqueta FROM bitacora WHERE modulo IS NOT NULL AND modulo<>'' ORDER BY modulo"),
    'etiqueta',
    'valor'
);
$pageTitle = 'Bitácora de Auditoría';
require BASE_PATH . '/app/views/layouts/encabezado.php';
require_once BASE_PATH . '/app/views/layouts/table_filters.php';
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/seguridad/bitacora.php');
</script>
<div class="wrapper">
    <div class="container-fluid">
        <div class="page-header clearfix">
            <h2>Bitácora de Auditoría</h2>
        </div>
        <?php renderTableFilters([
            'search_label' => 'Buscar en la bitácora',
            'search_placeholder' => 'Usuario, acción, módulo, detalle o IP',
            'table_id' => 'tablaBitacora',
            'filters' => [
                ['name' => 'accion', 'label' => 'Acción', 'options' => $filtroAcciones],
                ['name' => 'modulo', 'label' => 'Módulo', 'options' => $filtroModulos],
                ['name' => 'fecha_desde', 'label' => 'Desde', 'type' => 'date'],
                ['name' => 'fecha_hasta', 'label' => 'Hasta', 'type' => 'date'],
            ],
        ]); ?>
        <div id="datos" aria-live="polite"></div>
    </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
