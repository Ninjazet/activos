<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('seguridad');
Auth::guardarPagina(__FILE__);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    // ---- INSERTAR ----
    if (isset($_POST['add'])) {
        $usuario    = trim($_POST['usuario'] ?? '');
        $pass       = trim($_POST['pass'] ?? '');
        $idempleado = (int)($_POST['empleado'] ?? 0);

        $yaExiste    = $db->fila("SELECT idusuario FROM usuarios WHERE username=?", [$usuario]);
        $yaVinculado = $db->fila("SELECT idusuario FROM usuarios WHERE idempleado=?", [$idempleado]);

        if ($usuario === '' || $pass === '' || $idempleado <= 0) {
            Auth::flash('error', 'Usuario, contraseña y empleado son obligatorios.');
        } elseif ($yaExiste) {
            Auth::flash('error', 'Ese nombre de usuario ya existe.');
        } elseif ($yaVinculado) {
            Auth::flash('error', 'Ese empleado ya tiene una cuenta de usuario.');
        } else {
            $permisos = [
                isset($_POST['maestros'])      ? 1 : 0,
                isset($_POST['transacciones']) ? 1 : 0,
                isset($_POST['consultas'])     ? 1 : 0,
                isset($_POST['reportes'])      ? 1 : 0,
                isset($_POST['seguridad'])     ? 1 : 0,
            ];
            $passHash = Auth::hashPassword($pass);

            try {
                $db->transaccion(function (Database $db) use ($usuario, $passHash, $idempleado, $permisos) {
                    $idusuario = $db->ejecutar(
                        "INSERT INTO usuarios (username, pass, idempleado, estado) VALUES (?, ?, ?, 1)",
                        [$usuario, $passHash, $idempleado]
                    );
                    $db->ejecutar(
                        "INSERT INTO permisos (idusuario, datosmaestros, transacciones, consultas, reportes, seguridad)
                         VALUES (?, ?, ?, ?, ?, ?)",
                        array_merge([$idusuario], $permisos)
                    );
                });
                Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'crear', 'usuarios', $usuario);
                Auth::flash('success', 'Usuario creado correctamente.');
            } catch (\Throwable $e) {
                Auth::flash('error', 'No se pudo crear el usuario. Intenta de nuevo.');
            }
        }
    }

    // ---- EDITAR ----
    if (isset($_POST['edit'])) {
        $id      = (int)($_POST['idusuario'] ?? 0);
        $usuario = trim($_POST['usuarioAct'] ?? '');
        $pass    = trim($_POST['passAct'] ?? '');

        $yaExiste = $db->fila("SELECT idusuario FROM usuarios WHERE username=? AND idusuario<>?", [$usuario, $id]);

        if ($usuario === '') {
            Auth::flash('error', 'El nombre de usuario no puede quedar vacío.');
        } elseif ($yaExiste) {
            Auth::flash('error', 'Ese nombre de usuario ya lo usa otra cuenta.');
        } else {
            $permisos = [
                isset($_POST['maestrosAct'])      ? 1 : 0,
                isset($_POST['transaccionesAct']) ? 1 : 0,
                isset($_POST['consultasAct'])     ? 1 : 0,
                isset($_POST['reportesAct'])      ? 1 : 0,
                isset($_POST['seguridadAct'])     ? 1 : 0,
            ];
            // La contraseña solo se cambia si el admin escribió una nueva.
            // Si se cambia, se guarda con hash (nunca en texto plano).
            $nuevoHash = ($pass !== '') ? Auth::hashPassword($pass) : null;

            try {
                $db->transaccion(function (Database $db) use ($usuario, $nuevoHash, $id, $permisos) {
                    if ($nuevoHash !== null) {
                        $db->ejecutar("UPDATE usuarios SET username=?, pass=? WHERE idusuario=?", [$usuario, $nuevoHash, $id]);
                    } else {
                        $db->ejecutar("UPDATE usuarios SET username=? WHERE idusuario=?", [$usuario, $id]);
                    }
                    $db->ejecutar(
                        "UPDATE permisos SET datosmaestros=?, transacciones=?, consultas=?, reportes=?, seguridad=? WHERE idusuario=?",
                        array_merge($permisos, [$id])
                    );
                });
                Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'editar', 'usuarios', "$usuario (#$id)");
                Auth::flash('success', 'Usuario actualizado correctamente.');
            } catch (\Throwable $e) {
                Auth::flash('error', 'No se pudo actualizar el usuario. Intenta de nuevo.');
            }
        }
    }

    // ---- ELIMINAR / REACTIVAR (baja lógica reversible) ----
    if (isset($_POST['del'])) {
        $id         = (int)($_POST['idUsuarioDel'] ?? 0);
        $filaActual = $db->fila("SELECT estado FROM usuarios WHERE idusuario=?", [$id]);

        if ($id === (int)Auth::get('idusuario')) {
            Auth::flash('error', 'No puedes desactivar tu propia cuenta mientras tienes la sesión abierta.');
        } elseif ($filaActual && (int)$filaActual['estado'] === 1) {
            $db->ejecutar("UPDATE usuarios SET estado=0 WHERE idusuario=?", [$id]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'eliminar', 'usuarios', "#$id");
            Auth::flash('success', 'Usuario desactivado correctamente.');
        } else {
            $db->ejecutar("UPDATE usuarios SET estado=1 WHERE idusuario=?", [$id]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'reactivar', 'usuarios', "#$id");
            Auth::flash('success', 'Usuario reactivado correctamente.');
        }
    }

    header('Location: ' . BASE_URL . '/usuarios.php');
    exit;
}

$pageTitle = 'Usuarios';
require BASE_PATH . '/app/views/layouts/encabezado.php';
Auth::imprimirFlash();
?>

<script src="<?= BASE_URL ?>/public/js/ajax-loader.js"></script>
<script>
$(document).ready(function () { ajaxLoad('<?= BASE_URL ?>/app/ajax/maestros/usuarios.php'); });
$(document).on('keyup', '#buscar', function () {
    ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/maestros/usuarios.php', $(this).val());
});
</script>

<div class="wrapper">
    <div class="container-fluid">
        <div class="page-header clearfix">
            <h2 class="pull-left">Administrar Usuarios</h2>
            <a href="#" class="btn btn-primary pull-right" data-toggle="modal" data-target="#newModal">+ Agregar</a>
        </div>
        <div class="form-group">
            <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
            <br><div id="datos"></div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
