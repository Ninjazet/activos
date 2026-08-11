<?php

/**
 * Fuente única de modalidades, métricas y estados calculados de licencias.
 */
final class LicenciaEstado {
    public const PERPETUA = 'Perpetua';
    public const SUSCRIPCION = 'Suscripción';
    public const PRUEBA = 'Prueba';

    public const POR_USUARIO = 'Usuario';
    public const POR_DISPOSITIVO = 'Dispositivo';
    public const CONCURRENTE = 'Concurrente';
    public const CORPORATIVA = 'Corporativa';
    public const SERVIDOR_PROCESADOR = 'Servidor/Procesador';

    public const VIGENTE = 'Vigente';
    public const PERPETUA_VIGENTE = 'Perpetua';
    public const PROXIMA_VENCER = 'Próxima a vencer';
    public const VENCIDA = 'Vencida';
    public const AGOTADA = 'Sin cupos';
    public const INACTIVA = 'Inactiva';

    public static function modalidades(): array {
        return [
            self::PERPETUA => self::PERPETUA,
            self::SUSCRIPCION => self::SUSCRIPCION,
            self::PRUEBA => self::PRUEBA,
        ];
    }

    public static function metricas(): array {
        return [
            self::POR_USUARIO => 'Por usuario',
            self::POR_DISPOSITIVO => 'Por dispositivo',
            self::CONCURRENTE => 'Concurrente',
            self::CORPORATIVA => 'Corporativa o de sitio',
            self::SERVIDOR_PROCESADOR => 'Servidor o procesador',
        ];
    }

    public static function monedas(): array {
        return ['HNL' => 'Lempira (HNL)', 'USD' => 'Dólar (USD)', 'EUR' => 'Euro (EUR)'];
    }

    public static function destinosPermitidos(string $metrica): array {
        return match ($metrica) {
            self::POR_USUARIO => ['empleado'],
            self::POR_DISPOSITIVO, self::SERVIDOR_PROCESADOR => ['equipo'],
            self::CONCURRENTE, self::CORPORATIVA => ['empleado', 'equipo'],
            default => [],
        };
    }

    public static function estado(
        array $licencia,
        int $cuposUsados = 0,
        ?DateTimeImmutable $hoy = null,
        int $diasAlerta = 30
    ): string {
        if ((int)($licencia['activo'] ?? 0) !== 1) {
            return self::INACTIVA;
        }

        $hoy ??= new DateTimeImmutable('today');
        $vencimientoTexto = trim((string)($licencia['fecha_vencimiento'] ?? ''));
        if ($vencimientoTexto !== '') {
            $vencimiento = DateTimeImmutable::createFromFormat('!Y-m-d', $vencimientoTexto);
            if ($vencimiento instanceof DateTimeImmutable) {
                if ($vencimiento < $hoy) {
                    return self::VENCIDA;
                }
                if ($vencimiento <= $hoy->modify('+' . max(0, $diasAlerta) . ' days')) {
                    return self::PROXIMA_VENCER;
                }
            }
        }

        $cantidad = $licencia['cantidad_total'] ?? null;
        if ($cantidad !== null && (int)$cantidad > 0 && $cuposUsados >= (int)$cantidad) {
            return self::AGOTADA;
        }

        if (($licencia['modalidad'] ?? '') === self::PERPETUA && $vencimientoTexto === '') {
            return self::PERPETUA_VIGENTE;
        }
        return self::VIGENTE;
    }

    public static function badge(string $estado): string {
        return match ($estado) {
            self::VIGENTE, self::PERPETUA_VIGENTE => 'success',
            self::PROXIMA_VENCER => 'warning',
            self::VENCIDA => 'danger',
            self::AGOTADA => 'primary',
            default => 'muted',
        };
    }
}
