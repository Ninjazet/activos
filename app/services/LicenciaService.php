<?php

/**
 * Registro comercial y reglas base de las licencias de software.
 */
final class LicenciaService {
    public function __construct(private Database $db) {}

    public function procesar(array $datos): array {
        if (isset($datos['add'])) {
            return $this->crear($datos);
        }
        if (isset($datos['edit'])) {
            return $this->editar($datos);
        }
        if (isset($datos['del'])) {
            return $this->cambiarEstado($datos);
        }
        throw new RuntimeException('La operación de la licencia no es válida.');
    }

    public function listar(array $filtros = []): array {
        $sql = "SELECT l.*,s.nombre AS software,s.fabricante,s.version,s.edicion,
                       p.nombre AS proveedor,
                       (SELECT COUNT(*) FROM licencia_asignaciones la
                        WHERE la.idlicencia=l.idlicencia AND la.activa=1) AS cupos_usados,
                       (SELECT COUNT(*) FROM licencia_cupos lc
                        WHERE lc.idlicencia=l.idlicencia AND lc.activo=1
                          AND NOT EXISTS (
                            SELECT 1 FROM licencia_asignaciones la
                            WHERE la.idcupo=lc.idcupo AND la.activa=1
                          )) AS cupos_disponibles,
                       (SELECT COUNT(*) FROM licencia_cupos lc
                        WHERE lc.idlicencia=l.idlicencia AND lc.activo=0) AS cupos_retirados,
                       (SELECT COUNT(*) FROM licencia_instalaciones li
                        WHERE li.idlicencia=l.idlicencia AND li.activa=1) AS instalaciones_activas
                FROM licencias l
                INNER JOIN software s ON s.idsoftware=l.idsoftware
                LEFT JOIN proveedores p ON p.idproveedor=l.idproveedor";
        $condiciones = [];
        $params = [];
        $busqueda = (string)($filtros['busqueda'] ?? '');
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $condiciones[] = '(l.codigo_licencia LIKE ? OR s.nombre LIKE ? OR s.fabricante LIKE ?
                              OR p.nombre LIKE ? OR l.factura LIKE ? OR l.numero_contrato LIKE ?
                              OR l.licenciado_a_nombre LIKE ? OR l.licenciado_a_correo LIKE ?)';
            $params = [$like, $like, $like, $like, $like, $like, $like, $like];
        }
        foreach (['idsoftware' => 'l.idsoftware', 'idproveedor' => 'l.idproveedor'] as $clave => $columna) {
            if ((int)($filtros[$clave] ?? 0) > 0) {
                $condiciones[] = $columna . '=?';
                $params[] = (int)$filtros[$clave];
            }
        }
        if (($filtros['modalidad'] ?? '') !== '') {
            $condiciones[] = 'l.modalidad=?';
            $params[] = $filtros['modalidad'];
        }
        if (($filtros['metrica'] ?? '') !== '') {
            $condiciones[] = 'l.metrica=?';
            $params[] = $filtros['metrica'];
        }
        $estado = (string)($filtros['estado'] ?? '');
        if ($estado === 'activa') {
            $condiciones[] = 'l.activo=1';
        } elseif ($estado === 'inactiva') {
            $condiciones[] = 'l.activo=0';
        } elseif ($estado === 'vencida') {
            $condiciones[] = 'l.activo=1 AND l.fecha_vencimiento<CURDATE()';
        } elseif ($estado === 'proxima') {
            $condiciones[] = 'l.activo=1 AND l.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)';
        } elseif ($estado === 'vigente') {
            $condiciones[] = 'l.activo=1 AND (l.fecha_vencimiento IS NULL OR l.fecha_vencimiento>DATE_ADD(CURDATE(),INTERVAL 30 DAY))';
        }
        if ($condiciones) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        return $this->db->consulta($sql . ' ORDER BY l.idlicencia DESC', $params);
    }

    public function metricas(): array {
        return $this->db->fila(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(activo=1),0) AS activas,
                    COALESCE(SUM(activo=1 AND fecha_vencimiento<CURDATE()),0) AS vencidas,
                    COALESCE(SUM(activo=1 AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)),0) AS proximas
             FROM licencias"
        ) ?? ['total' => 0, 'activas' => 0, 'vencidas' => 0, 'proximas' => 0];
    }

    public function obtener(int $id): ?array {
        return $this->db->fila(
            "SELECT l.*,s.nombre AS software,s.fabricante,s.version,s.edicion,s.categoria,
                    p.nombre AS proveedor,p.activo AS proveedor_activo,
                    (SELECT COUNT(*) FROM licencia_cupos lc WHERE lc.idlicencia=l.idlicencia AND lc.activo=1) AS cupos_generados,
                    (SELECT COUNT(*) FROM licencia_asignaciones la WHERE la.idlicencia=l.idlicencia AND la.activa=1) AS cupos_usados,
                    (SELECT COUNT(*) FROM licencia_cupos lc
                     WHERE lc.idlicencia=l.idlicencia AND lc.activo=1
                       AND NOT EXISTS (
                         SELECT 1 FROM licencia_asignaciones la
                         WHERE la.idcupo=lc.idcupo AND la.activa=1
                       )) AS cupos_disponibles,
                    (SELECT COUNT(*) FROM licencia_cupos lc WHERE lc.idlicencia=l.idlicencia AND lc.activo=0) AS cupos_retirados,
                    (SELECT COUNT(*) FROM licencia_instalaciones li WHERE li.idlicencia=l.idlicencia AND li.activa=1) AS instalaciones_activas,
                    (SELECT COUNT(*) FROM licencia_renovaciones lr WHERE lr.idlicencia=l.idlicencia) AS renovaciones
             FROM licencias l
             INNER JOIN software s ON s.idsoftware=l.idsoftware
             LEFT JOIN proveedores p ON p.idproveedor=l.idproveedor
             WHERE l.idlicencia=?",
            [$id]
        );
    }

    public function proveedores(): array {
        return $this->db->consulta('SELECT idproveedor,nombre,activo FROM proveedores ORDER BY nombre');
    }

    public function revelarClave(int $id): array {
        $fila = $this->db->fila(
            'SELECT codigo_licencia,clave_cifrada FROM licencias WHERE idlicencia=?',
            [$id]
        );
        if (!$fila) {
            throw new RuntimeException('La licencia indicada no existe.');
        }
        if (empty($fila['clave_cifrada'])) {
            throw new RuntimeException('Esta licencia no tiene una clave de producto registrada.');
        }
        return [
            'codigo' => (string)$fila['codigo_licencia'],
            'clave' => SecretoLicencia::descifrar((string)$fila['clave_cifrada']),
        ];
    }

    private function crear(array $datos): array {
        $valores = $this->normalizar($datos);
        $this->validarReferencias($valores);
        $clave = $this->prepararClave($datos['clave_producto'] ?? null);

        return $this->db->transaccion(function (Database $db) use ($valores, $clave): array {
            $id = (int)$db->ejecutar(
                'INSERT INTO licencias
                 (idsoftware,idproveedor,modalidad,metrica,cantidad_total,fecha_compra,fecha_inicio,
                  fecha_vencimiento,renovacion_automatica,reutilizable,costo_total,moneda,factura,
                  orden_compra,numero_contrato,licenciado_a_nombre,licenciado_a_correo,
                  clave_cifrada,clave_mascara,clave_huella,observaciones)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                $this->parametros($valores, $clave)
            );
            $codigo = 'LIC-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
            $db->ejecutar('UPDATE licencias SET codigo_licencia=? WHERE idlicencia=?', [$codigo, $id]);
            (new LicenciaAsignacionService($db))->crearCuposIniciales($id, $valores['cantidad_total']);
            return [
                'accion' => 'crear',
                'detalle' => "$codigo software #{$valores['idsoftware']}",
                'mensaje' => 'Licencia registrada correctamente.',
            ];
        });
    }

    private function editar(array $datos): array {
        $id = Validacion::enteroPositivo($datos['idlicencia'] ?? null, 'La licencia');
        $valores = $this->normalizar($datos);
        $claveNueva = $this->prepararClave($datos['clave_producto'] ?? null, $id);
        $quitarClave = isset($datos['quitar_clave']);
        if ($claveNueva !== null && $quitarClave) {
            throw new RuntimeException('Elige entre reemplazar o eliminar la clave de producto.');
        }

        return $this->db->transaccion(function (Database $db) use ($id, $valores, $claveNueva, $quitarClave): array {
            $actual = $db->fila('SELECT * FROM licencias WHERE idlicencia=? FOR UPDATE', [$id]);
            if (!$actual) {
                throw new RuntimeException('La licencia indicada no existe.');
            }
            $this->validarReferencias($valores, $actual);
            $this->validarCambiosConHistorial($id, $actual, $valores);
            $clave = $claveNueva;
            if ($clave === null && !$quitarClave) {
                $clave = [
                    'cifrada' => $actual['clave_cifrada'],
                    'mascara' => $actual['clave_mascara'],
                    'huella' => $actual['clave_huella'],
                ];
            }
            if ($quitarClave) {
                $clave = ['cifrada' => null, 'mascara' => null, 'huella' => null];
            }
            (new LicenciaAsignacionService($db))->ajustarCupos(
                $id,
                $actual['cantidad_total'] === null ? null : (int)$actual['cantidad_total'],
                $valores['cantidad_total']
            );
            $db->ejecutar(
                'UPDATE licencias SET idsoftware=?,idproveedor=?,modalidad=?,metrica=?,cantidad_total=?,
                 fecha_compra=?,fecha_inicio=?,fecha_vencimiento=?,renovacion_automatica=?,reutilizable=?,
                 costo_total=?,moneda=?,factura=?,orden_compra=?,numero_contrato=?,licenciado_a_nombre=?,
                 licenciado_a_correo=?,clave_cifrada=?,clave_mascara=?,clave_huella=?,observaciones=?
                 WHERE idlicencia=?',
                array_merge($this->parametros($valores, $clave), [$id])
            );
            return [
                'accion' => 'editar',
                'detalle' => "{$actual['codigo_licencia']} (#$id)",
                'mensaje' => 'Licencia actualizada correctamente.',
            ];
        });
    }

    private function cambiarEstado(array $datos): array {
        $id = Validacion::enteroPositivo($datos['idlicencia'] ?? null, 'La licencia');
        return $this->db->transaccion(function (Database $db) use ($id): array {
            $fila = $db->fila(
                'SELECT l.codigo_licencia,l.activo,s.activo AS software_activo
                 FROM licencias l INNER JOIN software s ON s.idsoftware=l.idsoftware
                 WHERE l.idlicencia=? FOR UPDATE',
                [$id]
            );
            if (!$fila) {
                throw new RuntimeException('La licencia indicada no existe.');
            }
            $activar = (int)$fila['activo'] !== 1;
            if ($activar && (int)$fila['software_activo'] !== 1) {
                throw new RuntimeException('Reactiva primero el producto de software relacionado.');
            }
            if (!$activar) {
                $ocupada = $db->contar(
                    'SELECT (SELECT COUNT(*) FROM licencia_asignaciones WHERE idlicencia=? AND activa=1)
                          + (SELECT COUNT(*) FROM licencia_instalaciones WHERE idlicencia=? AND activa=1)',
                    [$id, $id]
                );
                if ($ocupada > 0) {
                    throw new RuntimeException('No se puede desactivar mientras tenga asignaciones o instalaciones activas.');
                }
            }
            $db->ejecutar('UPDATE licencias SET activo=? WHERE idlicencia=?', [$activar ? 1 : 0, $id]);
            return [
                'accion' => $activar ? 'reactivar' : 'eliminar',
                'detalle' => "{$fila['codigo_licencia']} (#$id)",
                'mensaje' => $activar
                    ? 'Licencia reactivada correctamente.'
                    : 'Licencia desactivada correctamente. Su historial se conserva.',
            ];
        });
    }

    private function normalizar(array $datos): array {
        $modalidad = is_string($datos['modalidad'] ?? null) ? trim($datos['modalidad']) : '';
        $metrica = is_string($datos['metrica'] ?? null) ? trim($datos['metrica']) : '';
        if (!array_key_exists($modalidad, LicenciaEstado::modalidades())) {
            throw new RuntimeException('Selecciona una modalidad de licencia válida.');
        }
        if (!array_key_exists($metrica, LicenciaEstado::metricas())) {
            throw new RuntimeException('Selecciona una métrica de licencia válida.');
        }
        $cantidad = Validacion::enteroPositivoOpcional($datos['cantidad_total'] ?? null, 'La cantidad total');
        if ($cantidad !== null && $cantidad > 10000) {
            throw new RuntimeException('La cantidad total no puede superar 10000 cupos. Para licenciamiento de sitio utiliza la metrica Corporativa sin limite.');
        }
        if ($cantidad === null && $metrica !== LicenciaEstado::CORPORATIVA) {
            throw new RuntimeException('La cantidad total es obligatoria para esta métrica.');
        }
        $inicio = Validacion::fechaOpcional($datos['fecha_inicio'] ?? null, 'La fecha de inicio');
        $vencimiento = Validacion::fechaOpcional($datos['fecha_vencimiento'] ?? null, 'La fecha de vencimiento');
        if (in_array($modalidad, [LicenciaEstado::SUSCRIPCION, LicenciaEstado::PRUEBA], true)
            && ($inicio === null || $vencimiento === null)) {
            throw new RuntimeException('Las suscripciones y pruebas requieren fecha de inicio y vencimiento.');
        }
        if ($inicio !== null && $vencimiento !== null && $vencimiento < $inicio) {
            throw new RuntimeException('La fecha de vencimiento no puede ser anterior al inicio.');
        }
        $moneda = is_string($datos['moneda'] ?? null) ? strtoupper(trim($datos['moneda'])) : '';
        if (!array_key_exists($moneda, LicenciaEstado::monedas())) {
            throw new RuntimeException('Selecciona una moneda válida.');
        }
        return [
            'idsoftware' => Validacion::enteroPositivo($datos['idsoftware'] ?? null, 'El software'),
            'idproveedor' => Validacion::enteroPositivoOpcional($datos['idproveedor'] ?? null, 'El proveedor'),
            'modalidad' => $modalidad,
            'metrica' => $metrica,
            'cantidad_total' => $cantidad,
            'fecha_compra' => Validacion::fechaOpcional($datos['fecha_compra'] ?? null, 'La fecha de compra'),
            'fecha_inicio' => $inicio,
            'fecha_vencimiento' => $vencimiento,
            'renovacion_automatica' => $modalidad === LicenciaEstado::SUSCRIPCION && isset($datos['renovacion_automatica']) ? 1 : 0,
            'reutilizable' => $this->bandera($datos['reutilizable'] ?? '1', 'La opción reutilizable'),
            'costo_total' => Validacion::costoOpcional($datos['costo_total'] ?? null),
            'moneda' => $moneda,
            'factura' => Validacion::textoOpcional($datos['factura'] ?? null, 100, 'La factura'),
            'orden_compra' => Validacion::textoOpcional($datos['orden_compra'] ?? null, 100, 'La orden de compra'),
            'numero_contrato' => Validacion::textoOpcional($datos['numero_contrato'] ?? null, 100, 'El número de contrato'),
            'licenciado_a_nombre' => Validacion::textoOpcional($datos['licenciado_a_nombre'] ?? null, 150, 'El titular'),
            'licenciado_a_correo' => Validacion::correoOpcional($datos['licenciado_a_correo'] ?? null),
            'observaciones' => Validacion::textoOpcional($datos['observaciones'] ?? null, 1000, 'Las observaciones'),
        ];
    }

    private function validarReferencias(array $valores, ?array $actual = null): void {
        $software = $this->db->fila('SELECT activo FROM software WHERE idsoftware=?', [$valores['idsoftware']]);
        if (!$software) {
            throw new RuntimeException('El producto de software indicado no existe.');
        }
        if ((int)$software['activo'] !== 1 && (int)($actual['idsoftware'] ?? 0) !== $valores['idsoftware']) {
            throw new RuntimeException('El producto de software seleccionado está inactivo.');
        }
        if ($valores['idproveedor'] !== null) {
            $proveedor = $this->db->fila('SELECT activo FROM proveedores WHERE idproveedor=?', [$valores['idproveedor']]);
            if (!$proveedor) {
                throw new RuntimeException('El proveedor indicado no existe.');
            }
            if ((int)$proveedor['activo'] !== 1 && (int)($actual['idproveedor'] ?? 0) !== $valores['idproveedor']) {
                throw new RuntimeException('El proveedor seleccionado está inactivo.');
            }
        }
    }

    private function validarCambiosConHistorial(int $id, array $actual, array $valores): void {
        if ((int)$actual['idsoftware'] !== $valores['idsoftware']) {
            $historial = $this->db->contar(
                'SELECT (SELECT COUNT(*) FROM licencia_asignaciones WHERE idlicencia=?)
                      + (SELECT COUNT(*) FROM licencia_instalaciones WHERE idlicencia=?)
                      + (SELECT COUNT(*) FROM licencia_renovaciones WHERE idlicencia=?)',
                [$id, $id, $id]
            );
            if ($historial > 0) {
                throw new RuntimeException('No se puede cambiar el software porque la licencia ya tiene historial.');
            }
        }
        $destinos = LicenciaEstado::destinosPermitidos($valores['metrica']);
        if (!in_array('empleado', $destinos, true)
            && $this->db->contar('SELECT COUNT(*) FROM licencia_asignaciones WHERE idlicencia=? AND activa=1 AND idempleado IS NOT NULL', [$id]) > 0) {
            throw new RuntimeException('La métrica elegida no admite las asignaciones activas a empleados.');
        }
        if (!in_array('equipo', $destinos, true)
            && $this->db->contar('SELECT COUNT(*) FROM licencia_asignaciones WHERE idlicencia=? AND activa=1 AND idequipo IS NOT NULL', [$id]) > 0) {
            throw new RuntimeException('La métrica elegida no admite las asignaciones activas a equipos.');
        }
    }

    private function prepararClave($valor, ?int $excepto = null): ?array {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (!is_string($valor) || mb_strlen($valor) > 1000) {
            throw new RuntimeException('La clave de producto supera el tamaño permitido.');
        }
        $secreto = trim($valor);
        if ($secreto === '') {
            return null;
        }
        $huella = SecretoLicencia::huella($secreto);
        $sql = 'SELECT idlicencia FROM licencias WHERE clave_huella=?';
        $params = [$huella];
        if ($excepto !== null) {
            $sql .= ' AND idlicencia<>?';
            $params[] = $excepto;
        }
        if ($this->db->fila($sql, $params)
            || $this->db->fila('SELECT idcupo FROM licencia_cupos WHERE clave_huella=? LIMIT 1', [$huella])) {
            throw new RuntimeException('Esta clave de producto ya está registrada.');
        }
        return [
            'cifrada' => SecretoLicencia::cifrar($secreto),
            'mascara' => SecretoLicencia::mascara($secreto),
            'huella' => $huella,
        ];
    }

    private function parametros(array $valores, ?array $clave): array {
        return [
            $valores['idsoftware'], $valores['idproveedor'], $valores['modalidad'], $valores['metrica'],
            $valores['cantidad_total'], $valores['fecha_compra'], $valores['fecha_inicio'], $valores['fecha_vencimiento'],
            $valores['renovacion_automatica'], $valores['reutilizable'], $valores['costo_total'], $valores['moneda'],
            $valores['factura'], $valores['orden_compra'], $valores['numero_contrato'],
            $valores['licenciado_a_nombre'], $valores['licenciado_a_correo'],
            $clave['cifrada'] ?? null, $clave['mascara'] ?? null, $clave['huella'] ?? null,
            $valores['observaciones'],
        ];
    }

    private function bandera($valor, string $campo): int {
        if ($valor === '1' || $valor === 1) {
            return 1;
        }
        if ($valor === '0' || $valor === 0) {
            return 0;
        }
        throw new RuntimeException($campo . ' no es válida.');
    }
}
