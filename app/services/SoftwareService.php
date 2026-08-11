<?php

/**
 * Catálogo de productos de software disponibles para licenciamiento.
 */
final class SoftwareService {
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
        throw new RuntimeException('La operación del software no es válida.');
    }

    public function listar(string $busqueda = '', ?int $activo = null, string $categoria = ''): array {
        $sql = "SELECT s.*,
                       COUNT(l.idlicencia) AS licencias,
                       COALESCE(SUM(CASE WHEN l.activo=1 THEN 1 ELSE 0 END),0) AS licencias_activas
                FROM software s
                LEFT JOIN licencias l ON l.idsoftware=s.idsoftware";
        $condiciones = [];
        $params = [];
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $condiciones[] = '(s.nombre LIKE ? OR s.fabricante LIKE ? OR s.version LIKE ? OR s.edicion LIKE ? OR s.categoria LIKE ?)';
            $params = [$like, $like, $like, $like, $like];
        }
        if ($activo !== null) {
            $condiciones[] = 's.activo=?';
            $params[] = $activo;
        }
        if ($categoria !== '') {
            $condiciones[] = 's.categoria=?';
            $params[] = $categoria;
        }
        if ($condiciones) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $sql .= ' GROUP BY s.idsoftware ORDER BY s.idsoftware DESC';
        return $this->db->consulta($sql, $params);
    }

    public function opciones(bool $incluirInactivos = true): array {
        $sql = 'SELECT idsoftware,nombre,fabricante,version,edicion,activo FROM software';
        if (!$incluirInactivos) {
            $sql .= ' WHERE activo=1';
        }
        return $this->db->consulta($sql . ' ORDER BY fabricante,nombre,version,edicion');
    }

    public function categorias(): array {
        return array_column($this->db->consulta(
            "SELECT DISTINCT categoria FROM software
             WHERE categoria IS NOT NULL AND categoria<>'' ORDER BY categoria"
        ), 'categoria');
    }

    public function obtener(int $id): ?array {
        return $this->db->fila('SELECT * FROM software WHERE idsoftware=?', [$id]);
    }

    private function crear(array $datos): array {
        $valores = $this->normalizar($datos);
        $this->validarDuplicado($valores);
        $id = (int)$this->db->ejecutar(
            'INSERT INTO software (nombre,fabricante,version,edicion,categoria,descripcion,activo)
             VALUES (?,?,?,?,?,?,1)',
            array_values($valores)
        );
        return [
            'accion' => 'crear',
            'detalle' => "#$id {$valores['fabricante']} {$valores['nombre']}",
            'mensaje' => 'Software agregado correctamente.',
        ];
    }

    private function editar(array $datos): array {
        $id = Validacion::enteroPositivo($datos['idsoftware'] ?? null, 'El software');
        if (!$this->obtener($id)) {
            throw new RuntimeException('El software indicado no existe.');
        }
        $valores = $this->normalizar($datos);
        $this->validarDuplicado($valores, $id);
        $this->db->ejecutar(
            'UPDATE software SET nombre=?,fabricante=?,version=?,edicion=?,categoria=?,descripcion=?
             WHERE idsoftware=?',
            array_merge(array_values($valores), [$id])
        );
        return [
            'accion' => 'editar',
            'detalle' => "#$id {$valores['fabricante']} {$valores['nombre']}",
            'mensaje' => 'Software actualizado correctamente.',
        ];
    }

    private function cambiarEstado(array $datos): array {
        $id = Validacion::enteroPositivo($datos['idsoftware'] ?? null, 'El software');
        $fila = $this->obtener($id);
        if (!$fila) {
            throw new RuntimeException('El software indicado no existe.');
        }
        $activar = (int)$fila['activo'] !== 1;
        if (!$activar && $this->db->contar(
            'SELECT COUNT(*) FROM licencias WHERE idsoftware=? AND activo=1',
            [$id]
        ) > 0) {
            throw new RuntimeException('No se puede dar de baja porque tiene licencias activas. Desactívalas primero.');
        }
        $this->db->ejecutar('UPDATE software SET activo=? WHERE idsoftware=?', [$activar ? 1 : 0, $id]);
        return [
            'accion' => $activar ? 'reactivar' : 'eliminar',
            'detalle' => "#$id {$fila['fabricante']} {$fila['nombre']}",
            'mensaje' => $activar
                ? 'Software reactivado correctamente.'
                : 'Software dado de baja correctamente. Su historial se conserva.',
        ];
    }

    private function normalizar(array $datos): array {
        $nombre = Validacion::textoOpcional($datos['nombre'] ?? null, 150, 'El nombre');
        $fabricante = Validacion::textoOpcional($datos['fabricante'] ?? null, 120, 'El fabricante');
        if ($nombre === null || $fabricante === null) {
            throw new RuntimeException('El nombre y el fabricante son obligatorios.');
        }
        return [
            'nombre' => $nombre,
            'fabricante' => $fabricante,
            'version' => Validacion::textoOpcional($datos['version'] ?? null, 60, 'La versión') ?? '',
            'edicion' => Validacion::textoOpcional($datos['edicion'] ?? null, 100, 'La edición') ?? '',
            'categoria' => Validacion::textoOpcional($datos['categoria'] ?? null, 80, 'La categoría'),
            'descripcion' => Validacion::textoOpcional($datos['descripcion'] ?? null, 500, 'La descripción'),
        ];
    }

    private function validarDuplicado(array $valores, ?int $excepto = null): void {
        $sql = 'SELECT idsoftware FROM software
                WHERE nombre=? AND fabricante=? AND version=? AND edicion=?';
        $params = [
            $valores['nombre'], $valores['fabricante'], $valores['version'], $valores['edicion'],
        ];
        if ($excepto !== null) {
            $sql .= ' AND idsoftware<>?';
            $params[] = $excepto;
        }
        if ($this->db->fila($sql, $params)) {
            throw new RuntimeException('Ya existe ese producto con la misma versión y edición.');
        }
    }
}
