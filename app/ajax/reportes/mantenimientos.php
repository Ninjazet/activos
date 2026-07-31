<?php
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('reportes');
$rows = (new MantenimientoService(Database::getInstance()))->listar(MantenimientoService::leerFiltros());
$tablaId = 'tablaReporteMantenimientos';
require BASE_PATH . '/app/views/mantenimientos/historial.php';
