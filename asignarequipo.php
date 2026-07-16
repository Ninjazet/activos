<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('transacciones');
Auth::guardarPagina(__FILE__);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    try {
        if (isset($_POST['add'])) {
            $idempleado = (int)($_POST['empleado'] ?? 0);
            $idequipo   = (int)($_POST['equipo'] ?? 0);
            if ($idempleado <= 0 || $idequipo <= 0) {
                throw new RuntimeException('Debe seleccionar un empleado y un equipo.');
            }
            $db->transaccion(function (Database $db) use ($idempleado, $idequipo) {
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
                $db->ejecutar(
                    "INSERT INTO asignacion (idempleado, idequipo, activa, fecha_asignacion) VALUES (?, ?, 1, NOW())",
                    [$idempleado, $idequipo]
                );
                $db->ejecutar("UPDATE equipo SET estado_equipo=2 WHERE idequipo=?", [$idequipo]);
            });
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'crear', 'asignacion', "emp=$idempleado equipo=$idequipo");
            Auth::flash('success', 'Asignación creada y equipo marcado como asignado.');
        }

        if (isset($_POST['edit'])) {
            $id         = (int)($_POST['idasignacion'] ?? 0);
            $idempleado = (int)($_POST['empleado'] ?? 0);
            $idequipo   = (int)($_POST['equipo'] ?? 0);
            if ($id <= 0 || $idempleado <= 0 || $idequipo <= 0) {
                throw new RuntimeException('Los datos de la asignación no son válidos.');
            }
            $equipoAnterior = $db->transaccion(function (Database $db) use ($id, $idempleado, $idequipo) {
                $asignacion = $db->fila("SELECT idequipo FROM asignacion WHERE idasignacion=? AND activa=1 FOR UPDATE", [$id]);
                if (!$asignacion) {
                    throw new RuntimeException('La asignación ya está cerrada o no existe.');
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
                if ($db->fila("SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1 AND idasignacion<>?", [$idequipo, $id])) {
                    throw new RuntimeException('El equipo ya tiene otra asignación abierta.');
                }
                $db->ejecutar("UPDATE asignacion SET idempleado=?, idequipo=? WHERE idasignacion=?", [$idempleado, $idequipo, $id]);
                if ($anterior !== $idequipo) {
                    $db->ejecutar("UPDATE equipo SET estado_equipo=1 WHERE idequipo=? AND activo=1", [$anterior]);
                }
                $db->ejecutar("UPDATE equipo SET estado_equipo=2 WHERE idequipo=?", [$idequipo]);
                return $anterior;
            });
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'editar', 'asignacion', "#$id emp=$idempleado equipo=$idequipo anterior=$equipoAnterior");
            Auth::flash('success', 'Asignación actualizada correctamente.');
        }

        if (isset($_POST['del'])) {
            $id = (int)($_POST['idAsignacionDel'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('La asignación indicada no es válida.');
            }
            $idequipo = $db->transaccion(function (Database $db) use ($id) {
                $asignacion = $db->fila("SELECT idequipo FROM asignacion WHERE idasignacion=? AND activa=1 FOR UPDATE", [$id]);
                if (!$asignacion) {
                    throw new RuntimeException('La asignación ya fue devuelta o no existe.');
                }
                $idequipo = (int)$asignacion['idequipo'];
                $db->ejecutar("UPDATE asignacion SET activa=0, fecha_devolucion=NOW() WHERE idasignacion=?", [$id]);
                $db->ejecutar("UPDATE equipo SET estado_equipo=1 WHERE idequipo=? AND activo=1", [$idequipo]);
                return $idequipo;
            });
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'devolucion', 'asignacion', "#$id equipo=$idequipo");
            Auth::flash('success', 'Equipo devuelto y disponible nuevamente. El historial fue conservado.');
        }
    } catch (RuntimeException $e) {
        Auth::flash('error', $e->getMessage());
    } catch (PDOException $e) {
        Auth::flash('error', 'No se pudo completar la operación de asignación. Intenta de nuevo.');
    }

    header('Location: ' . BASE_URL . '/asignarequipo.php');
    exit;
}
$pageTitle = 'Asignar Equipos';
require BASE_PATH . '/app/views/layouts/encabezado.php';
Auth::imprimirFlash();
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js"></script>
<script>
$(document).ready(function () { ajaxLoad('<?= BASE_URL ?>/app/ajax/transacciones/asignarequipo.php'); });
$(document).on('keyup', '#buscar', function () {
    ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/transacciones/asignarequipo.php', $(this).val());
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
