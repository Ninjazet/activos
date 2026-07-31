<?php

final class MantenimientoEstado {
    public const ABIERTO = 'Abierto';
    public const EN_PROCESO = 'En proceso';
    public const COMPLETADO = 'Completado';
    public const CANCELADO = 'Cancelado';

    public const PREVENTIVO = 'Preventivo';
    public const CORRECTIVO = 'Correctivo';

    public const REPARADO = 'Reparado';
    public const NO_REPARABLE = 'No reparable';

    public static function estados(): array {
        return [
            self::ABIERTO => self::ABIERTO,
            self::EN_PROCESO => self::EN_PROCESO,
            self::COMPLETADO => self::COMPLETADO,
            self::CANCELADO => self::CANCELADO,
        ];
    }

    public static function estadosActivos(): array {
        return [self::ABIERTO, self::EN_PROCESO];
    }

    public static function tipos(): array {
        return [self::PREVENTIVO => self::PREVENTIVO, self::CORRECTIVO => self::CORRECTIVO];
    }

    public static function resultados(): array {
        return [self::REPARADO => self::REPARADO, self::NO_REPARABLE => self::NO_REPARABLE];
    }

    public static function badge(string $estado): string {
        return match ($estado) {
            self::ABIERTO => 'warning',
            self::EN_PROCESO => 'primary',
            self::COMPLETADO => 'success',
            self::CANCELADO => 'muted',
            default => 'muted',
        };
    }
}
