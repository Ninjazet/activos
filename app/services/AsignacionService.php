<?php

/**
 * Reglas y transacciones del ciclo de asignacion.
 *
 * No genera HTML ni redirecciones. Devuelve la informacion necesaria para que
 * el controlador registre la bitacora y presente el mensaje correspondiente.
 */
final class AsignacionService {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function procesar(array $datos): array {
        if (isset($datos['add'])) {
            return $this->crear($datos);
        }
        if (isset($datos['edit'])) {
            return $this->editar($datos);
        }
        if (isset($datos['del'])) {
            throw new RuntimeException(
                'La devolución debe completarse desde el formulario con condición física y firma de recepción.'
            );
        }
        throw new RuntimeException('La operación de asignación no es válida.');
    }

    private function crear(array $datos): array {
        $idempleado = Validacion::enteroPositivo($datos['empleado'] ?? null, 'El empleado');
        $idequipo = Validacion::enteroPositivo($datos['equipo'] ?? null, 'El equipo');
        $entrega = $this->datosEntrega($datos);

        $idAsignacion = $this->db->transaccion(function (Database $db) use (
            $idempleado,
            $idequipo,
            $entrega
        ): int {
            $empleado = $db->fila(
                'SELECT activo FROM empleados WHERE idempleado=? FOR UPDATE',
                [$idempleado]
            );
            $equipo = $db->fila(
                'SELECT activo, estado_equipo FROM equipo WHERE idequipo=? FOR UPDATE',
                [$idequipo]
            );
            if (!$empleado || (int)$empleado['activo'] !== 1) {
                throw new RuntimeException('El empleado seleccionado no está activo.');
            }
            if (!$equipo || (int)$equipo['activo'] !== 1) {
                throw new RuntimeException('El equipo seleccionado está dado de baja.');
            }
            if ((int)$equipo['estado_equipo'] !== EquipoEstado::DISPONIBLE) {
                throw new RuntimeException('El equipo ya no está disponible para asignación.');
            }
            if ($db->fila(
                'SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1',
                [$idequipo]
            )) {
                throw new RuntimeException('El equipo ya tiene una asignación abierta.');
            }

            $id = (int)$db->ejecutar(
                "INSERT INTO asignacion
                    (idempleado, idequipo, activa, fecha_asignacion, condicion_entrega,
                     entrega_cargador, entrega_maletin, entrega_otros, observaciones_entrega,
                     requiere_firma_entrega)
                 VALUES (?, ?, 1, NOW(), ?, ?, ?, ?, ?, 1)",
                [
                    $idempleado,
                    $idequipo,
                    $entrega['condicion'],
                    $entrega['cargador'],
                    $entrega['maletin'],
                    $entrega['otros'],
                    $entrega['observaciones'],
                ]
            );
            $db->ejecutar(
                'UPDATE equipo SET estado_equipo=? WHERE idequipo=?',
                [EquipoEstado::ASIGNADO, $idequipo]
            );
            return $id;
        });

        return [
            'accion' => 'crear',
            'detalle' => "#$idAsignacion emp=$idempleado equipo=$idequipo",
            'mensaje' => 'Asignación creada con su checklist de entrega. El equipo quedó marcado como asignado.',
        ];
    }

    private function editar(array $datos): array {
        $id = Validacion::enteroPositivo($datos['idasignacion'] ?? null, 'La asignación');
        $idempleado = Validacion::enteroPositivo($datos['empleado'] ?? null, 'El empleado');
        $idequipo = Validacion::enteroPositivo($datos['equipo'] ?? null, 'El equipo');
        $entrega = $this->datosEntrega($datos);

        $equipoAnterior = $this->db->transaccion(function (Database $db) use (
            $id,
            $idempleado,
            $idequipo,
            $entrega
        ): int {
            $asignacion = $db->fila(
                'SELECT idequipo, firma FROM asignacion WHERE idasignacion=? AND activa=1 FOR UPDATE',
                [$id]
            );
            if (!$asignacion) {
                throw new RuntimeException('La asignación ya está cerrada o no existe.');
            }
            if (!empty($asignacion['firma'])) {
                throw new RuntimeException(
                    'Una asignación con acta firmada no puede editarse. Debe conservarse exactamente como fue aceptada.'
                );
            }

            $anterior = (int)$asignacion['idequipo'];
            $empleado = $db->fila(
                'SELECT activo FROM empleados WHERE idempleado=? FOR UPDATE',
                [$idempleado]
            );
            $equipo = $db->fila(
                'SELECT activo, estado_equipo FROM equipo WHERE idequipo=? FOR UPDATE',
                [$idequipo]
            );
            if (!$empleado || (int)$empleado['activo'] !== 1) {
                throw new RuntimeException('El empleado seleccionado no está activo.');
            }
            if (!$equipo || (int)$equipo['activo'] !== 1) {
                throw new RuntimeException('El equipo seleccionado está dado de baja.');
            }
            if (
                $idequipo !== $anterior
                && (int)$equipo['estado_equipo'] !== EquipoEstado::DISPONIBLE
            ) {
                throw new RuntimeException('El nuevo equipo no está disponible.');
            }
            if ($db->fila(
                'SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1 AND idasignacion<>?',
                [$idequipo, $id]
            )) {
                throw new RuntimeException('El equipo ya tiene otra asignación abierta.');
            }

            $db->ejecutar(
                "UPDATE asignacion
                 SET idempleado=?, idequipo=?, condicion_entrega=?, entrega_cargador=?,
                     entrega_maletin=?, entrega_otros=?, observaciones_entrega=?
                 WHERE idasignacion=?",
                [
                    $idempleado,
                    $idequipo,
                    $entrega['condicion'],
                    $entrega['cargador'],
                    $entrega['maletin'],
                    $entrega['otros'],
                    $entrega['observaciones'],
                    $id,
                ]
            );
            if ($anterior !== $idequipo) {
                $db->ejecutar(
                    'UPDATE equipo SET estado_equipo=? WHERE idequipo=? AND activo=1',
                    [EquipoEstado::DISPONIBLE, $anterior]
                );
            }
            $db->ejecutar(
                'UPDATE equipo SET estado_equipo=? WHERE idequipo=?',
                [EquipoEstado::ASIGNADO, $idequipo]
            );
            return $anterior;
        });

        return [
            'accion' => 'editar',
            'detalle' => "#$id emp=$idempleado equipo=$idequipo anterior=$equipoAnterior",
            'mensaje' => 'Asignación y checklist actualizados correctamente.',
        ];
    }

    private function datosEntrega(array $datos): array {
        $condicion = Validacion::textoOpcional(
            $datos['condicion_entrega'] ?? null,
            30,
            'La condición de entrega'
        );
        if (!in_array($condicion, ['Nuevo', 'Excelente', 'Bueno', 'Regular'], true)) {
            throw new RuntimeException('Selecciona una condición de entrega válida.');
        }

        return [
            'condicion' => $condicion,
            'cargador' => isset($datos['entrega_cargador']) ? 1 : 0,
            'maletin' => isset($datos['entrega_maletin']) ? 1 : 0,
            'otros' => Validacion::textoOpcional(
                $datos['entrega_otros'] ?? null,
                255,
                'El detalle de otros accesorios'
            ),
            'observaciones' => Validacion::textoOpcional(
                $datos['observaciones_entrega'] ?? null,
                500,
                'Las observaciones de entrega'
            ),
        ];
    }
}
