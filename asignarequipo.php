<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('transacciones');
Auth::guardarPagina(__FILE__);

$db = Database::getInstance();

function obtenerDatosEntregaAsignacion(array $origen): array {
    $condicionesValidas = ['Nuevo', 'Excelente', 'Bueno', 'Regular'];
    $condicion = is_string($origen['condicion_entrega'] ?? null)
        ? trim($origen['condicion_entrega'])
        : '';
    $otros = is_string($origen['entrega_otros'] ?? null)
        ? trim($origen['entrega_otros'])
        : '';
    $observaciones = is_string($origen['observaciones_entrega'] ?? null)
        ? trim($origen['observaciones_entrega'])
        : '';

    if (!in_array($condicion, $condicionesValidas, true)) {
        throw new RuntimeException('Selecciona una condición de entrega válida.');
    }
    if (strlen($otros) > 255) {
        throw new RuntimeException('El detalle de otros accesorios supera el tamaño permitido.');
    }
    if (strlen($observaciones) > 500) {
        throw new RuntimeException('Las observaciones de entrega superan el tamaño permitido.');
    }

    return [
        'condicion' => $condicion,
        'cargador' => isset($origen['entrega_cargador']) ? 1 : 0,
        'maletin' => isset($origen['entrega_maletin']) ? 1 : 0,
        'otros' => $otros !== '' ? $otros : null,
        'observaciones' => $observaciones !== '' ? $observaciones : null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    try {
        if (isset($_POST['add'])) {
            $idempleado = (int)($_POST['empleado'] ?? 0);
            $idequipo = (int)($_POST['equipo'] ?? 0);
            $entrega = obtenerDatosEntregaAsignacion($_POST);
            if ($idempleado <= 0 || $idequipo <= 0) {
                throw new RuntimeException('Debe seleccionar un empleado y un equipo.');
            }

            $idAsignacion = $db->transaccion(function (Database $db) use ($idempleado, $idequipo, $entrega): int {
                $empleado = $db->fila("SELECT activo FROM empleados WHERE idempleado=? FOR UPDATE", [$idempleado]);
                $equipo = $db->fila("SELECT activo, estado_equipo FROM equipo WHERE idequipo=? FOR UPDATE", [$idequipo]);
                if (!$empleado || (int)$empleado['activo'] !== 1) {
                    throw new RuntimeException('El empleado seleccionado no está activo.');
                }
                if (!$equipo || (int)$equipo['activo'] !== 1) {
                    throw new RuntimeException('El equipo seleccionado está dado de baja.');
                }
                if ((int)$equipo['estado_equipo'] !== 1) {
                    throw new RuntimeException('El equipo ya no está disponible para asignación.');
                }
                if ($db->fila("SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1", [$idequipo])) {
                    throw new RuntimeException('El equipo ya tiene una asignación abierta.');
                }

                $id = (int)$db->ejecutar(
                    "INSERT INTO asignacion
                        (idempleado, idequipo, activa, fecha_asignacion, condicion_entrega,
                         entrega_cargador, entrega_maletin, entrega_otros, observaciones_entrega,
                         requiere_firma_entrega)
                     VALUES (?, ?, 1, NOW(), ?, ?, ?, ?, ?, 1)",
                    [
                        $idempleado, $idequipo, $entrega['condicion'], $entrega['cargador'],
                        $entrega['maletin'], $entrega['otros'], $entrega['observaciones'],
                    ]
                );
                $db->ejecutar("UPDATE equipo SET estado_equipo=2 WHERE idequipo=?", [$idequipo]);
                return $id;
            });

            Auth::registrarBitacora(
                (int)Auth::get('idusuario'),
                Auth::get('usuario'),
                'crear',
                'asignacion',
                "#$idAsignacion emp=$idempleado equipo=$idequipo"
            );
            Auth::flash('success', 'Asignación creada con su checklist de entrega. El equipo quedó marcado como asignado.');
        }

        if (isset($_POST['edit'])) {
            $id = (int)($_POST['idasignacion'] ?? 0);
            $idempleado = (int)($_POST['empleado'] ?? 0);
            $idequipo = (int)($_POST['equipo'] ?? 0);
            $entrega = obtenerDatosEntregaAsignacion($_POST);
            if ($id <= 0 || $idempleado <= 0 || $idequipo <= 0) {
                throw new RuntimeException('Los datos de la asignación no son válidos.');
            }

            $equipoAnterior = $db->transaccion(function (Database $db) use ($id, $idempleado, $idequipo, $entrega): int {
                $asignacion = $db->fila(
                    "SELECT idequipo, firma FROM asignacion WHERE idasignacion=? AND activa=1 FOR UPDATE",
                    [$id]
                );
                if (!$asignacion) {
                    throw new RuntimeException('La asignación ya está cerrada o no existe.');
                }
                if (!empty($asignacion['firma'])) {
                    throw new RuntimeException('Una asignación con acta firmada no puede editarse. Debe conservarse exactamente como fue aceptada.');
                }

                $anterior = (int)$asignacion['idequipo'];
                $empleado = $db->fila("SELECT activo FROM empleados WHERE idempleado=? FOR UPDATE", [$idempleado]);
                $equipo = $db->fila("SELECT activo, estado_equipo FROM equipo WHERE idequipo=? FOR UPDATE", [$idequipo]);
                if (!$empleado || (int)$empleado['activo'] !== 1) {
                    throw new RuntimeException('El empleado seleccionado no está activo.');
                }
                if (!$equipo || (int)$equipo['activo'] !== 1) {
                    throw new RuntimeException('El equipo seleccionado está dado de baja.');
                }
                if ($idequipo !== $anterior && (int)$equipo['estado_equipo'] !== 1) {
                    throw new RuntimeException('El nuevo equipo no está disponible.');
                }
                if ($db->fila(
                    "SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1 AND idasignacion<>?",
                    [$idequipo, $id]
                )) {
                    throw new RuntimeException('El equipo ya tiene otra asignación abierta.');
                }

                $db->ejecutar(
                    "UPDATE asignacion
                     SET idempleado=?, idequipo=?, condicion_entrega=?, entrega_cargador=?,
                         entrega_maletin=?, entrega_otros=?, observaciones_entrega=?
                     WHERE idasignacion=?",
                    [
                        $idempleado, $idequipo, $entrega['condicion'], $entrega['cargador'],
                        $entrega['maletin'], $entrega['otros'], $entrega['observaciones'], $id,
                    ]
                );
                if ($anterior !== $idequipo) {
                    $db->ejecutar("UPDATE equipo SET estado_equipo=1 WHERE idequipo=? AND activo=1", [$anterior]);
                }
                $db->ejecutar("UPDATE equipo SET estado_equipo=2 WHERE idequipo=?", [$idequipo]);
                return $anterior;
            });

            Auth::registrarBitacora(
                (int)Auth::get('idusuario'),
                Auth::get('usuario'),
                'editar',
                'asignacion',
                "#$id emp=$idempleado equipo=$idequipo anterior=$equipoAnterior"
            );
            Auth::flash('success', 'Asignación y checklist actualizados correctamente.');
        }

        if (isset($_POST['del'])) {
            throw new RuntimeException('La devolución debe completarse desde el formulario con condición física y firma de recepción.');
        }
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
Auth::imprimirFlash();
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
const asignacionUrlAjax = '<?= BASE_URL ?>/app/ajax/transacciones/asignarequipo.php';
const preseleccionAsignacion = {
    preseleccionar_empleado: <?= json_encode($preseleccionarEmpleado) ?>,
    preseleccionar_equipo: <?= json_encode($preseleccionarEquipo) ?>
};

$(document).ready(function () {
    ajaxLoad(asignacionUrlAjax, '', preseleccionAsignacion);
});
$(document).on('input', '#buscar', function () {
    ajaxLoadDebounced(asignacionUrlAjax, $(this).val());
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
                <div class="form-group">
                    <label for="buscar" class="visually-hidden">Buscar por empleado, equipo, código o asignación</label>
                    <input type="search" name="buscar" id="buscar" class="form-control" placeholder="Buscar empleado, equipo o código..." autocomplete="off"><br>
                    <div id="datos"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
