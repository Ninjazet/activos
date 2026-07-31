<?php
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('consultas');
$rows = (new MantenimientoService(Database::getInstance()))->listar(MantenimientoService::leerFiltros());
$tablaId = 'tablaConsultaMantenimientos';
require BASE_PATH . '/app/views/mantenimientos/historial.php';
