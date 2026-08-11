<?php

/**
 * Administra cupos y el historial de asignaciones de una licencia.
 */
final class LicenciaAsignacionService {
    public function __construct(private Database $db) {}

    public function procesar(array $datos, int $idusuario): array {
        if (isset($datos['asignar_licencia'])) {
            return $this->asignar($datos, $idusuario);
        }
        if (isset($datos['devolver_licencia'])) {
            return $this->devolver($datos, $idusuario);
        }
        throw new RuntimeException('La operacion solicitada no es valida.');
    }

    public function crearCuposIniciales(int $idlicencia, ?int $cantidad): void {
        if ($cantidad === null) {
            return;
        }
        for ($numero = 1; $numero <= $cantidad; $numero++) {
            $this->db->ejecutar(
                'INSERT INTO licencia_cupos (idlicencia,numero_cupo) VALUES (?,?)',
                [$idlicencia, $numero]
            );
        }
    }

    public function ajustarCupos(int $idlicencia, ?int $cantidadAnterior, ?int $cantidadNueva): void {
        if ($cantidadAnterior === $cantidadNueva) {
            return;
        }

        $asignacionesActivas = $this->db->contar(
            'SELECT COUNT(*) FROM licencia_asignaciones WHERE idlicencia=? AND activa=1',
            [$idlicencia]
        );
        if ($cantidadNueva === null) {
            if ($asignacionesActivas > 0) {
                throw new RuntimeException('Devuelve las asignaciones activas antes de convertir la licencia a cupos ilimitados.');
            }
            $this->db->ejecutar(
                "UPDATE licencia_cupos
                 SET activo=0,fecha_retiro=NOW(),motivo_retiro='Conversion a licencia sin limite'
                 WHERE idlicencia=? AND activo=1",
                [$idlicencia]
            );
            return;
        }

        if ($cantidadAnterior === null) {
            if ($asignacionesActivas > 0) {
                throw new RuntimeException('Devuelve las asignaciones activas antes de convertir la licencia ilimitada a cupos numerados.');
            }
            $this->crearCuposNuevos($idlicencia, $cantidadNueva);
            return;
        }

        $diferencia = $cantidadNueva - $cantidadAnterior;
        if ($diferencia > 0) {
            $this->crearCuposNuevos($idlicencia, $diferencia);
            return;
        }

        $aRetirar = abs($diferencia);
        $disponibles = $this->db->consulta(
            'SELECT lc.idcupo
             FROM licencia_cupos lc
             LEFT JOIN licencia_asignaciones la ON la.idcupo=lc.idcupo AND la.activa=1
             WHERE lc.idlicencia=? AND lc.activo=1 AND la.idasignacion_licencia IS NULL
             ORDER BY lc.numero_cupo DESC
             LIMIT ' . (int)$aRetirar . ' FOR UPDATE',
            [$idlicencia]
        );
        if (count($disponibles) < $aRetirar) {
            throw new RuntimeException('La cantidad no puede reducirse: no hay suficientes cupos libres. Devuelve primero las asignaciones necesarias.');
        }
        foreach ($disponibles as $cupo) {
            $this->db->ejecutar(
                "UPDATE licencia_cupos
                 SET activo=0,fecha_retiro=NOW(),motivo_retiro='Reduccion de cantidad adquirida'
                 WHERE idcupo=? AND activo=1",
                [(int)$cupo['idcupo']]
            );
        }
    }

    public function listarCupos(int $idlicencia): array {
        return $this->db->consulta(
            "SELECT lc.idcupo,lc.numero_cupo,lc.etiqueta,lc.clave_mascara,lc.activo,
                    lc.fecha_retiro,lc.motivo_retiro,
                    la.idasignacion_licencia,
                    CASE
                      WHEN la.idempleado IS NOT NULL THEN CONCAT(e.nombre,' ',e.apellidos)
                      WHEN la.idequipo IS NOT NULL THEN CONCAT(eq.codigo_activo,' - ',eq.tipo_equipo)
                      ELSE NULL
                    END AS asignado_a
             FROM licencia_cupos lc
             LEFT JOIN licencia_asignaciones la ON la.idcupo=lc.idcupo AND la.activa=1
             LEFT JOIN empleados e ON e.idempleado=la.idempleado
             LEFT JOIN equipo eq ON eq.idequipo=la.idequipo
             WHERE lc.idlicencia=?
             ORDER BY lc.numero_cupo",
            [$idlicencia]
        );
    }

    public function listarAsignaciones(int $idlicencia): array {
        return $this->db->consulta(
            "SELECT la.*,lc.numero_cupo,
                    CONCAT(e.nombre,' ',e.apellidos) AS empleado,
                    CONCAT(eq.codigo_activo,' - ',eq.tipo_equipo,' - ',ma.nombreMarca,' ',mo.nombreModelo) AS equipo,
                    ua.username AS usuario_asignacion,ud.username AS usuario_devolucion
             FROM licencia_asignaciones la
             LEFT JOIN licencia_cupos lc ON lc.idcupo=la.idcupo
             LEFT JOIN empleados e ON e.idempleado=la.idempleado
             LEFT JOIN equipo eq ON eq.idequipo=la.idequipo
             LEFT JOIN marca ma ON ma.idmarca=eq.idmarca_equipo
             LEFT JOIN modelo mo ON mo.idmodelo=eq.idmodelo_equipo
             LEFT JOIN usuarios ua ON ua.idusuario=la.idusuario_asignacion
             LEFT JOIN usuarios ud ON ud.idusuario=la.idusuario_devolucion
             WHERE la.idlicencia=?
             ORDER BY la.activa DESC,la.idasignacion_licencia DESC",
            [$idlicencia]
        );
    }

