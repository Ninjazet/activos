<?php
// ============================================================
// GestActivos - Manejo de sesión, autenticación, CSRF,
// mensajes flash, contraseñas y bitácora de auditoría
// ============================================================

class Auth {

    // Inicia sesión si no está activa (con cookies de sesión más seguras)
    public static function iniciar(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    // Verifica que haya sesión activa; redirige si no
    public static function requerir(): void {
        self::iniciar();
        if (empty($_SESSION['usuario'])) {
            header('Location: ' . BASE_URL . '/login.php');
            exit();
        }
    }

    // Verifica permiso de módulo; redirige si no tiene acceso
    public static function requerirPermiso(string $modulo): void {
        self::requerir();
        if (empty($_SESSION[$modulo]) || $_SESSION[$modulo] != '1') {
            $pagina = $_SESSION['pagina'] ?? (BASE_URL . '/index.php');
            header('Location: ' . $pagina);
            exit();
        }
    }

    // Permite consultar actas sin conceder acceso a las transacciones.
    // Transacciones conserva acceso implícito por compatibilidad operativa.
    public static function requerirPermisoActas(): void {
        self::requerir();
        $puedeVer = (string)($_SESSION['actas'] ?? '0') === '1'
            || (string)($_SESSION['transacciones'] ?? '0') === '1';
        if (!$puedeVer) {
            $pagina = $_SESSION['pagina'] ?? (BASE_URL . '/index.php');
            header('Location: ' . $pagina);
            exit();
        }
    }

    // Guarda la página actual en sesión (para redirecciones)
    public static function guardarPagina(string $archivo): void {
        $_SESSION['pagina'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/index.php');
    }

    // Destruye la sesión
    public static function cerrar(): void {
        self::iniciar();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }

    // Devuelve valor de sesión con fallback
    public static function get(string $key, string $default = ''): string {
        return $_SESSION[$key] ?? $default;
    }

    // ------------------------------------------------------------------
    // CSRF — un token por sesión, se valida en cada POST que cambie datos
    // ------------------------------------------------------------------

    public static function csrfToken(): string {
        self::iniciar();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // <input> oculto listo para imprimir dentro de cualquier <form>
    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES) . '">';
    }

    // Verifica el token recibido por POST. Si falla, detiene la ejecución.
    public static function verificarCsrf(): void {
        self::iniciar();
        $recibido = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !is_string($recibido) || !hash_equals($_SESSION['csrf_token'], $recibido)) {
            self::flash('error', 'Tu sesión o el formulario expiraron. Intenta de nuevo.');
            $destino = $_SESSION['pagina'] ?? (BASE_URL . '/index.php');
            header('Location: ' . $destino);
            exit();
        }
    }

    // ------------------------------------------------------------------
    // Mensajes flash — para mostrar un aviso después de un redirect
    // (patrón Post-Redirect-Get)
    // ------------------------------------------------------------------

    public static function flash(string $tipo, string $mensaje): void {
        self::iniciar();
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    // Devuelve el mensaje pendiente (si hay) y lo borra de la sesión
    public static function obtenerFlash(): ?array {
        self::iniciar();
        if (empty($_SESSION['flash'])) {
            return null;
        }
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }

    // Imprime el <script> de toastr para el mensaje flash pendiente, si hay uno
    public static function imprimirFlash(): void {
        $f = self::obtenerFlash();
        if (!$f) {
            return;
        }
        $tipo = $f['tipo'] === 'error' ? 'error' : 'success';
        $msg  = htmlspecialchars($f['mensaje'], ENT_QUOTES);
        echo "<script>toastr.{$tipo}('{$msg}', 'GestActivos');</script>";
    }

    // ------------------------------------------------------------------
    // Contraseñas — hash seguro y renovación automática
    // ------------------------------------------------------------------

    public static function hashPassword(string $plano): string {
        return password_hash($plano, PASSWORD_DEFAULT);
    }

    // Solo acepta contraseñas almacenadas con password_hash().
    public static function verificarPassword(string $plano, string $guardado): bool {
        return password_verify($plano, $guardado);
    }

    // ------------------------------------------------------------------
    // Bitácora de auditoría — quién hizo qué, cuándo y desde dónde
    // ------------------------------------------------------------------

    public static function registrarBitacora(?int $idusuario, ?string $usuarioTexto, string $accion, ?string $modulo = null, ?string $detalle = null): void {
        try {
            $db = Database::getInstance();
            $db->ejecutar(
                "INSERT INTO bitacora (idusuario, usuario_texto, accion, modulo, detalle, ip) VALUES (?, ?, ?, ?, ?, ?)",
                [$idusuario, $usuarioTexto, $accion, $modulo, $detalle, self::ipCliente()]
            );
        } catch (\Throwable $e) {
            // La bitácora nunca debe tumbar la operación principal.
            // Si la tabla aún no existe (no se corrió la migración), se ignora.
        }
    }

    public static function ipCliente(): string {
        return $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    }

    // ------------------------------------------------------------------
    // Protección contra fuerza bruta en el login
    // ------------------------------------------------------------------

    private const MAX_INTENTOS    = 5;
    private const VENTANA_MINUTOS = 15;

    // true si el usuario+IP ya superó el máximo de intentos fallidos recientes
    public static function bloqueadoPorIntentos(string $usuario): bool {
        try {
            $db   = Database::getInstance();
            $fila = $db->fila(
                "SELECT COUNT(*) AS total FROM bitacora
                 WHERE accion = 'login_fallido'
                   AND usuario_texto = ?
                   AND ip = ?
                   AND fecha >= (NOW() - INTERVAL " . self::VENTANA_MINUTOS . " MINUTE)",
                [$usuario, self::ipCliente()]
            );
            return (int)($fila['total'] ?? 0) >= self::MAX_INTENTOS;
        } catch (\Throwable $e) {
            return false; // si la bitácora no existe todavía, no bloquear
        }
    }

    public static function minutosBloqueo(): int {
        return self::VENTANA_MINUTOS;
    }
}
