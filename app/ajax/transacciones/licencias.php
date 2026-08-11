<?php
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('licencias');
LicenciaController::ajax();
