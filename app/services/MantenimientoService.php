<?php

/**
 * Ciclo transaccional de mantenimiento y sincronización con equipo.
 */
final class MantenimientoService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public static function leerFiltros(?array $origen = null): array {
        $origen ??= $_POST;
        return [
            'query' => TableFilter::text('query', 150, $origen),
            'tipo' => TableFilter::enum('tipo', array_keys(MantenimientoEstado::tipos()), $origen),
            'estado' => TableFilter::enum('estado', array_keys(MantenimientoEstado::estados()), $origen),
            'idproveedor' => TableFilter::positiveInt('idproveedor', $origen),
            'fecha_desde' => TableFilter::date('fecha_desde', $origen),
            'fecha_hasta' => TableFilter::date('fecha_hasta', $origen),
        ];
    }

    public function procesar(array $datos, int $idusuario): array {
        if ($idusuario <= 0) {
            throw new RuntimeException('No se pudo identificar al usuario responsable.');
        }
        if (isset($datos['add'])) {
            return $this->abrirManual($datos, $idusuario);
        }
        if (isset($datos['save'])) {
            return $this->actualizar($datos, $idusuario);
        }
        if (isset($datos['close'])) {
            return $this->cerrar($datos, $idusuario);
        }
        if (isset($datos['cancel'])) {
            return $this->cancelar($datos, $idusuario);
        }
        throw new RuntimeException('La operación de mantenimiento no es válida.');
    }

    public function listar(array $filtros = []): array {
        $sql = "SELECT m.*, eq.codigo_activo, eq.numero_serie, eq.tipo_equipo,
                       ma.nombreMarca, mo.nombreModelo, p.nombre AS proveedor,
                       ua.username AS usuario_apertura, uc.username AS usuario_cierre
                FROM mantenimientos m
                INNER JOIN equipo eq ON eq.idequipo=m.idequipo
                INNER JOIN marca ma ON ma.idmarca=eq.idmarca_equipo
                INNER JOIN modelo mo ON mo.idmodelo=eq.idmodelo_equipo
                LEFT JOIN proveedores p ON p.idproveedor=m.idproveedor
                LEFT JOIN usuarios ua ON ua.idusuario=m.idusuario_apertura
                LEFT JOIN usuarios uc ON uc.idusuario=m.idusuario_cierre";
        $condiciones = [];
        $params = [];
        $busqueda = trim((string)($filtros['query'] ?? ''));
        if ($busqueda !== '') {
            $condiciones[] = "(eq.codigo_activo LIKE ? OR eq.numero_serie LIKE ? OR eq.tipo_equipo LIKE ?
                OR ma.nombreMarca LIKE ? OR mo.nombreModelo LIKE ? OR m.descripcion_problema LIKE ? OR p.nombre LIKE ?)";
            $like = '%' . $busqueda . '%';
            $params = [$like, $like, $like, $like, $like, $like, $like];
        }
        foreach (['tipo' => 'm.tipo', 'estado' => 'm.estado'] as $clave => $columna) {
            if (($filtros[$clave] ?? '') !== '') {
                $condiciones[] = "$columna=?";
                $params[] = $filtros[$clave];
            }
        }
        if ((int)($filtros['idproveedor'] ?? 0) > 0) {
            $condiciones[] = 'm.idproveedor=?';
            $params[] = (int)$filtros['idproveedor'];
        }
        if (($filtros['fecha_desde'] ?? '') !== '') {
            $condiciones[] = 'DATE(m.fecha_ingreso)>=?';
            $params[] = $filtros['fecha_desde'];
        }
        if (($filtros['fecha_hasta'] ?? '') !== '') {
            $condiciones[] = 'DATE(m.fecha_ingreso)<=?';
            $params[] = $filtros['fecha_hasta'];
        }
        if ($condiciones) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $sql .= ' ORDER BY m.fecha_ingreso DESC, m.idmantenimiento DESC';
        return $this->db->consulta($sql, $params);
    }

    public function metricas(): array {
        return $this->db->fila(
            "SELECT
                COALESCE(SUM(estado='Abierto'),0) abiertos,
                COALESCE(SUM(estado='En proceso'),0) en_proceso,
                COALESCE(SUM(estado IN ('Completado','Cancelado') AND YEAR(fecha_cierre)=YEAR(CURDATE()) AND MONTH(fecha_cierre)=MONTH(CURDATE())),0) cerrados_mes,
                COALESCE(SUM(CASE WHEN estado='Completado' AND YEAR(fecha_cierre)=YEAR(CURDATE()) AND MONTH(fecha_cierre)=MONTH(CURDATE()) THEN costo ELSE 0 END),0) costo_mes
             FROM mantenimientos"
        ) ?? ['abiertos' => 0, 'en_proceso' => 0, 'cerrados_mes' => 0, 'costo_mes' => 0];
    }

    public function equiposDisponibles(): array {
        return $this->db->consulta(
            "SELECT eq.idequipo, eq.codigo_activo, eq.numero_serie, eq.tipo_equipo,
                    ma.nombreMarca, mo.nombreModelo
             FROM equipo eq
             INNER JOIN marca ma ON ma.idmarca=eq.idmarca_equipo
             INNER JOIN modelo mo ON mo.idmodelo=eq.idmodelo_equipo
             WHERE eq.activo=1 AND eq.estado_equipo=?
               AND NOT EXISTS (SELECT 1 FROM asignacion a WHERE a.idequipo=eq.idequipo AND a.activa=1)
               AND NOT EXISTS (SELECT 1 FROM mantenimientos m WHERE m.idequipo=eq.idequipo AND m.estado IN ('Abierto','En proceso'))
             ORDER BY eq.codigo_activo",
            [EquipoEstado::DISPONIBLE]
        );
    }

    public function abrirDesdeDevolucion(
        int $idasignacion,
        int $idequipo,
        string $condicion,
        ?string $observaciones,
        int $idusuario
    ): int {
        return $this->db->transaccion(function (Database $db) use (
            $idasignacion, $idequipo, $condicion, $observaciones, $idusuario
        ): int {
            $existente = $db->fila(
                'SELECT idmantenimiento,idequipo FROM mantenimientos WHERE idasignacion_origen=?',
                [$idasignacion]
            );
            if ($existente) {
                if ((int)$existente['idequipo'] !== $idequipo) {
                    throw new RuntimeException('La asignación ya está vinculada a otro mantenimiento.');
                }
                return (int)$existente['idmantenimiento'];
            }
            $asignacion = $db->fila(
                'SELECT idequipo,activa FROM asignacion WHERE idasignacion=? FOR UPDATE',
                [$idasignacion]
            );
            if (!$asignacion || (int)$asignacion['idequipo'] !== $idequipo || (int)$asignacion['activa'] !== 0) {
                throw new RuntimeException('La asignación debe estar cerrada y corresponder al equipo devuelto.');
            }
            $equipo = $db->fila('SELECT activo FROM equipo WHERE idequipo=? FOR UPDATE', [$idequipo]);
            if (!$equipo || (int)$equipo['activo'] !== 1) {
                throw new RuntimeException('El equipo devuelto no está disponible para abrir mantenimiento.');
            }
            $this->asegurarSinMantenimientoAbierto($db, $idequipo);
            $descripcion = 'Equipo recibido con condición: ' . $condicion . '.';
            $id = (int)$db->ejecutar(
                "INSERT INTO mantenimientos
                    (idequipo,idasignacion_origen,tipo,estado,descripcion_problema,observaciones,
                     estado_anterior_equipo,origen,idusuario_apertura)
                 VALUES (?,?,?,'Abierto',?,?,?,?,?)",
                [
                    $idequipo, $idasignacion, MantenimientoEstado::CORRECTIVO,
                    $descripcion, $observaciones, EquipoEstado::DISPONIBLE, 'Devolución', $idusuario,
                ]
            );
            $db->ejecutar('UPDATE equipo SET estado_equipo=? WHERE idequipo=? AND activo=1', [EquipoEstado::MANTENIMIENTO, $idequipo]);
            return $id;
        });
    }

    private function abrirManual(array $datos, int $idusuario): array {
        $idequipo = Validacion::enteroPositivo($datos['idequipo'] ?? null, 'El equipo');
        $valores = $this->normalizarTrabajo($datos, true);
        $id = $this->db->transaccion(function (Database $db) use ($idequipo, $valores, $idusuario): int {
            $equipo = $db->fila('SELECT activo,estado_equipo FROM equipo WHERE idequipo=? FOR UPDATE', [$idequipo]);
            if (!$equipo || (int)$equipo['activo'] !== 1) {
                throw new RuntimeException('El equipo no está activo.');
            }
            if ((int)$equipo['estado_equipo'] !== EquipoEstado::DISPONIBLE) {
                throw new RuntimeException('Solo un equipo Disponible puede enviarse manualmente a mantenimiento.');
            }
            if ($db->fila('SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1', [$idequipo])) {
                throw new RuntimeException('El equipo tiene una asignación abierta. Debe devolverse primero.');
            }
            $this->asegurarSinMantenimientoAbierto($db, $idequipo);
            $this->validarProveedor($db, $valores['idproveedor'], true);
            $id = (int)$db->ejecutar(
                "INSERT INTO mantenimientos
                    (idequipo,idproveedor,tipo,estado,descripcion_problema,diagnostico,
                     trabajo_realizado,costo,observaciones,estado_anterior_equipo,origen,idusuario_apertura)
                 VALUES (?,?,?,'Abierto',?,?,?,?,?,?, 'Manual',?)",
                [
                    $idequipo, $valores['idproveedor'], $valores['tipo'], $valores['descripcion'],
                    $valores['diagnostico'], $valores['trabajo'], $valores['costo'],
                    $valores['observaciones'], EquipoEstado::DISPONIBLE, $idusuario,
                ]
            );
            $db->ejecutar('UPDATE equipo SET estado_equipo=? WHERE idequipo=?', [EquipoEstado::MANTENIMIENTO, $idequipo]);
            return $id;
        });
        return ['accion' => 'crear', 'detalle' => "mantenimiento #$id equipo=$idequipo", 'mensaje' => 'Mantenimiento abierto. El equipo quedó marcado como En mantenimiento.'];
    }

    private function actualizar(array $datos, int $idusuario): array {
        $id = Validacion::enteroPositivo($datos['idmantenimiento'] ?? null, 'El mantenimiento');
        $valores = $this->normalizarTrabajo($datos, true);
        $estado = (string)($datos['estado'] ?? '');
        if (!in_array($estado, MantenimientoEstado::estadosActivos(), true)) {
            throw new RuntimeException('El estado operativo del mantenimiento no es válido.');
        }
        $this->db->transaccion(function (Database $db) use ($id, $valores, $estado): void {
            $actual = $db->fila('SELECT idequipo,idproveedor,estado FROM mantenimientos WHERE idmantenimiento=? FOR UPDATE', [$id]);
            if (!$actual || !in_array($actual['estado'], MantenimientoEstado::estadosActivos(), true)) {
                throw new RuntimeException('El mantenimiento ya está cerrado o no existe.');
            }
            $conservaProveedor = (int)($actual['idproveedor'] ?? 0) === (int)($valores['idproveedor'] ?? 0);
            $this->validarProveedor($db, $valores['idproveedor'], !$conservaProveedor);
            $db->ejecutar(
                "UPDATE mantenimientos SET idproveedor=?,tipo=?,estado=?,descripcion_problema=?,
                    diagnostico=?,trabajo_realizado=?,costo=?,observaciones=? WHERE idmantenimiento=?",
                [
                    $valores['idproveedor'], $valores['tipo'], $estado, $valores['descripcion'],
                    $valores['diagnostico'], $valores['trabajo'], $valores['costo'],
                    $valores['observaciones'], $id,
                ]
            );
        });
        return ['accion' => 'editar', 'detalle' => "mantenimiento #$id usuario=$idusuario", 'mensaje' => 'Seguimiento del mantenimiento actualizado.'];
    }

    private function cerrar(array $datos, int $idusuario): array {
        $id = Validacion::enteroPositivo($datos['idmantenimiento'] ?? null, 'El mantenimiento');
        $resultado = (string)($datos['resultado'] ?? '');
        if (!array_key_exists($resultado, MantenimientoEstado::resultados())) {
            throw new RuntimeException('Selecciona un resultado válido.');
        }
        $diagnostico = Validacion::textoOpcional($datos['diagnostico'] ?? null, 1000, 'El diagnóstico');
        $trabajo = Validacion::textoOpcional($datos['trabajo_realizado'] ?? null, 1000, 'El trabajo realizado');
        if ($diagnostico === null) {
            throw new RuntimeException('El diagnóstico es obligatorio para cerrar el mantenimiento.');
        }
        $costo = Validacion::costoOpcional($datos['costo'] ?? null);
        $observaciones = Validacion::textoOpcional($datos['observaciones'] ?? null, 1000, 'Las observaciones');

        $idequipo = $this->db->transaccion(function (Database $db) use (
            $id, $resultado, $diagnostico, $trabajo, $costo, $observaciones, $idusuario
        ): int {
            $actual = $db->fila('SELECT idequipo,estado FROM mantenimientos WHERE idmantenimiento=? FOR UPDATE', [$id]);
            if (!$actual || !in_array($actual['estado'], MantenimientoEstado::estadosActivos(), true)) {
                throw new RuntimeException('El mantenimiento ya está cerrado o no existe.');
            }
            $idequipo = (int)$actual['idequipo'];
            $db->fila('SELECT idequipo FROM equipo WHERE idequipo=? FOR UPDATE', [$idequipo]);
            $db->ejecutar(
                "UPDATE mantenimientos SET estado='Completado',fecha_cierre=NOW(),diagnostico=?,
                    trabajo_realizado=?,costo=?,resultado=?,observaciones=?,idusuario_cierre=?
                 WHERE idmantenimiento=?",
                [$diagnostico, $trabajo, $costo, $resultado, $observaciones, $idusuario, $id]
            );
            if ($resultado === MantenimientoEstado::REPARADO) {
                $db->ejecutar('UPDATE equipo SET activo=1,estado_equipo=? WHERE idequipo=?', [EquipoEstado::DISPONIBLE, $idequipo]);
            } else {
                $db->ejecutar('UPDATE equipo SET activo=0,estado_equipo=? WHERE idequipo=?', [EquipoEstado::BAJA, $idequipo]);
            }
            return $idequipo;
        });
        $destino = $resultado === MantenimientoEstado::REPARADO ? 'Disponible' : 'Dado de baja';
        return ['accion' => 'cerrar', 'detalle' => "mantenimiento #$id equipo=$idequipo resultado=$resultado", 'mensaje' => "Mantenimiento cerrado. El equipo quedó $destino."];
    }

    private function cancelar(array $datos, int $idusuario): array {
        $id = Validacion::enteroPositivo($datos['idmantenimiento'] ?? null, 'El mantenimiento');
        $motivo = Validacion::textoOpcional($datos['motivo_cancelacion'] ?? null, 1000, 'El motivo de cancelación');
        if ($motivo === null) {
            throw new RuntimeException('Indica el motivo de cancelación.');
        }
        $idequipo = $this->db->transaccion(function (Database $db) use ($id, $idusuario, $motivo): int {
            $actual = $db->fila(
                'SELECT idequipo,estado,estado_anterior_equipo,observaciones FROM mantenimientos WHERE idmantenimiento=? FOR UPDATE',
                [$id]
            );
            if (!$actual || !in_array($actual['estado'], MantenimientoEstado::estadosActivos(), true)) {
                throw new RuntimeException('El mantenimiento ya está cerrado o no existe.');
            }
            $idequipo = (int)$actual['idequipo'];
            $db->fila('SELECT idequipo FROM equipo WHERE idequipo=? FOR UPDATE', [$idequipo]);
            $observaciones = trim((string)$actual['observaciones']);
            $observaciones .= ($observaciones === '' ? '' : "\n") . 'Cancelación: ' . $motivo;
            $db->ejecutar(
                "UPDATE mantenimientos SET estado='Cancelado',fecha_cierre=NOW(),observaciones=?,idusuario_cierre=? WHERE idmantenimiento=?",
                [$observaciones, $idusuario, $id]
            );
            $estadoAnterior = (int)$actual['estado_anterior_equipo'];
            if (!EquipoEstado::esValido($estadoAnterior) || in_array($estadoAnterior, [EquipoEstado::ASIGNADO, EquipoEstado::MANTENIMIENTO], true)) {
                $estadoAnterior = EquipoEstado::DISPONIBLE;
            }
            $db->ejecutar('UPDATE equipo SET activo=1,estado_equipo=? WHERE idequipo=?', [$estadoAnterior, $idequipo]);
            return $idequipo;
        });
        return ['accion' => 'cancelar', 'detalle' => "mantenimiento #$id equipo=$idequipo", 'mensaje' => 'Mantenimiento cancelado y estado del equipo restaurado.'];
    }

    private function normalizarTrabajo(array $datos, bool $descripcionObligatoria): array {
        $tipo = (string)($datos['tipo'] ?? '');
        if (!array_key_exists($tipo, MantenimientoEstado::tipos())) {
            throw new RuntimeException('Selecciona un tipo de mantenimiento válido.');
        }
        $descripcion = Validacion::textoOpcional($datos['descripcion_problema'] ?? null, 1000, 'La descripción del problema');
        if ($descripcionObligatoria && $descripcion === null) {
            throw new RuntimeException('La descripción del problema es obligatoria.');
        }
        return [
            'idproveedor' => Validacion::enteroPositivoOpcional($datos['idproveedor'] ?? null, 'El proveedor'),
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'diagnostico' => Validacion::textoOpcional($datos['diagnostico'] ?? null, 1000, 'El diagnóstico'),
            'trabajo' => Validacion::textoOpcional($datos['trabajo_realizado'] ?? null, 1000, 'El trabajo realizado'),
            'costo' => Validacion::costoOpcional($datos['costo'] ?? null),
            'observaciones' => Validacion::textoOpcional($datos['observaciones'] ?? null, 1000, 'Las observaciones'),
        ];
    }

    private function asegurarSinMantenimientoAbierto(Database $db, int $idequipo): void {
        if ($db->fila(
            "SELECT idmantenimiento FROM mantenimientos
             WHERE idequipo=? AND estado IN ('Abierto','En proceso')",
            [$idequipo]
        )) {
            throw new RuntimeException('El equipo ya tiene un mantenimiento abierto.');
        }
    }

    private function validarProveedor(Database $db, ?int $idproveedor, bool $debeEstarActivo): void {
        if ($idproveedor === null) {
            return;
        }
        $proveedor = $db->fila('SELECT activo FROM proveedores WHERE idproveedor=?', [$idproveedor]);
        if (!$proveedor || ($debeEstarActivo && (int)$proveedor['activo'] !== 1)) {
            throw new RuntimeException('El proveedor seleccionado no está disponible.');
        }
    }
}
