<?php
// ============================================================
// GestActivos - Configuración de Base de Datos
// Edita solo este archivo para cambiar la conexión
// ============================================================

require_once __DIR__ . '/env.php';

define('DB_HOST',    (string)gestEnv('DB_HOST', 'localhost'));
define('DB_PORT',    (int)gestEnv('DB_PORT', 3306));
define('DB_USER',    (string)gestEnv('DB_USER', 'root'));
define('DB_PASS',    (string)gestEnv('DB_PASS', ''));
define('DB_NAME',    (string)gestEnv('DB_NAME', 'gestactivos'));
define('DB_CHARSET', (string)gestEnv('DB_CHARSET', 'utf8mb4'));
