<?php
// ============================================================
// GestActivos - Encabezado general de páginas internas
// Uso: require BASE_PATH . '/app/views/layouts/encabezado.php';
// ============================================================

require_once BASE_PATH . '/bootstrap.php';
Auth::requerir();

$db = Database::getInstance();

require BASE_PATH . '/app/views/layouts/head.php';
require BASE_PATH . '/app/views/layouts/sidebar.php';

// Toast de bienvenida al iniciar sesión (solo la primera página después del login)
if (!empty($_SESSION['estado']) && $_SESSION['estado'] === '1') {
    $nombre = htmlspecialchars(Auth::get('nombre'));
    echo "<script>toastr.success('Bienvenido {$nombre}', 'GestActivos');</script>";
    $_SESSION['estado'] = '0';
}