    public function empleadosDisponibles(int $idlicencia): array {
        return $this->db->consulta(
            "SELECT e.idempleado,CONCAT(e.nombre,' ',e.apellidos) AS nombre,e.correo
             FROM empleados e
             WHERE e.activo=1
               AND NOT EXISTS (
                 SELECT 1 FROM licencia_asignaciones la
                 WHERE la.idlicencia=? AND la.idempleado=e.idempleado AND la.activa=1
               )
             ORDER BY e.nombre,e.apellidos",
            [$idlicencia]
        );
    }

    public function equiposDisponibles(int $idlicencia): array {
        return $this->db->consulta(
            'SELECT eq.idequipo,eq.codigo_activo,eq.tipo_equipo,ma.nombreMarca,mo.nombreModelo,eq.estado_equipo
             FROM equipo eq
             INNER JOIN marca ma ON ma.idmarca=eq.idmarca_equipo
             INNER JOIN modelo mo ON mo.idmodelo=eq.idmodelo_equipo
             WHERE eq.activo=1 AND eq.estado_equipo IN (?,?,?)
               AND NOT EXISTS (
                 SELECT 1 FROM licencia_asignaciones la
                 WHERE la.idlicencia=? AND la.idequipo=eq.idequipo AND la.activa=1
               )
             ORDER BY eq.codigo_activo',
            [EquipoEstado::DISPONIBLE, EquipoEstado::ASIGNADO, EquipoEstado::MANTENIMIENTO, $idlicencia]
        );
    }

    private function asignar(array $datos, int $idusuario): array {
        $idlicencia = Validacion::enteroPositivo($datos['idlicencia'] ?? null, 'La licencia');
        $idcontexto = Validacion::enteroPositivo($datos['idlicencia_contexto'] ?? null, 'La licencia');
        if ($idlicencia !== $idcontexto) {
            throw new RuntimeException('La licencia del formulario no coincide con la ficha abierta.');
        }
        $tipoDestino = is_string($datos['tipo_destino'] ?? null) ? trim($datos['tipo_destino']) : '';
        if (!in_array($tipoDestino, ['empleado', 'equipo'], true)) {
            throw new RuntimeException('Selecciona si la licencia se asignara a un empleado o a un equipo.');
        }
        $idDestino = Validacion::enteroPositivo($datos['id_destino'] ?? null, 'El destino');
        $correo = Validacion::correoOpcional($datos['correo_cuenta'] ?? null);
        $observaciones = Validacion::textoOpcional($datos['observaciones'] ?? null, 1000, 'Las observaciones');

        return $this->db->transaccion(function (Database $db) use (
            $idlicencia, $tipoDestino, $idDestino, $correo, $observaciones, $idusuario
        ): array {
            $licencia = $db->fila(
                'SELECT l.*,s.nombre AS software FROM licencias l
                 INNER JOIN software s ON s.idsoftware=l.idsoftware
                 WHERE l.idlicencia=? FOR UPDATE',
                [$idlicencia]
            );
            if (!$licencia) {
                throw new RuntimeException('La licencia indicada no existe.');
            }
            if ((int)$licencia['activo'] !== 1) {
                throw new RuntimeException('La licencia esta inactiva y no admite nuevas asignaciones.');
            }
            if (!empty($licencia['fecha_vencimiento']) && $licencia['fecha_vencimiento'] < date('Y-m-d')) {
                throw new RuntimeException('La licencia esta vencida y no admite nuevas asignaciones.');
            }
            if (!in_array($tipoDestino, LicenciaEstado::destinosPermitidos((string)$licencia['metrica']), true)) {
                throw new RuntimeException('La metrica de esta licencia no permite ese tipo de destino.');
            }

            $idempleado = null;
            $idequipo = null;
            if ($tipoDestino === 'empleado') {
                $empleado = $db->fila('SELECT nombre,apellidos,activo FROM empleados WHERE idempleado=? FOR UPDATE', [$idDestino]);
                if (!$empleado || (int)$empleado['activo'] !== 1) {
                    throw new RuntimeException('El empleado seleccionado no existe o esta inactivo.');
                }
                $idempleado = $idDestino;
                $destinoTexto = trim($empleado['nombre'] . ' ' . $empleado['apellidos']);
            } else {
                $equipo = $db->fila('SELECT codigo_activo,activo,estado_equipo FROM equipo WHERE idequipo=? FOR UPDATE', [$idDestino]);
                if (!$equipo || (int)$equipo['activo'] !== 1
                    || !in_array((int)$equipo['estado_equipo'], [EquipoEstado::DISPONIBLE, EquipoEstado::ASIGNADO, EquipoEstado::MANTENIMIENTO], true)) {
                    throw new RuntimeException('El equipo seleccionado no esta disponible para licenciamiento.');
                }
                $idequipo = $idDestino;
                $destinoTexto = (string)$equipo['codigo_activo'];
            }

            $duplicada = $db->fila(
                'SELECT idasignacion_licencia FROM licencia_asignaciones
                 WHERE idlicencia=? AND activa=1 AND '
                    . ($tipoDestino === 'empleado' ? 'idempleado=?' : 'idequipo=?') . ' FOR UPDATE',
                [$idlicencia, $idDestino]
            );
            if ($duplicada) {
                throw new RuntimeException('Este destino ya tiene una asignacion activa de la misma licencia.');
            }

            $idcupo = null;
            if ($licencia['cantidad_total'] !== null) {
                $cupo = $db->fila(
                    'SELECT lc.idcupo
                     FROM licencia_cupos lc
                     LEFT JOIN licencia_asignaciones la ON la.idcupo=lc.idcupo AND la.activa=1
                     WHERE lc.idlicencia=? AND lc.activo=1 AND la.idasignacion_licencia IS NULL
                     ORDER BY lc.numero_cupo LIMIT 1 FOR UPDATE',
                    [$idlicencia]
                );
                if (!$cupo) {
                    throw new RuntimeException('La licencia no tiene cupos disponibles.');
                }
                $idcupo = (int)$cupo['idcupo'];
            }

            $id = (int)$db->ejecutar(
                'INSERT INTO licencia_asignaciones
                 (idlicencia,idcupo,idempleado,idequipo,correo_cuenta,observaciones,idusuario_asignacion)
                 VALUES (?,?,?,?,?,?,?)',
                [$idlicencia, $idcupo, $idempleado, $idequipo, $correo, $observaciones, $idusuario ?: null]
            );
            return [
                'accion' => 'asignar',
                'detalle' => "{$licencia['codigo_licencia']} a $destinoTexto (#$id)",
                'mensaje' => 'Licencia asignada correctamente.',
            ];
        });
    }

