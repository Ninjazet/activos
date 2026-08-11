<?php

/**
 * Resuelve imagenes publicas sin confiar en rutas antiguas guardadas en la BD.
 */
final class Imagen {
    public static function empleado(?string $ruta): string {
        return self::resolver(
            $ruta,
            IMG_EMPLEADOS,
            'empleado',
            '/public/img/empleados/avatar1.png'
        );
    }

    public static function equipo(?string $ruta): string {
        return self::resolver(
            $ruta,
            IMG_EQUIPOS,
            'equipo',
            '/public/icons/equipo.png'
        );
    }

    public static function firmaRuta(?string $ruta): string {
        $archivo = basename(trim((string)$ruta));
        return $archivo === '' ? '' : IMG_FIRMAS . $archivo;
    }

    private static function resolver(
        ?string $ruta,
        string $directorio,
        string $tipo,
        string $fallback
    ): string {
        $archivo = basename(trim((string)$ruta));
        if ($archivo !== '' && is_file($directorio . $archivo)) {
            return BASE_URL . '/media.php?' . http_build_query(
                ['tipo' => $tipo, 'archivo' => $archivo],
                '',
                '&',
                PHP_QUERY_RFC3986
            );
        }
        return BASE_URL . $fallback;
    }
}
