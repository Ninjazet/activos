<?php

/**
 * Reglas del catálogo ampliado de proveedores.
 */
final class ProveedorService {
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
            return $this->cambiarEstado($datos);
        }
        throw new RuntimeException('La operación del proveedor no es válida.');
    }

    public function listar(string $busqueda = '', ?int $activo = null): array {
        $sql = "SELECT p.*,
                       COUNT(eq.idequipo) AS equipos,
                       COALESCE(SUM(eq.costo),0) AS total_compras
                FROM proveedores p
                LEFT JOIN equipo eq ON eq.idproveedor=p.idproveedor";
        $condiciones = [];
        $params = [];
        if ($busqueda !== '') {
            $condiciones[] = '(p.nombre LIKE ? OR p.rtn LIKE ? OR p.contacto LIKE ? OR p.correo LIKE ? OR p.telefono LIKE ?)';
            $like = '%' . $busqueda . '%';
            $params = [$like, $like, $like, $like, $like];
        }
        if ($activo !== null) {
            $condiciones[] = 'p.activo=?';
            $params[] = $activo;
        }
        if ($condiciones) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $sql .= ' GROUP BY p.idproveedor ORDER BY p.idproveedor DESC';
        return $this->db->consulta($sql, $params);
    }

    public function obtener(int $id): ?array {
        return $this->db->fila(
            "SELECT p.*, COUNT(eq.idequipo) AS equipos,
                    COALESCE(SUM(eq.costo),0) AS total_compras
             FROM proveedores p
             LEFT JOIN equipo eq ON eq.idproveedor=p.idproveedor
             WHERE p.idproveedor=? GROUP BY p.idproveedor",
            [$id]
        );
    }

    public function equipos(int $id): array {
        return $this->db->consulta(
            "SELECT eq.idequipo, eq.codigo_activo, eq.numero_serie, eq.tipo_equipo,
                    eq.fecha_compra, eq.costo, eq.factura, eq.estado_equipo, eq.activo,
                    ma.nombreMarca, mo.nombreModelo
             FROM equipo eq
             INNER JOIN marca ma ON ma.idmarca=eq.idmarca_equipo
             INNER JOIN modelo mo ON mo.idmodelo=eq.idmodelo_equipo
             WHERE eq.idproveedor=?
             ORDER BY eq.fecha_compra DESC, eq.idequipo DESC",
            [$id]
        );
    }

    private function crear(array $datos): array {
        $valores = $this->normalizar($datos);
        $this->validarDuplicados($valores);
        $id = (int)$this->db->ejecutar(
            "INSERT INTO proveedores
                (nombre,rtn,contacto,telefono,correo,direccion,observaciones,activo)
             VALUES (?,?,?,?,?,?,?,1)",
            array_values($valores)
        );
        return ['accion' => 'crear', 'detalle' => "#$id {$valores['nombre']}", 'mensaje' => 'Proveedor creado correctamente.'];
    }

    private function editar(array $datos): array {
        $id = Validacion::enteroPositivo($datos['idproveedor'] ?? null, 'El proveedor');
        if (!$this->db->fila('SELECT idproveedor FROM proveedores WHERE idproveedor=?', [$id])) {
            throw new RuntimeException('El proveedor indicado no existe.');
        }
        $valores = $this->normalizar($datos);
        $this->validarDuplicados($valores, $id);
        $this->db->ejecutar(
            "UPDATE proveedores SET nombre=?,rtn=?,contacto=?,telefono=?,correo=?,direccion=?,observaciones=?
             WHERE idproveedor=?",
            array_merge(array_values($valores), [$id])
        );
        return ['accion' => 'editar', 'detalle' => "#$id {$valores['nombre']}", 'mensaje' => 'Proveedor actualizado correctamente.'];
    }

    private function cambiarEstado(array $datos): array {
        $id = Validacion::enteroPositivo($datos['idproveedor'] ?? null, 'El proveedor');
        $fila = $this->db->fila('SELECT nombre,activo FROM proveedores WHERE idproveedor=?', [$id]);
        if (!$fila) {
            throw new RuntimeException('El proveedor indicado no existe.');
        }
        $activar = (int)$fila['activo'] !== 1;
        $this->db->ejecutar('UPDATE proveedores SET activo=? WHERE idproveedor=?', [$activar ? 1 : 0, $id]);
        return [
            'accion' => $activar ? 'reactivar' : 'eliminar',
            'detalle' => "#$id {$fila['nombre']}",
            'mensaje' => $activar ? 'Proveedor reactivado correctamente.' : 'Proveedor dado de baja correctamente. El historial de compras se conserva.',
        ];
    }

    private function normalizar(array $datos): array {
        $nombre = Validacion::textoOpcional($datos['nombre'] ?? null, 150, 'El nombre');
        if ($nombre === null) {
            throw new RuntimeException('El nombre del proveedor es obligatorio.');
        }
        $rtn = Validacion::textoOpcional($datos['rtn'] ?? null, 30, 'El RTN');
        return [
            'nombre' => $nombre,
            'rtn' => $rtn !== null ? mb_strtoupper($rtn, 'UTF-8') : null,
            'contacto' => Validacion::textoOpcional($datos['contacto'] ?? null, 120, 'El contacto'),
            'telefono' => Validacion::textoOpcional($datos['telefono'] ?? null, 30, 'El teléfono'),
            'correo' => Validacion::correoOpcional($datos['correo'] ?? null),
            'direccion' => Validacion::textoOpcional($datos['direccion'] ?? null, 255, 'La dirección'),
            'observaciones' => Validacion::textoOpcional($datos['observaciones'] ?? null, 500, 'Las observaciones'),
        ];
    }

    private function validarDuplicados(array $valores, ?int $excepto = null): void {
        $sql = 'SELECT idproveedor FROM proveedores WHERE nombre=?';
        $params = [$valores['nombre']];
        if ($excepto !== null) {
            $sql .= ' AND idproveedor<>?';
            $params[] = $excepto;
        }
        if ($this->db->fila($sql, $params)) {
            throw new RuntimeException('Ya existe un proveedor con ese nombre.');
        }
        if ($valores['rtn'] !== null) {
            $sql = 'SELECT idproveedor FROM proveedores WHERE rtn=?';
            $params = [$valores['rtn']];
            if ($excepto !== null) {
                $sql .= ' AND idproveedor<>?';
                $params[] = $excepto;
            }
            if ($this->db->fila($sql, $params)) {
                throw new RuntimeException('Ya existe un proveedor con ese RTN.');
            }
        }
    }
}
