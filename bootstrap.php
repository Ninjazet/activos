<?php
// ============================================================
// GestActivos - Bootstrap
// Incluir al inicio de CADA archivo PHP del proyecto
// ============================================================

// BASE_PATH y BASE_URL se definen en config/app.php
require_once __DIR__ . '/config/app.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/controllers/Database.php';
require_once BASE_PATH . '/app/controllers/Auth.php';
require_once BASE_PATH . '/app/controllers/Upload.php';
require_once BASE_PATH . '/app/controllers/TableFilter.php';
require_once BASE_PATH . '/app/domain/EquipoEstado.php';
require_once BASE_PATH . '/app/domain/EquipoFormulario.php';
require_once BASE_PATH . '/app/domain/MantenimientoEstado.php';
require_once BASE_PATH . '/app/domain/LicenciaEstado.php';
require_once BASE_PATH . '/app/support/Imagen.php';
require_once BASE_PATH . '/app/support/Validacion.php';
require_once BASE_PATH . '/app/support/SecretoLicencia.php';
require_once BASE_PATH . '/app/services/AsignacionService.php';
require_once BASE_PATH . '/app/services/CatalogoService.php';
require_once BASE_PATH . '/app/controllers/CatalogoController.php';
require_once BASE_PATH . '/app/services/ProveedorService.php';
require_once BASE_PATH . '/app/controllers/ProveedorController.php';
require_once BASE_PATH . '/app/services/MantenimientoService.php';
require_once BASE_PATH . '/app/controllers/MantenimientoController.php';
require_once BASE_PATH . '/app/services/SoftwareService.php';
require_once BASE_PATH . '/app/services/LicenciaAsignacionService.php';
require_once BASE_PATH . '/app/services/LicenciaService.php';
require_once BASE_PATH . '/app/controllers/LicenciaController.php';
