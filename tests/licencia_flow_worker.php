<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (!str_contains(DB_NAME, '_feature_test_')) {
    fwrite(STDERR, 'Esta prueba solo puede ejecutarse sobre una base temporal de funciones.');
    exit(2);
}

set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});

$assert = static function (bool $condicion, string $mensaje): void {
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
};
$debeFallar = static function (callable $accion, string $mensaje) use ($assert): void {
    $fallo = false;
    try {
        $accion();
    } catch (RuntimeException $e) {
        $fallo = true;
    }
    $assert($fallo, $mensaje);
};

try {
    $db = Database::getInstance();
    $softwareService = new SoftwareService($db);
    $licenciaService = new LicenciaService($db);
    $sufijo = strtoupper(bin2hex(random_bytes(4)));
    $nombreSoftware = 'SOFTWARE FLUJO ' . $sufijo;

    $softwareService->procesar([
        'add' => '1',
        'nombre' => $nombreSoftware,
        'fabricante' => 'GESTACTIVOS TEST',
        'version' => '1.0',
        'edicion' => 'EMPRESARIAL',
        'categoria' => 'Pruebas',
    ]);
    $software = $db->fila('SELECT * FROM software WHERE nombre=?', [$nombreSoftware]);
    $assert($software !== null && (int)$software['activo'] === 1, 'No se creó el software.');
    $idsoftware = (int)$software['idsoftware'];

    $debeFallar(static function () use ($softwareService, $nombreSoftware): void {
        $softwareService->procesar([
            'add' => '1', 'nombre' => $nombreSoftware, 'fabricante' => 'GESTACTIVOS TEST',
            'version' => '1.0', 'edicion' => 'EMPRESARIAL',
        ]);
    }, 'Se permitió duplicar el mismo producto, versión y edición.');

    $proveedor = $db->fila('SELECT idproveedor FROM proveedores WHERE activo=1 ORDER BY idproveedor LIMIT 1');
    $idproveedor = $proveedor ? (int)$proveedor['idproveedor'] : 0;
    $clave = 'TEST-' . $sufijo . '-KEY-2026';
    $datosLicencia = [
        'idsoftware' => (string)$idsoftware,
        'idproveedor' => (string)$idproveedor,
        'modalidad' => LicenciaEstado::SUSCRIPCION,
        'metrica' => LicenciaEstado::POR_USUARIO,
        'cantidad_total' => '12',
        'fecha_compra' => '2026-07-31',
        'fecha_inicio' => '2026-08-01',
        'fecha_vencimiento' => '2027-07-31',
        'renovacion_automatica' => '1',
        'reutilizable' => '1',
        'costo_total' => '1250.50',
        'moneda' => 'USD',
        'factura' => 'FAC-' . $sufijo,
        'licenciado_a_nombre' => 'Empresa de Prueba',
        'licenciado_a_correo' => 'licencias@prueba.test',
        'clave_producto' => $clave,
    ];
    $licenciaService->procesar(['add' => '1'] + $datosLicencia);
    $licencia = $db->fila('SELECT * FROM licencias WHERE idsoftware=?', [$idsoftware]);
    $assert($licencia !== null, 'No se creó la licencia.');
    $idlicencia = (int)$licencia['idlicencia'];
    $assert(
        $db->contar('SELECT COUNT(*) FROM licencia_cupos WHERE idlicencia=? AND activo=1', [$idlicencia]) === 12,
        'No se generaron los 12 cupos iniciales.'
    );
    $assert(str_starts_with((string)$licencia['codigo_licencia'], 'LIC-'), 'No se generó el código de licencia.');
    $assert((string)$licencia['clave_cifrada'] !== $clave && !str_contains((string)$licencia['clave_cifrada'], $clave), 'La clave quedó expuesta.');
    $assert(SecretoLicencia::descifrar((string)$licencia['clave_cifrada']) === $clave, 'La clave cifrada no pudo recuperarse.');
    $claveRevelada = $licenciaService->revelarClave($idlicencia);
    $assert($claveRevelada['clave'] === $clave, 'El servicio no reveló la clave correcta.');

    $debeFallar(static function () use ($licenciaService, $datosLicencia): void {
        $licenciaService->procesar(['add' => '1'] + $datosLicencia);
    }, 'Se permitió registrar dos veces la misma clave.');

    $datosEditados = $datosLicencia;
    unset($datosEditados['clave_producto']);
    $datosEditados['idlicencia'] = (string)$idlicencia;
    $datosEditados['costo_total'] = '1300.75';
    $datosEditados['observaciones'] = 'Actualizada por la prueba de flujo.';
    $licenciaService->procesar(['edit' => '1'] + $datosEditados);
    $licenciaEditada = $db->fila('SELECT * FROM licencias WHERE idlicencia=?', [$idlicencia]);
    $assert((float)$licenciaEditada['costo_total'] === 1300.75, 'No se actualizó la licencia.');
    $assert(SecretoLicencia::descifrar((string)$licenciaEditada['clave_cifrada']) === $clave, 'La edición eliminó la clave existente.');

    $asignacionService = new LicenciaAsignacionService($db);
    $empleado = $db->fila('SELECT idempleado FROM empleados WHERE activo=1 ORDER BY idempleado LIMIT 1');
    $usuario = $db->fila('SELECT idusuario FROM usuarios WHERE estado=1 ORDER BY idusuario LIMIT 1');
    $assert($empleado !== null && $usuario !== null, 'Falta un empleado o usuario activo para probar la asignacion.');
    $datosAsignacion = [
        'asignar_licencia' => '1',
        'idlicencia' => (string)$idlicencia,
        'idlicencia_contexto' => (string)$idlicencia,
        'tipo_destino' => 'empleado',
        'id_destino' => (string)$empleado['idempleado'],
        'correo_cuenta' => 'asignacion@prueba.test',
    ];
    $asignacionService->procesar($datosAsignacion, (int)$usuario['idusuario']);
    $asignacion = $db->fila(
        'SELECT * FROM licencia_asignaciones WHERE idlicencia=? AND activa=1',
        [$idlicencia]
    );
    $assert($asignacion !== null && $asignacion['idcupo'] !== null, 'La asignacion no tomo un cupo numerado.');
    $debeFallar(
        static fn() => $asignacionService->procesar($datosAsignacion, (int)$usuario['idusuario']),
        'Se permitio duplicar una asignacion activa para el mismo empleado.'
    );
    $asignacionService->procesar([
        'devolver_licencia' => '1',
        'idasignacion_licencia' => (string)$asignacion['idasignacion_licencia'],
        'idlicencia_contexto' => (string)$idlicencia,
        'motivo_devolucion' => 'Fin de la prueba automatizada',
    ], (int)$usuario['idusuario']);
    $asignacionCerrada = $db->fila(
        'SELECT * FROM licencia_asignaciones WHERE idasignacion_licencia=?',
        [(int)$asignacion['idasignacion_licencia']]
    );
    $assert((int)$asignacionCerrada['activa'] === 0 && $asignacionCerrada['fecha_devolucion'] !== null, 'La devolucion no cerro la asignacion.');
    $assert(
        $db->contar('SELECT COUNT(*) FROM licencia_cupos WHERE idcupo=? AND activo=1', [(int)$asignacion['idcupo']]) === 1,
        'El cupo reutilizable no regreso a disponibilidad.'
    );

    $datosAumentados = $datosEditados;
    $datosAumentados['cantidad_total'] = '14';
    $licenciaService->procesar(['edit' => '1'] + $datosAumentados);
    $assert($db->contar('SELECT COUNT(*) FROM licencia_cupos WHERE idlicencia=? AND activo=1', [$idlicencia]) === 14, 'No se agregaron cupos al aumentar la cantidad.');
    $licenciaService->procesar(['edit' => '1'] + $datosEditados);
    $assert($db->contar('SELECT COUNT(*) FROM licencia_cupos WHERE idlicencia=? AND activo=1', [$idlicencia]) === 12, 'No se retiraron cupos al reducir la cantidad.');

    $debeFallar(static function () use ($softwareService, $idsoftware): void {
        $softwareService->procesar(['del' => '1', 'idsoftware' => (string)$idsoftware]);
    }, 'Se permitió desactivar software con una licencia activa.');

    $licenciaService->procesar(['del' => '1', 'idlicencia' => (string)$idlicencia]);
    $softwareService->procesar(['del' => '1', 'idsoftware' => (string)$idsoftware]);
    $debeFallar(static function () use ($licenciaService, $idlicencia): void {
        $licenciaService->procesar(['del' => '1', 'idlicencia' => (string)$idlicencia]);
    }, 'Se permitió reactivar una licencia cuyo software está inactivo.');
    $softwareService->procesar(['del' => '1', 'idsoftware' => (string)$idsoftware]);
    $licenciaService->procesar(['del' => '1', 'idlicencia' => (string)$idlicencia]);

    $listado = $licenciaService->listar(['busqueda' => $sufijo]);
    $assert(count($listado) === 1, 'La búsqueda no encontró la licencia creada.');
    $sesiones = __DIR__ . '/.tmp';
    if (!is_dir($sesiones) && !mkdir($sesiones, 0775, true) && !is_dir($sesiones)) {
        throw new RuntimeException('No se pudo preparar la sesión temporal.');
    }
    session_save_path($sesiones);
    session_id('licencia-flujo-' . strtolower($sufijo));
    Auth::iniciar();
    $rows = $listado;
    $software = $softwareService->opciones(true);
    $proveedores = $licenciaService->proveedores();
    ob_start();
    require dirname(__DIR__) . '/app/views/licencias/listado.php';
    $htmlListado = (string)ob_get_clean();
    $assert(str_contains($htmlListado, (string)$licencia['codigo_licencia']), 'El listado no mostró la licencia.');
    $assert(!str_contains($htmlListado, $clave), 'El listado expuso la clave completa.');

    $rows = $softwareService->listar($sufijo);
    $categorias = $softwareService->categorias();
    ob_start();
    require dirname(__DIR__) . '/app/views/licencias/software_listado.php';
    $htmlSoftware = (string)ob_get_clean();
    $assert(str_contains($htmlSoftware, $nombreSoftware), 'El listado no mostró el software.');
    $licenciaDetalle = $licenciaService->obtener($idlicencia);
    $assert($licenciaDetalle !== null, 'No se pudo consultar la ficha de la licencia.');
    $licencia = $licenciaDetalle;
    $asignaciones = $asignacionService->listarAsignaciones($idlicencia);
    $cupos = $asignacionService->listarCupos($idlicencia);
    $destinosPermitidos = LicenciaEstado::destinosPermitidos((string)$licencia['metrica']);
    $empleadosDisponibles = $asignacionService->empleadosDisponibles($idlicencia);
    $equiposDisponibles = [];
    ob_start();
    require dirname(__DIR__) . '/app/views/licencias/detalle.php';
    $htmlDetalle = (string)ob_get_clean();
    $assert(str_contains($htmlDetalle, (string)$licenciaDetalle['codigo_licencia']), 'La ficha no mostró el código.');
    $assert(!str_contains($htmlDetalle, $clave), 'La ficha expuso la clave completa.');
    $assert(str_contains($htmlDetalle, (string)$licenciaDetalle['clave_mascara']), 'La ficha no mostró la clave enmascarada.');
    if ($idproveedor > 0) {
        $detalleProveedor = (new ProveedorService($db))->obtener($idproveedor);
        $assert($detalleProveedor !== null && (int)$detalleProveedor['licencias'] >= 1, 'La ficha del proveedor no contó la licencia.');
    }

    echo json_encode([
        'software' => $idsoftware,
        'licencia' => $idlicencia,
        'codigo' => $licenciaDetalle['codigo_licencia'],
        'clave_enmascarada' => $licenciaDetalle['clave_mascara'],
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage());
    exit(1);
}
