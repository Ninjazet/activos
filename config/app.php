<?php
// ============================================================
// GestActivos - Configuración General
// ============================================================

define('APP_NAME',    'GestActivos');
define('APP_VERSION', '2.0.0');

// Ruta absoluta a la raíz del proyecto (donde está bootstrap.php)
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL',  '/activos');  // Subcarpeta dentro de htdocs

// Rutas de almacenamiento
define('IMG_EMPLEADOS',   BASE_PATH . '/public/img/empleados/');
define('IMG_EQUIPOS',     BASE_PATH . '/public/img/equipos/');

// Zona horaria
date_default_timezone_set('America/Tegucigalpa');
