<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('transacciones');
Auth::guardarPagina();

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    try {
        $resultado = (new AsignacionService($db))->procesar($_POST);
        Auth::registrarBitacora(
            (int)Auth::get('idusuario'),
            Auth::get('usuario'),
            $resultado['accion'],
            'asignacion',
            $resultado['detalle']
        );
        Auth::flash('success', $resultado['mensaje']);
    } catch (RuntimeException $e) {
        Auth::flash('error', $e->getMessage());
    } catch (PDOException $e) {
        error_log('GestActivos - Error de asignación: ' . $e->getMessage());
        Auth::flash('error', 'No se pudo completar la operación de asignación. Intenta de nuevo.');
    }

    header('Location: ' . BASE_URL . '/asignarequipo.php');
    exit;
}

$preseleccionarEmpleado = max(0, (int)($_GET['idempleado'] ?? 0));
$preseleccionarEquipo = max(0, (int)($_GET['idequipo'] ?? 0));

$pageTitle = 'Asignar Equipos';
require BASE_PATH . '/app/views/layouts/encabezado.php';
require_once BASE_PATH . '/app/views/layouts/table_filters.php';
Auth::imprimirFlash();
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
const asignacionUrlAjax = '<?= BASE_URL ?>/app/ajax/transacciones/asignarequipo.php';
const preseleccionAsignacion = {
    preseleccionar_empleado: <?= json_encode($preseleccionarEmpleado) ?>,
    preseleccionar_equipo: <?= json_encode($preseleccionarEquipo) ?>
};

let aplicarPreseleccionAsignacion = true;
initAjaxTableFilters(asignacionUrlAjax, function () {
    if (!aplicarPreseleccionAsignacion) {
        return {};
    }
    aplicarPreseleccionAsignacion = false;
    return preseleccionAsignacion;
});
</script>
<div class="wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header clearfix">
                    <h2>Asignaciones activas</h2>
                    <div class="page-header-actions">
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newModal">
                            <i class="fa fa-laptop-file" aria-hidden="true"></i> Nueva asignación
                        </a>
                    </div>
                </div>
                <ol class="assignment-flow" aria-label="Flujo de una asignación">
                    <li><span class="assignment-step">1</span><span><strong>Registrar entrega</strong><small>Empleado, equipo, condición y accesorios</small></span></li>
                    <li><span class="assignment-step">2</span><span><strong>Firmar el acta</strong><small>La firma confirma la recepción del equipo</small></span></li>
                    <li><span class="assignment-step">3</span><span><strong>Recibir devolución</strong><small>Condición final, accesorios y firma de IT</small></span></li>
                </ol>
                <?php renderTableFilters([
                    'search_label' => 'Buscar asignaciones',
                    'search_placeholder' => 'Empleado, equipo, código o asignación',
                    'table_id' => 'datosE',
                    'filters' => [
                        ['name' => 'condicion_entrega', 'label' => 'Condición de entrega', 'options' => ['Nuevo' => 'Nuevo', 'Excelente' => 'Excelente', 'Bueno' => 'Bueno', 'Regular' => 'Regular']],
                        ['name' => 'firma_entrega', 'label' => 'Firma de entrega', 'options' => ['firmada' => 'Firmada', 'pendiente' => 'Pendiente', 'no_requerida' => 'No requerida']],
                        ['name' => 'fecha_desde', 'label' => 'Asignada desde', 'type' => 'date'],
                        ['name' => 'fecha_hasta', 'label' => 'Asignada hasta', 'type' => 'date'],
                    ],
                ]); ?>
                <div id="datos" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
