<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (!str_contains(DB_NAME, '_feature_test_')) {
    fwrite(STDERR, 'Esta prueba solo puede ejecutarse sobre una base temporal de funciones.');
    exit(2);
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

try {
    $db = Database::getInstance();
    $user = $db->fila('SELECT idusuario FROM usuarios ORDER BY idusuario LIMIT 1');
    $employee = $db->fila('SELECT idempleado FROM empleados WHERE activo=1 ORDER BY idempleado LIMIT 1');
    $assert($user !== null && $employee !== null, 'La base temporal necesita un usuario y un empleado activos.');
    $userId = (int)$user['idusuario'];

    $providerName = 'PROVEEDOR PRUEBA FLUJO ' . bin2hex(random_bytes(4));
    $providers = new ProveedorService($db);
    $providers->procesar([
        'add' => '1',
        'nombre' => $providerName,
        'rtn' => 'TEST-' . bin2hex(random_bytes(4)),
        'correo' => 'flujo@prueba.test',
    ]);
    $provider = $db->fila('SELECT idproveedor,activo FROM proveedores WHERE nombre=?', [$providerName]);
    $assert($provider !== null && (int)$provider['activo'] === 1, 'No se creó el proveedor.');
    $providerId = (int)$provider['idproveedor'];

    $providers->procesar([
        'edit' => '1',
        'idproveedor' => (string)$providerId,
        'nombre' => $providerName,
        'contacto' => 'Contacto actualizado',
        'correo' => 'actualizado@prueba.test',
    ]);
    $providers->procesar(['del' => '1', 'idproveedor' => (string)$providerId]);
    $assert($db->contar('SELECT COUNT(*) FROM proveedores WHERE idproveedor=? AND activo=0', [$providerId]) === 1, 'No se inactivó el proveedor.');
    $providers->procesar(['del' => '1', 'idproveedor' => (string)$providerId]);

    $equipment = $db->fila(
        "SELECT eq.idequipo FROM equipo eq
         WHERE eq.activo=1 AND eq.estado_equipo=?
           AND NOT EXISTS (SELECT 1 FROM asignacion a WHERE a.idequipo=eq.idequipo AND a.activa=1)
           AND NOT EXISTS (SELECT 1 FROM mantenimientos m WHERE m.idequipo=eq.idequipo AND m.estado IN ('Abierto','En proceso'))
         ORDER BY eq.idequipo LIMIT 1",
        [EquipoEstado::DISPONIBLE]
    );
    $assert($equipment !== null, 'No hay un equipo disponible para probar mantenimiento.');
    $equipmentId = (int)$equipment['idequipo'];
    $db->ejecutar('UPDATE equipo SET idproveedor=? WHERE idequipo=?', [$providerId, $equipmentId]);
    $providerDetail = $providers->obtener($providerId);
    $assert($providerDetail !== null && (int)$providerDetail['equipos'] >= 1, 'La ficha no contabilizó el equipo relacionado.');

    $maintenance = new MantenimientoService($db);
    $maintenance->procesar([
        'add' => '1',
        'idequipo' => (string)$equipmentId,
        'idproveedor' => (string)$providerId,
        'tipo' => MantenimientoEstado::PREVENTIVO,
        'descripcion_problema' => 'Revisión preventiva automatizada.',
    ], $userId);
    $record = $db->fila(
        "SELECT idmantenimiento,estado FROM mantenimientos
         WHERE idequipo=? AND estado IN ('Abierto','En proceso')",
        [$equipmentId]
    );
    $assert($record !== null, 'No se abrió el mantenimiento manual.');
    $maintenanceId = (int)$record['idmantenimiento'];
    $assert($db->contar('SELECT COUNT(*) FROM equipo WHERE idequipo=? AND estado_equipo=?', [$equipmentId, EquipoEstado::MANTENIMIENTO]) === 1, 'El equipo no cambió a mantenimiento.');

    $maintenance->procesar([
        'save' => '1',
        'idmantenimiento' => (string)$maintenanceId,
        'idproveedor' => (string)$providerId,
        'tipo' => MantenimientoEstado::PREVENTIVO,
        'estado' => MantenimientoEstado::EN_PROCESO,
        'descripcion_problema' => 'Revisión preventiva automatizada.',
        'diagnostico' => 'Revisión iniciada.',
        'costo' => '125.50',
    ], $userId);
    $maintenance->procesar([
        'close' => '1',
        'idmantenimiento' => (string)$maintenanceId,
        'resultado' => MantenimientoEstado::REPARADO,
        'diagnostico' => 'Equipo funcional.',
        'trabajo_realizado' => 'Limpieza y revisión.',
        'costo' => '150.00',
    ], $userId);
    $assert($db->contar('SELECT COUNT(*) FROM equipo WHERE idequipo=? AND activo=1 AND estado_equipo=?', [$equipmentId, EquipoEstado::DISPONIBLE]) === 1, 'El cierre reparado no devolvió el equipo a Disponible.');

    $maintenance->procesar([
        'add' => '1',
        'idequipo' => (string)$equipmentId,
        'tipo' => MantenimientoEstado::CORRECTIVO,
        'descripcion_problema' => 'Prueba de equipo no reparable.',
    ], $userId);
    $secondMaintenance = $db->fila(
        "SELECT idmantenimiento FROM mantenimientos WHERE idequipo=? AND estado IN ('Abierto','En proceso')",
        [$equipmentId]
    );
    $maintenance->procesar([
        'close' => '1',
        'idmantenimiento' => (string)$secondMaintenance['idmantenimiento'],
        'resultado' => MantenimientoEstado::NO_REPARABLE,
        'diagnostico' => 'Daño no reparable de prueba.',
    ], $userId);
    $assert($db->contar('SELECT COUNT(*) FROM equipo WHERE idequipo=? AND activo=0 AND estado_equipo=?', [$equipmentId, EquipoEstado::BAJA]) === 1, 'El resultado No reparable no dio de baja el equipo.');

    $returnedEquipment = $db->fila(
        "SELECT eq.idequipo FROM equipo eq
         WHERE eq.activo=1 AND eq.estado_equipo=?
           AND NOT EXISTS (SELECT 1 FROM asignacion a WHERE a.idequipo=eq.idequipo AND a.activa=1)
           AND NOT EXISTS (SELECT 1 FROM mantenimientos m WHERE m.idequipo=eq.idequipo AND m.estado IN ('Abierto','En proceso'))
         ORDER BY eq.idequipo LIMIT 1",
        [EquipoEstado::DISPONIBLE]
    );
    $assert($returnedEquipment !== null, 'No hay otro equipo para probar la devolución con daño.');
    $returnedEquipmentId = (int)$returnedEquipment['idequipo'];
    $assignmentId = (int)$db->ejecutar(
        'INSERT INTO asignacion (idempleado,idequipo,activa,condicion_entrega,requiere_firma_entrega) VALUES (?,?,1,?,0)',
        [(int)$employee['idempleado'], $returnedEquipmentId, 'Bueno']
    );
    $db->ejecutar('UPDATE equipo SET estado_equipo=? WHERE idequipo=?', [EquipoEstado::ASIGNADO, $returnedEquipmentId]);
    $generatedMaintenance = $db->transaccion(function (Database $transaction) use (
        $maintenance, $assignmentId, $returnedEquipmentId, $userId
    ): int {
        $transaction->ejecutar(
            'UPDATE asignacion SET activa=0,fecha_devolucion=NOW(),condicion_devolucion=? WHERE idasignacion=?',
            ['Con daño', $assignmentId]
        );
        return $maintenance->abrirDesdeDevolucion(
            $assignmentId,
            $returnedEquipmentId,
            'Con daño',
            'Generado por prueba de integración.',
            $userId
        );
    });
    $assert($generatedMaintenance > 0, 'La devolución con daño no generó mantenimiento.');
    $assert($db->contar('SELECT COUNT(*) FROM equipo WHERE idequipo=? AND estado_equipo=?', [$returnedEquipmentId, EquipoEstado::MANTENIMIENTO]) === 1, 'La devolución con daño no actualizó el equipo.');

    echo json_encode([
        'proveedor' => $providerId,
        'mantenimiento_manual' => $maintenanceId,
        'mantenimiento_devolucion' => $generatedMaintenance,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage());
    exit(1);
}