    private function devolver(array $datos, int $idusuario): array {
        $id = Validacion::enteroPositivo($datos['idasignacion_licencia'] ?? null, 'La asignacion');
        $idlicencia = Validacion::enteroPositivo($datos['idlicencia_contexto'] ?? null, 'La licencia');
        $motivo = Validacion::textoOpcional($datos['motivo_devolucion'] ?? null, 500, 'El motivo de devolucion');
        if ($motivo === null) {
            throw new RuntimeException('Escribe el motivo de la devolucion.');
        }

        return $this->db->transaccion(function (Database $db) use ($id, $idlicencia, $motivo, $idusuario): array {
            $asignacion = $db->fila(
                'SELECT la.*,l.codigo_licencia,l.reutilizable
                 FROM licencia_asignaciones la
                 INNER JOIN licencias l ON l.idlicencia=la.idlicencia
                 WHERE la.idasignacion_licencia=? AND la.idlicencia=? FOR UPDATE',
                [$id, $idlicencia]
            );
            if (!$asignacion) {
                throw new RuntimeException('La asignacion indicada no existe.');
            }
            if ((int)$asignacion['activa'] !== 1) {
                throw new RuntimeException('La asignacion ya fue devuelta.');
            }

            $db->ejecutar(
                'UPDATE licencia_asignaciones
                 SET activa=0,fecha_devolucion=NOW(),motivo_devolucion=?,idusuario_devolucion=?
                 WHERE idasignacion_licencia=? AND activa=1',
                [$motivo, $idusuario ?: null, $id]
            );
            if ((int)$asignacion['reutilizable'] !== 1 && $asignacion['idcupo'] !== null) {
                $db->ejecutar(
                    "UPDATE licencia_cupos
                     SET activo=0,fecha_retiro=NOW(),motivo_retiro='Consumido al devolver una licencia no reutilizable'
                     WHERE idcupo=? AND activo=1",
                    [(int)$asignacion['idcupo']]
                );
            }
            return [
                'accion' => 'devolver',
                'detalle' => "{$asignacion['codigo_licencia']} asignacion #$id",
                'mensaje' => (int)$asignacion['reutilizable'] === 1
                    ? 'Licencia devuelta; el cupo vuelve a estar disponible.'
                    : 'Licencia devuelta; el cupo no reutilizable quedo consumido.',
            ];
        });
    }

    private function crearCuposNuevos(int $idlicencia, int $cantidad): void {
        $maximo = (int)($this->db->fila(
            'SELECT COALESCE(MAX(numero_cupo),0) AS numero FROM licencia_cupos WHERE idlicencia=? FOR UPDATE',
            [$idlicencia]
        )['numero'] ?? 0);
        for ($i = 1; $i <= $cantidad; $i++) {
            $this->db->ejecutar(
                'INSERT INTO licencia_cupos (idlicencia,numero_cupo) VALUES (?,?)',
                [$idlicencia, $maximo + $i]
            );
        }
    }
}
