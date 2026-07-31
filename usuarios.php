<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('seguridad');
Auth::guardarPagina();

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
            $permisoTransacciones = isset($_POST['transacciones']) ? 1 : 0;
            $permisos = [
                isset($_POST['maestros']) ? 1 : 0,
                $permisoTransacciones,
                isset($_POST['mantenimientos']) ? 1 : 0,
                isset($_POST['consultas']) ? 1 : 0,
                isset($_POST['reportes']) ? 1 : 0,
                isset($_POST['actas']) || $permisoTransacciones === 1 ? 1 : 0,
                isset($_POST['seguridad']) ? 1 : 0,
            ];
            $passHash = Auth::hashPassword($pass);

            try {
                $db->transaccion(function (Database $db) use ($usuario, $passHash, $idempleado, $permisos) {
                    $idusuario = $db->ejecutar(
                        "INSERT INTO usuarios (username, pass, idempleado, estado) VALUES (?, ?, ?, 1)",
                        [$usuario, $passHash, $idempleado]
                    );
                    $db->ejecutar(
                        "INSERT INTO permisos (idusuario, datosmaestros, transacciones, mantenimientos, consultas, reportes, actas, seguridad)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
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
            $permisoTransacciones = isset($_POST['transaccionesAct']) ? 1 : 0;
            $permisos = [
                isset($_POST['maestrosAct']) ? 1 : 0,
                $permisoTransacciones,
                isset($_POST['mantenimientosAct']) ? 1 : 0,
                isset($_POST['consultasAct']) ? 1 : 0,
                isset($_POST['reportesAct']) ? 1 : 0,
                isset($_POST['actasAct']) || $permisoTransacciones === 1 ? 1 : 0,
                isset($_POST['seguridadAct']) ? 1 : 0,
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
                        "UPDATE permisos SET datosmaestros=?, transacciones=?, mantenimientos=?, consultas=?, reportes=?, actas=?, seguridad=? WHERE idusuario=?",
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
        $filaActual = $db->fila("SELECT us.estado, em.activo AS empleado_activo FROM usuarios us INNER JOIN empleados em ON us.idempleado=em.idempleado WHERE us.idusuario=?", [$id]);

        if (!$filaActual) {
            Auth::flash('error', 'El usuario indicado no existe.');
        } elseif ($id === (int)Auth::get('idusuario')) {
            Auth::flash('error', 'No puedes desactivar tu propia cuenta mientras tienes la sesión abierta.');
        } elseif ((int)$filaActual['estado'] === 1) {
            $db->ejecutar("UPDATE usuarios SET estado=0 WHERE idusuario=?", [$id]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'eliminar', 'usuarios', "#$id");
            Auth::flash('success', 'Usuario desactivado correctamente.');
        } elseif ((int)$filaActual['empleado_activo'] !== 1) {
            Auth::flash('error', 'No se puede reactivar esta cuenta porque el empleado está inactivo. Reactiva primero al empleado.');
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
require_once BASE_PATH . '/app/views/layouts/table_filters.php';
Auth::imprimirFlash();
?>

<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/maestros/usuarios.php');
</script>

<div class="wrapper">
    <div class="container-fluid">
        <div class="page-header clearfix">
            <h2>Usuarios y permisos</h2>
            <div class="page-header-actions">
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newModal">
                    <i class="fa fa-user-plus" aria-hidden="true"></i> Crear usuario
                </a>
            </div>
        </div>
        <?php renderTableFilters([
            'search_label' => 'Buscar usuarios',
            'search_placeholder' => 'Usuario, empleado o identificador',
            'table_id' => 'datosE',
            'filters' => [
                ['name' => 'estado_usuario', 'label' => 'Estado', 'options' => [1 => 'Activo', 0 => 'Inactivo']],
                ['name' => 'permiso', 'label' => 'Con permiso de', 'options' => [
                    'datosmaestros' => 'Maestros', 'transacciones' => 'Transacciones',
                    'mantenimientos' => 'Mantenimientos',
                    'consultas' => 'Consultas', 'reportes' => 'Reportes',
                    'actas' => 'Actas', 'seguridad' => 'Seguridad',
                ]],
            ],
        ]); ?>
        <div id="datos" aria-live="polite"></div>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
