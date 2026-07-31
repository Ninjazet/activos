<?php

/**
 * Resuelve imagenes publicas sin confiar en rutas antiguas guardadas en la BD.
 */
final class Imagen {
    public static function empleado(?string $ruta): string {
        return self::resolver(
            $ruta,
            IMG_EMPLEADOS,
            '/public/img/empleados/',
            '/public/img/empleados/avatar1.png'
        );
    }

    public static function equipo(?string $ruta): string {
        return self::resolver(
            $ruta,
            IMG_EQUIPOS,
            '/public/img/equipos/',
            '/public/icons/equipo.png'
        );
    }

    private static function resolver(
        ?string $ruta,
        string $directorio,
        string $urlDirectorio,
        string $fallback
    ): string {
        $archivo = basename(trim((string)$ruta));
        if ($archivo !== '' && is_file($directorio . $archivo)) {
            return BASE_URL . $urlDirectorio . rawurlencode($archivo);
        }
        return BASE_URL . $fallback;
    }
}
