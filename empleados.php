<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('maestros');
Auth::guardarPagina(__FILE__);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    // ---- INSERTAR ----
    if (isset($_POST['add'])) {
        $nombre    = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $edad      = (int)($_POST['edad'] ?? 0);
        $idarea    = (int)($_POST['idarea'] ?? 0);
        $idcargo   = (int)($_POST['idcargo'] ?? 0);
        $idsexo    = (int)($_POST['idsexo'] ?? 0);
        $telefono  = trim($_POST['telefono'] ?? '')  ?: null;
        $correo    = strtolower(trim($_POST['correo'] ?? '')) ?: null;
        $direccion = trim($_POST['direccion'] ?? '') ?: null;

        if ($correo !== null && (strlen($correo) > 150 || !filter_var($correo, FILTER_VALIDATE_EMAIL))) {
            Auth::flash('error', 'Ingresa un correo electrónico válido.');
            header('Location: ' . BASE_URL . '/empleados.php');
            exit;
        }
        $imagen    = '';

        try {
            if (!Upload::estaVacio($_FILES['archivo'] ?? null)) {
                $archivoGuardado = Upload::guardarImagen($_FILES['archivo'], IMG_EMPLEADOS, 'emp');
                $imagen = 'public/img/empleados/' . $archivoGuardado;
            }

            $db->ejecutar(
                "INSERT INTO empleados (nombre, apellidos, edad, telefono, correo, direccion, imagen, idarea, idcargo, idsexo, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                [$nombre, $apellidos, $edad, $telefono, $correo, $direccion, $imagen, $idarea, $idcargo, $idsexo]
            );
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'crear', 'empleados', "$nombre $apellidos");
            Auth::flash('success', 'Empleado creado correctamente.');
        } catch (\RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
        } catch (PDOException $e) {
            Auth::flash('error', 'No se pudo crear: verifica que el área y el cargo seleccionados sean válidos.');
        }
    }

    // ---- EDITAR ----
    if (isset($_POST['edit'])) {
        $id        = (int)($_POST['idempleado'] ?? 0);
        $nombre    = trim($_POST['nombreAct'] ?? '');
        $apellidos = trim($_POST['apellidosAct'] ?? '');
        $edad      = (int)($_POST['edadAct'] ?? 0);
        $idarea    = (int)($_POST['areaAct'] ?? 0);
        $idcargo   = (int)($_POST['cargoAct'] ?? 0);
        $idsexo    = (int)($_POST['sexoAct'] ?? 0);
        $telefono  = trim($_POST['telefonoAct'] ?? '')  ?: null;
        $correo    = strtolower(trim($_POST['correoAct'] ?? '')) ?: null;
        $direccion = trim($_POST['direccionAct'] ?? '') ?: null;

        if ($correo !== null && (strlen($correo) > 150 || !filter_var($correo, FILTER_VALIDATE_EMAIL))) {
            Auth::flash('error', 'Ingresa un correo electrónico válido.');
            header('Location: ' . BASE_URL . '/empleados.php');
            exit;
        }

        try {
            if (!Upload::estaVacio($_FILES['archivoAct'] ?? null)) {
                $archivoGuardado = Upload::guardarImagen($_FILES['archivoAct'], IMG_EMPLEADOS, 'emp');
                $db->ejecutar(
                    "UPDATE empleados SET nombre=?, apellidos=?, edad=?, telefono=?, correo=?, direccion=?,
                     imagen=?, idarea=?, idcargo=?, idsexo=? WHERE idempleado=?",
                    [$nombre, $apellidos, $edad, $telefono, $correo, $direccion,
                     'public/img/empleados/' . $archivoGuardado, $idarea, $idcargo, $idsexo, $id]
                );
            } else {
                $db->ejecutar(
                    "UPDATE empleados SET nombre=?, apellidos=?, edad=?, telefono=?, correo=?, direccion=?,
                     idarea=?, idcargo=?, idsexo=? WHERE idempleado=?",
                    [$nombre, $apellidos, $edad, $telefono, $correo, $direccion, $idarea, $idcargo, $idsexo, $id]
                );
            }
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'editar', 'empleados', "$nombre $apellidos (#$id)");
            Auth::flash('success', 'Empleado actualizado correctamente.');
        } catch (\RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
        } catch (PDOException $e) {
            Auth::flash('error', 'No se pudo actualizar: verifica que el área y el cargo seleccionados sean válidos.');
        }
    }

    // ---- ELIMINAR / REACTIVAR (baja lógica reversible) ----
    if (isset($_POST['del'])) {
        $idEmpleadoDel = (int)($_POST['idEmpleadoDel'] ?? 0);
        $filaActual    = $db->fila("SELECT activo FROM empleados WHERE idempleado=?", [$idEmpleadoDel]);

        if (!$filaActual) {
            Auth::flash('error', 'El empleado indicado no existe.');
        } elseif ((int)$filaActual['activo'] === 1) {
            // Una asignación abierta debe cerrarse antes de inactivar al empleado.
            $asignacionesAbiertas = $db->contar(
                "SELECT COUNT(*) FROM asignacion WHERE idempleado=? AND activa=1",
                [$idEmpleadoDel]
            );
            $tieneUsuario = $db->fila("SELECT idusuario FROM usuarios WHERE idempleado=? AND estado=1", [$idEmpleadoDel]);

            if ($asignacionesAbiertas > 0) {
                $detalle = $asignacionesAbiertas === 1 ? '1 asignación abierta' : "$asignacionesAbiertas asignaciones abiertas";
                Auth::flash('error', "No se puede inactivar al empleado: tiene $detalle. Debes devolver los equipos primero.");
            } elseif ($tieneUsuario) {
                Auth::flash('error', 'No se puede inactivar: este empleado tiene una cuenta de usuario activa. Desactiva primero esa cuenta en Seguridad.');
            } else {
                $db->ejecutar("UPDATE empleados SET activo=0 WHERE idempleado=?", [$idEmpleadoDel]);
                Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'eliminar', 'empleados', "#$idEmpleadoDel");
                Auth::flash('success', 'Empleado dado de baja correctamente.');
            }
        } else {
            $db->ejecutar("UPDATE empleados SET activo=1 WHERE idempleado=?", [$idEmpleadoDel]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'reactivar', 'empleados', "#$idEmpleadoDel");
            Auth::flash('success', 'Empleado reactivado correctamente.');
        }
    }

    // Patrón Post-Redirect-Get: evita que F5 vuelva a enviar el formulario
    header('Location: ' . BASE_URL . '/empleados.php');
    exit;
}

