<?php
require_once __DIR__ . '/bootstrap.php';
Auth::iniciar();

// Si ya tiene sesión activa, ir directo al inicio
if (!empty($_SESSION['usuario'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verificar CSRF
    $recibido = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $recibido)) {
        $error = 'Sesión expirada. Recarga la página e intenta de nuevo.';
    } else {
        $db       = Database::getInstance();
        $usuario  = trim($_POST['usuario']    ?? '');
        $password = trim($_POST['contrasena'] ?? '');

        // 2. Verificar bloqueo por fuerza bruta (IP + usuario)
        if (Auth::bloqueadoPorIntentos($usuario)) {
            $minutos = Auth::minutosBloqueo();
            $error = "Demasiados intentos fallidos. Espera {$minutos} minutos antes de intentar de nuevo.";
        } else {
            // 3. Buscar el usuario por nombre (SIN comparar la contraseña en SQL)
            $reg = $db->fila(
                "SELECT us.idusuario, us.username, us.pass, us.idempleado,
                        em.nombre, em.apellidos, em.imagen,
                        pe.datosmaestros, pe.transacciones, pe.mantenimientos, pe.licencias,
                        pe.consultas, pe.reportes, pe.actas, pe.seguridad
                 FROM usuarios us
                 INNER JOIN empleados em ON us.idempleado = em.idempleado
                 LEFT  JOIN permisos pe ON us.idusuario   = pe.idusuario
                 WHERE us.username = ? AND us.estado = 1",
                [$usuario]
            );

            // 4. Verificar la contraseña contra el hash almacenado.
            $passwordOk = $reg && Auth::verificarPassword($password, $reg['pass']);

            if ($passwordOk) {
                // Renueva automáticamente el hash si PHP recomienda un algoritmo más reciente.
                if (password_needs_rehash($reg['pass'], PASSWORD_DEFAULT)) {
                    $db->ejecutar(
                        "UPDATE usuarios SET pass=? WHERE idusuario=?",
                        [Auth::hashPassword($password), $reg['idusuario']]
                    );
                }

                // 5. Login correcto: regenerar ID de sesión para prevenir session fixation
                session_regenerate_id(true);

                $nomApe = trim(($reg['nombre'] ?? '') . ' ' . ($reg['apellidos'] ?? ''));
                $fotoUrl = Imagen::empleado($reg['imagen'] ?? null);

                // Cargar permisos y datos de usuario en sesión
                $_SESSION['idusuario']     = $reg['idusuario'];
                $_SESSION['usuario']       = $reg['username'];
                $_SESSION['nombre']        = $nomApe;
                $_SESSION['foto']          = $fotoUrl;
                $_SESSION['estado']        = '1'; // para el toast de bienvenida
                $_SESSION['maestros']      = $reg['datosmaestros'];
                $_SESSION['transacciones'] = $reg['transacciones'];
                $_SESSION['mantenimientos'] = $reg['mantenimientos'];
                $_SESSION['licencias']     = $reg['licencias'];
                $_SESSION['consultas']     = $reg['consultas'];
                $_SESSION['reportes']      = $reg['reportes'];
                $_SESSION['actas']         = $reg['actas'];
                $_SESSION['seguridad']     = $reg['seguridad'];

                Auth::registrarBitacora((int)$reg['idusuario'], $reg['username'], 'login_exitoso');
                header('Location: ' . BASE_URL . '/index.php');
                exit();
            } else {
                // 6. Login fallido: registrar en bitácora (para el contador de fuerza bruta)
                Auth::registrarBitacora(null, $usuario, 'login_fallido', null, 'Intento fallido desde ' . Auth::ipCliente());
                $error = 'Usuario o contraseña incorrectos.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> · Iniciar Sesión</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Iconos de Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Tu CSS personalizado con estilo Mazer -->
    <link href="<?= BASE_URL ?>/public/css/login.css?v=<?= urlencode((string)(@filemtime(BASE_PATH . '/public/css/login.css') ?: APP_VERSION)) ?>" rel="stylesheet">
    <!-- Toastr para notificaciones -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</head>
<body>
    <div id="auth">
        <canvas class="login-pixel-rain" id="loginPixelRain" aria-hidden="true"></canvas>
        <div class="row h-100 g-0 login-content">
            <!-- Columna izquierda (ilustración y texto, visible en pantallas grandes) -->
            <div class="col-lg-7 d-none d-lg-flex align-items-center justify-content-center p-5">
                <div class="login-brand text-white text-center">
                    <!-- Ícono en lugar de imagen (no dependemos de un archivo externo) -->
                    <i class="bi bi-shield-lock mb-4" style="font-size: 8rem; opacity: 0.9;"></i>
                    <h1 class="display-5 fw-bold"><?= htmlspecialchars(APP_NAME) ?></h1>
                    <p class="lead">Sistema de gestión interno · Inicia sesión para continuar</p>
                </div>
            </div>

            <!-- Columna derecha (formulario de login) -->
            <div class="col-lg-5 d-flex align-items-center justify-content-center">
                <div class="login-form-panel p-4 p-md-5 w-100">
                    <!-- Nombre de la app visible solo en móvil -->
                    <div class="d-lg-none text-center mb-4">
                        <h2 class="text-brand"><?= htmlspecialchars(APP_NAME) ?></h2>
                    </div>
                    <h4 class="mb-4 text-dark fw-bold">Iniciar Sesión</h4>

                    <form action="" method="post">
                        <?= Auth::csrfField() ?>

                        <!-- Campo Usuario -->
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Usuario</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="usuario" name="usuario"
                                       value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                                       autocomplete="username" required>
                            </div>
                        </div>

                        <!-- Campo Contraseña -->
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="contrasena" name="contrasena"
                                       autocomplete="current-password" required>
                            </div>
                        </div>

                        <!-- Botón de envío -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar sesión
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php if ($error): ?>
<script>
    toastr.options = { positionClass: 'toast-top-center' };
    toastr.error('<?= addslashes(htmlspecialchars($error)) ?>', 'Error');
</script>
<?php endif; ?>
<script src="<?= BASE_URL ?>/public/js/login-background.js?v=<?= urlencode((string)(@filemtime(BASE_PATH . '/public/js/login-background.js') ?: APP_VERSION)) ?>"></script>
</body>
</html>
