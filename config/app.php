<?php
// ============================================================
// GestActivos - Configuración General
// ============================================================

require_once __DIR__ . '/env.php';

define('APP_NAME',    (string)gestEnv('APP_NAME', 'GestActivos'));
define('APP_VERSION', (string)gestEnv('APP_VERSION', '2.0.0'));
define('APP_ENCRYPTION_KEY', (string)gestEnv('APP_ENCRYPTION_KEY', ''));

// Ruta absoluta a la raíz del proyecto (donde está bootstrap.php)
define('BASE_PATH', dirname(__DIR__));

$baseUrl = trim((string)gestEnv('APP_BASE_URL', '/activos'));
if ($baseUrl === '' || $baseUrl === '/') {
    $baseUrl = '';
} else {
    $baseUrl = '/' . trim($baseUrl, '/');
}
define('BASE_URL', $baseUrl);

// Rutas de almacenamiento
$storagePath = rtrim(
    (string)gestEnv('APP_STORAGE_PATH', BASE_PATH . '/public/img'),
    '/' . chr(92)
);
define('IMG_EMPLEADOS', $storagePath . DIRECTORY_SEPARATOR . 'empleados' . DIRECTORY_SEPARATOR);
define('IMG_EQUIPOS',   $storagePath . DIRECTORY_SEPARATOR . 'equipos' . DIRECTORY_SEPARATOR);
define('IMG_FIRMAS',    $storagePath . DIRECTORY_SEPARATOR . 'firmas' . DIRECTORY_SEPARATOR);

// Zona horaria
$zonaHoraria = (string)gestEnv('APP_TIMEZONE', 'America/Tegucigalpa');
if (!@date_default_timezone_set($zonaHoraria)) {
    date_default_timezone_set('America/Tegucigalpa');
}
