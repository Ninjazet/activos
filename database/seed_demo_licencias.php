<?php

declare(strict_types=1);

/**
 * Datos ficticios para probar el módulo de Licencias.
 * Es idempotente: usa la factura DEMO-LIC-FAC-* para evitar duplicados.
 * Las claves incluidas son demostrativas y no activan productos reales.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

if (!SecretoLicencia::disponible()) {
    fwrite(STDERR, 'Configura APP_ENCRYPTION_KEY antes de ejecutar este seed.');
    exit(2);
}

try {
    $db = Database::getInstance();
    $softwareService = new SoftwareService($db);
    $licenciaService = new LicenciaService($db);
    $proveedores = $db->consulta('SELECT idproveedor FROM proveedores WHERE activo=1 ORDER BY idproveedor LIMIT 6');
    $proveedorIds = array_map(static fn(array $fila): int => (int)$fila['idproveedor'], $proveedores);

    $ejemplos = [
        [
            'software' => ['DEMO - Microsoft 365', 'Microsoft', '2026', 'Business Standard', 'Ofimática', 'Suite de productividad demostrativa.'],
            'licencia' => [LicenciaEstado::SUSCRIPCION, LicenciaEstado::POR_USUARIO, '25', '2026-07-01', '2026-07-01', '2027-06-30', 'USD', '1250.00', 'DEMO-M365-2026-USER-0001'],
        ],
        [
            'software' => ['DEMO - Adobe Acrobat', 'Adobe', '2026', 'Pro', 'PDF y documentos', 'Edición y firma de documentos PDF de prueba.'],
            'licencia' => [LicenciaEstado::SUSCRIPCION, LicenciaEstado::POR_USUARIO, '10', '2026-01-01', '2026-01-01', '2026-08-15', 'USD', '780.00', 'DEMO-ADOBE-2026-PRO-0002'],
        ],
        [
            'software' => ['DEMO - ESET Protect', 'ESET', 'Cloud', 'Advanced', 'Seguridad', 'Protección de dispositivos para pruebas de vencimiento.'],
            'licencia' => [LicenciaEstado::SUSCRIPCION, LicenciaEstado::POR_DISPOSITIVO, '40', '2025-07-01', '2025-07-01', '2026-06-30', 'USD', '980.00', 'DEMO-ESET-2026-ADV-0003'],
        ],
        [
            'software' => ['DEMO - Windows 11', 'Microsoft', '11', 'Pro', 'Sistema operativo', 'Licencia perpetua demostrativa por dispositivo.'],
            'licencia' => [LicenciaEstado::PERPETUA, LicenciaEstado::POR_DISPOSITIVO, '15', '2026-02-10', '', '', 'USD', '2100.00', 'DEMO-WIN11-PRO-OEM-0004'],
        ],
        [
            'software' => ['DEMO - AnyDesk', 'AnyDesk Software', 'Cloud', 'Advanced', 'Acceso remoto', 'Acceso remoto concurrente para pruebas.'],
            'licencia' => [LicenciaEstado::SUSCRIPCION, LicenciaEstado::CONCURRENTE, '5', '2026-07-15', '2026-07-15', '2027-07-14', 'USD', '650.00', 'DEMO-ANYDESK-ADV-0005'],
        ],
        [
            'software' => ['DEMO - Notion', 'Notion Labs', 'Cloud', 'Enterprise', 'Colaboración', 'Licenciamiento corporativo demostrativo sin límite de cupos.'],
            'licencia' => [LicenciaEstado::SUSCRIPCION, LicenciaEstado::CORPORATIVA, '', '2026-07-31', '2026-07-31', '2027-07-30', 'USD', '1800.00', 'DEMO-NOTION-ENT-0006'],
        ],
    ];

    $creadosSoftware = 0;
    $creadasLicencias = 0;
    foreach ($ejemplos as $indice => $ejemplo) {
        [$nombre, $fabricante, $version, $edicion, $categoria, $descripcion] = $ejemplo['software'];
        $producto = $db->fila(
            'SELECT idsoftware FROM software WHERE nombre=? AND fabricante=? AND version=? AND edicion=?',
            [$nombre, $fabricante, $version, $edicion]
        );
        if (!$producto) {
            $softwareService->procesar([
                'add' => '1', 'nombre' => $nombre, 'fabricante' => $fabricante,
                'version' => $version, 'edicion' => $edicion,
                'categoria' => $categoria, 'descripcion' => $descripcion,
            ]);
            $producto = $db->fila(
                'SELECT idsoftware FROM software WHERE nombre=? AND fabricante=? AND version=? AND edicion=?',
                [$nombre, $fabricante, $version, $edicion]
            );
            $creadosSoftware++;
        }

        $numero = str_pad((string)($indice + 1), 3, '0', STR_PAD_LEFT);
        $factura = 'DEMO-LIC-FAC-' . $numero;
        if ($db->fila('SELECT idlicencia FROM licencias WHERE factura=?', [$factura])) {
            continue;
        }
        [$modalidad, $metrica, $cantidad, $compra, $inicio, $vencimiento, $moneda, $costo, $clave] = $ejemplo['licencia'];
        $idproveedor = $proveedorIds ? $proveedorIds[$indice % count($proveedorIds)] : 0;
        $licenciaService->procesar([
            'add' => '1',
            'idsoftware' => (string)$producto['idsoftware'],
            'idproveedor' => (string)$idproveedor,
            'modalidad' => $modalidad,
            'metrica' => $metrica,
            'cantidad_total' => $cantidad,
            'fecha_compra' => $compra,
            'fecha_inicio' => $inicio,
            'fecha_vencimiento' => $vencimiento,
            'renovacion_automatica' => $modalidad === LicenciaEstado::SUSCRIPCION ? '1' : '0',
            'reutilizable' => '1',
            'costo_total' => $costo,
            'moneda' => $moneda,
            'factura' => $factura,
            'orden_compra' => 'DEMO-LIC-OC-' . $numero,
            'numero_contrato' => 'DEMO-LIC-CONTRATO-' . $numero,
            'licenciado_a_nombre' => 'GestActivos - Datos demostrativos',
            'licenciado_a_correo' => 'licencias' . $numero . '@demo.test',
            'clave_producto' => $clave,
            'observaciones' => 'Registro ficticio generado por database/seed_demo_licencias.php.',
        ]);
        $creadasLicencias++;
    }

    echo json_encode([
        'software_creado' => $creadosSoftware,
        'licencias_creadas' => $creadasLicencias,
        'software_demo_total' => $db->contar("SELECT COUNT(*) FROM software WHERE nombre LIKE 'DEMO - %'"),
        'licencias_demo_total' => $db->contar("SELECT COUNT(*) FROM licencias WHERE factura LIKE 'DEMO-LIC-FAC-%'"),
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage());
    exit(1);
}
