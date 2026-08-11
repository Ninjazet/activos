<?php

$raiz = dirname(__DIR__);
$tipo = $argv[1] ?? '';
$archivo = $argv[2] ?? '';

$sesiones = $raiz . '/tests/.tmp';
if (!is_dir($sesiones) && !mkdir($sesiones, 0775, true) && !is_dir($sesiones)) {
    fwrite(STDERR, 'No se pudo preparar la sesión temporal.');
    exit(2);
}

session_save_path($sesiones);
session_id('media-' . substr(hash('sha256', microtime(true) . $archivo), 0, 20));
session_start();
$_SESSION = [
    'idusuario' => '11',
    'usuario' => 'PRUEBAS',
    'nombre' => 'USUARIO PRUEBAS',
];
$_GET = ['tipo' => $tipo, 'archivo' => $archivo];
$_SERVER['REQUEST_METHOD'] = 'GET';

include $raiz . '/media.php';
