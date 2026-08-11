<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (!str_contains(DB_NAME, '_feature_test_')) {
    fwrite(STDERR, 'Esta prueba solo puede ejecutarse sobre una base temporal de funciones.');
    exit(2);
}

$sesiones = __DIR__ . '/.tmp';
if (!is_dir($sesiones) && !mkdir($sesiones, 0775, true) && !is_dir($sesiones)) {
    fwrite(STDERR, 'No se pudo preparar la sesión temporal.');
    exit(3);
}
session_save_path($sesiones);
session_id('licencia-reveal-' . bin2hex(random_bytes(6)));
Auth::iniciar();

$usuario = Database::getInstance()->fila('SELECT idusuario,username FROM usuarios ORDER BY idusuario LIMIT 1');
$licencia = Database::getInstance()->fila(
    'SELECT idlicencia FROM licencias WHERE clave_cifrada IS NOT NULL ORDER BY idlicencia LIMIT 1'
);
if (!$usuario || !$licencia) {
    fwrite(STDERR, 'La base temporal necesita un usuario y una licencia con clave.');
    exit(4);
}

$_SESSION['idusuario'] = (string)$usuario['idusuario'];
$_SESSION['usuario'] = (string)$usuario['username'];
$_SESSION['licencias'] = '1';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$token = Auth::csrfToken();
if (($argv[1] ?? '') === 'csrf_invalido') {
    $token = str_repeat('0', 64);
}
$_POST = [
    'idlicencia' => (string)$licencia['idlicencia'],
    'csrf_token' => $token,
];

require dirname(__DIR__) . '/app/ajax/transacciones/revelar_clave_licencia.php';
