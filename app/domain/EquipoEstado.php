<?php

/**
 * Estados operativos permitidos para un equipo.
 *
 * Esta clase es la fuente unica para etiquetas, estilos y opciones de filtros.
 * La base de datos conserva enteros para no romper los registros existentes.
 */
final class EquipoEstado {
    public const DISPONIBLE = 1;
    public const ASIGNADO = 2;
    public const MANTENIMIENTO = 3;
    public const PERDIDO_ROBADO = 4;
    public const BAJA = 5;

    private const DEFINICIONES = [
        self::DISPONIBLE => ['nombre' => 'Disponible', 'badge' => 'success'],
        self::ASIGNADO => ['nombre' => 'Asignado', 'badge' => 'primary'],
        self::MANTENIMIENTO => ['nombre' => 'En mantenimiento', 'badge' => 'warning'],
        self::PERDIDO_ROBADO => ['nombre' => 'Perdido o robado', 'badge' => 'danger'],
        self::BAJA => ['nombre' => 'Dado de baja', 'badge' => 'muted'],
    ];

    public static function ids(): array {
        return array_keys(self::DEFINICIONES);
    }

    public static function idsComoTexto(): array {
        return array_map('strval', self::ids());
    }

    public static function esValido(int $estado): bool {
        return isset(self::DEFINICIONES[$estado]);
    }

    public static function nombre(?int $estado, string $fallback = 'Sin definir'): string {
        return self::DEFINICIONES[$estado]['nombre'] ?? $fallback;
    }

    public static function badge(?int $estado): string {
        return self::DEFINICIONES[$estado]['badge'] ?? 'muted';
    }

    public static function opciones(): array {
        $opciones = [];
        foreach (self::DEFINICIONES as $id => $definicion) {
            $opciones[$id] = $definicion['nombre'];
        }
        return $opciones;
    }

    public static function opcionesDevolucion(): array {
        return [
            self::DISPONIBLE => self::nombre(self::DISPONIBLE),
            self::MANTENIMIENTO => self::nombre(self::MANTENIMIENTO),
            self::PERDIDO_ROBADO => self::nombre(self::PERDIDO_ROBADO),
            self::BAJA => self::nombre(self::BAJA),
        ];
    }

    public static function desdeCondicionDevolucion(string $condicion): int {
        return $condicion === 'Bueno' ? self::DISPONIBLE : self::MANTENIMIENTO;
    }
}