$pageTitle = 'Empleados';
require BASE_PATH . '/app/views/layouts/encabezado.php';
Auth::imprimirFlash();
?>

<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script src="<?= BASE_URL ?>/public/js/catalogos-contextuales.js?v=<?= @filemtime(BASE_PATH . '/public/js/catalogos-contextuales.js') ?: APP_VERSION ?>"></script>
<script>
$(document).ready(function () { ajaxLoad('<?= BASE_URL ?>/app/ajax/maestros/empleados.php'); });
$(document).on('input', '#buscar', function () {
    ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/maestros/empleados.php', $(this).val());
});
</script>

<div class="wrapper">
    <div class="container-fluid">
        <div class="page-header">
            <div class="module-header-copy">
                <h2>Empleados</h2>
                <p>Administra los datos, el estado y la asignación del personal.</p>
            </div>
            <div class="page-header-actions">
                <button type="button" class="btn btn-primary"
                        data-toggle="modal" data-target="#newModal">
                    <i class="fa fa-user-plus" aria-hidden="true"></i>
                    <span>Agregar empleado</span>
                </button>
            </div>
        </div>
        <div class="page-toolbar" role="search">
            <label for="buscar" class="sr-only">Buscar empleados</label>
            <input type="search" id="buscar" class="form-control"
                   placeholder="Buscar por nombre, teléfono, correo o área"
                   autocomplete="off" aria-controls="tablaEmp">
        </div>
        <div id="datos" aria-live="polite"></div>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
