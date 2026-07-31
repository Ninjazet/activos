<?php
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('mantenimientos');
MantenimientoController::ajax();
