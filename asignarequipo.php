<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('transacciones');
Auth::guardarPagina(__FILE__);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    // ---- INSERTAR ----
    if (isset($_POST['add'])) {
        $idempleado = (int)($_POST['empleado'] ?? 0);
        $idequipo   = (int)($_POST['equipo'] ?? 0);

        if ($idempleado <= 0 || $idequipo <= 0) {
            Auth::flash('error', 'Debe seleccionar empleado y equipo.');
        } else {
            $yaAsignado = $db->fila(
                "SELECT asg.idasignacion, CONCAT(em.nombre,' ',em.apellidos) AS empleado
                 FROM asignacion asg
                 INNER JOIN empleados em ON asg.idempleado = em.idempleado
                 WHERE asg.idequipo = ? AND asg.activa = 1",
                [$idequipo]
            );
            if ($yaAsignado) {
                Auth::flash('error', 'Ese equipo ya está asignado a ' . $yaAsignado['empleado'] . '. Quita esa asignación primero.');
            } else {
                try {
                    $db->ejecutar(
                        "INSERT INTO asignacion (idempleado, idequipo, activa, fecha_asignacion) VALUES (?, ?, 1, NOW())",
                        [$idempleado, $idequipo]
                    );
                    Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'crear', 'asignacion', "emp=$idempleado equipo=$idequipo");
                    Auth::flash('success', 'Asignación creada correctamente.');
                } catch (PDOException $e) {
                    Auth::flash('error', 'No se pudo crear la asignación. Verifica los datos seleccionados.');
                }
            }
        }
    }

    // ---- EDITAR ----
    if (isset($_POST['edit'])) {
        $id         = (int)($_POST['idasignacion'] ?? 0);
        $idempleado = (int)($_POST['empleado'] ?? 0);
        $idequipo   = (int)($_POST['equipo'] ?? 0);

        $yaAsignado = $db->fila(
            "SELECT asg.idasignacion, CONCAT(em.nombre,' ',em.apellidos) AS empleado
             FROM asignacion asg
             INNER JOIN empleados em ON asg.idempleado = em.idempleado
             WHERE asg.idequipo = ? AND asg.activa = 1 AND asg.idasignacion <> ?",
            [$idequipo, $id]
        );
        if ($yaAsignado) {
            Auth::flash('error', 'Ese equipo ya está asignado a ' . $yaAsignado['empleado'] . '. Quita esa asignación primero.');
        } else {
            try {
                $db->ejecutar(
                    "UPDATE asignacion SET idempleado=?, idequipo=? WHERE idasignacion=?",
                    [$idempleado, $idequipo, $id]
                );
                Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'editar', 'asignacion', "#$id emp=$idempleado equipo=$idequipo");
                Auth::flash('success', 'Asignación actualizada correctamente.');
            } catch (PDOException $e) {
                Auth::flash('error', 'No se pudo actualizar. Verifica los datos seleccionados.');
            }
        }
    }

    

    // ---- DEVOLVER EQUIPO (cierre lógico: guarda fecha de devolución) ----
    if (isset($_POST['del'])) {
        $id = (int)($_POST['idAsignacionDel'] ?? 0);
        $db->ejecutar(
            "UPDATE asignacion SET activa=0, fecha_devolucion=NOW() WHERE idasignacion=?",
            [$id]
        );
        Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'devolucion', 'asignacion', "#$id");
        Auth::flash('success', 'Equipo devuelto. La asignación quedó registrada en el historial.');
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
