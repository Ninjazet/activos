<?php

/**
 * CRUD comun para catalogos simples con baja logica.
 */
final class CatalogoService {
    private const CATALOGOS = [
        'areas' => [
            'tabla' => 'areas',
            'id' => 'idarea',
            'campo' => 'descripcionarea',
            'singular' => 'Área',
            'plural' => 'Áreas',
            'ruta' => 'areas.php',
            'ajax' => 'areas.php',
            'auditoria' => 'areas',
            'creado' => 'Área creada correctamente.',
            'actualizado' => 'Área actualizada correctamente.',
            'baja' => 'Área dada de baja correctamente.',
            'reactivado' => 'Área reactivada correctamente.',
        ],
        'cargos' => [
            'tabla' => 'cargos',
            'id' => 'idcargo',
            'campo' => 'descripcioncargo',
            'singular' => 'Cargo',
            'plural' => 'Cargos',
            'ruta' => 'cargo.php',
            'ajax' => 'cargo.php',
            'auditoria' => 'cargos',
            'creado' => 'Cargo creado correctamente.',
            'actualizado' => 'Cargo actualizado correctamente.',
            'baja' => 'Cargo dado de baja correctamente.',
            'reactivado' => 'Cargo reactivado correctamente.',
        ],
        'marcas' => [
            'tabla' => 'marca',
            'id' => 'idmarca',
            'campo' => 'nombreMarca',
            'singular' => 'Marca',
            'plural' => 'Marcas',
            'ruta' => 'marcas.php',
            'ajax' => 'marcas.php',
            'auditoria' => 'marca',
            'creado' => 'Marca creada correctamente.',
            'actualizado' => 'Marca actualizada correctamente.',
            'baja' => 'Marca dada de baja correctamente.',
            'reactivado' => 'Marca reactivada correctamente.',
        ],
        'modelos' => [
            'tabla' => 'modelo',
            'id' => 'idmodelo',
            'campo' => 'nombreModelo',
            'singular' => 'Modelo',
            'plural' => 'Modelos',
            'ruta' => 'modelos.php',
            'ajax' => 'modelos.php',
            'auditoria' => 'modelo',
            'creado' => 'Modelo creado correctamente.',
            'actualizado' => 'Modelo actualizado correctamente.',
            'baja' => 'Modelo dado de baja correctamente.',
            'reactivado' => 'Modelo reactivado correctamente.',
        ],
    ];

    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public static function definicion(string $catalogo): array {
        if (!isset(self::CATALOGOS[$catalogo])) {
            throw new InvalidArgumentException('Catálogo no permitido.');
        }
        return self::CATALOGOS[$catalogo];
    }

    public function listar(string $catalogo, string $busqueda = ''): array {
        $config = self::definicion($catalogo);
        $sql = "SELECT {$config['id']} AS id, {$config['campo']} AS nombre, activo
                FROM {$config['tabla']}";
        $params = [];
        if ($busqueda !== '') {
            $sql .= " WHERE ({$config['campo']} LIKE ? OR {$config['id']} LIKE ?)";
            $like = '%' . $busqueda . '%';
            $params = [$like, $like];
        }
        $sql .= " ORDER BY {$config['id']} DESC";
        return $this->db->consulta($sql, $params);
    }

    public function procesar(string $catalogo, array $datos): array {
        $config = self::definicion($catalogo);
        if (isset($datos['add'])) {
            return $this->crear($config, $datos);
        }
        if (isset($datos['edit'])) {
            return $this->editar($config, $datos);
        }
        if (isset($datos['del'])) {
            return $this->cambiarEstado($config, $datos);
        }
        throw new RuntimeException('La operación del catálogo no es válida.');
    }

    private function crear(array $config, array $datos): array {
        $nombre = $this->nombreRequerido($config, $datos['campo'] ?? null);
        if ($this->db->fila(
            "SELECT {$config['id']} FROM {$config['tabla']} WHERE {$config['campo']}=?",
            [$nombre]
        )) {
            throw new RuntimeException(
                $config['singular'] . ': ya existe un registro con ese nombre; si está inactivo, puedes reactivarlo.'
            );
        }
        $this->db->ejecutar(
            "INSERT INTO {$config['tabla']} ({$config['campo']}, activo) VALUES (?, 1)",
            [$nombre]
        );
        return [
            'accion' => 'crear',
            'detalle' => $nombre,
            'mensaje' => $config['creado'],
        ];
    }

    private function editar(array $config, array $datos): array {
        $id = Validacion::enteroPositivo($datos['id'] ?? null, 'El registro');
        $nombre = $this->nombreRequerido($config, $datos['campo'] ?? null);
        if ($this->db->fila(
            "SELECT {$config['id']} FROM {$config['tabla']}
             WHERE {$config['campo']}=? AND {$config['id']}<>?",
            [$nombre, $id]
        )) {
            throw new RuntimeException($config['singular'] . ': ya existe otro registro con ese nombre.');
        }
        $this->db->ejecutar(
            "UPDATE {$config['tabla']} SET {$config['campo']}=? WHERE {$config['id']}=?",
            [$nombre, $id]
        );
        return [
            'accion' => 'editar',
            'detalle' => $nombre,
            'mensaje' => $config['actualizado'],
        ];
    }

    private function cambiarEstado(array $config, array $datos): array {
        $id = Validacion::enteroPositivo($datos['id'] ?? null, 'El registro');
        $fila = $this->db->fila(
            "SELECT activo FROM {$config['tabla']} WHERE {$config['id']}=?",
            [$id]
        );
        if (!$fila) {
            throw new RuntimeException('El registro indicado no existe.');
        }
        $activar = (int)$fila['activo'] !== 1;
        $this->db->ejecutar(
            "UPDATE {$config['tabla']} SET activo=? WHERE {$config['id']}=?",
            [$activar ? 1 : 0, $id]
        );
        return [
            'accion' => $activar ? 'reactivar' : 'eliminar',
            'detalle' => '#' . $id,
            'mensaje' => $activar ? $config['reactivado'] : $config['baja'],
        ];
    }

    private function nombreRequerido(array $config, $valor): string {
        $nombre = Validacion::textoOpcional(
            $valor,
            100,
            $config['singular'] . ': el nombre'
        );
        if ($nombre === null) {
            throw new RuntimeException($config['singular'] . ': el nombre no puede estar vacío.');
        }
        return $nombre;
    }
}
