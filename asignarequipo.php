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
                         entrega_cargador, entrega_maletin, entrega_otros, observaciones_entrega)
                     VALUES (?, ?, 1, NOW(), ?, ?, ?, ?, ?)",
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
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js"></script>
<script>
const asignacionUrlAjax = '<?= BASE_URL ?>/app/ajax/transacciones/asignarequipo.php';
const preseleccionAsignacion = {
    preseleccionar_empleado: <?= json_encode($preseleccionarEmpleado) ?>,
    preseleccionar_equipo: <?= json_encode($preseleccionarEquipo) ?>
};

$(document).ready(function () {
    ajaxLoad(asignacionUrlAjax, '', preseleccionAsignacion);
});
$(document).on('keyup', '#buscar', function () {
    ajaxLoadDebounced(asignacionUrlAjax, $(this).val());
});
</script>
<div class="wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header clearfix">
                    <h2 class="pull-left">Asignación de Equipos</h2>
                    <a href="#" class="btn btn-primary pull-right"
                       data-toggle="modal" data-target="#newModal">+ Nueva Asignación</a>
                </div>
                <div class="form-group">
                    <input type="text" name="buscar" id="buscar" class="form-control" placeholder="Buscar..."><br>
                    <div id="datos"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>