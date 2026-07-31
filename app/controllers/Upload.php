<?php
// ============================================================
// GestActivos - Validación segura de archivos subidos
//
// Nunca confía en el nombre de archivo ni en la extensión que
// manda el navegador: detecta el tipo MIME real leyendo el
// contenido del archivo, y genera un nombre nuevo aleatorio.
// Esto evita poder subir un .php (u otro ejecutable) disfrazado
// de imagen dentro de una carpeta pública.
// ============================================================

class Upload {

    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    private const TAMANO_MAXIMO = 5 * 1024 * 1024; // 5 MB

    /**
     * Valida y guarda un archivo subido como imagen.
     * Devuelve el nombre final del archivo (sin ruta).
     * Lanza RuntimeException con un mensaje seguro de mostrar al usuario
     * si algo no es válido.
     */
    public static function guardarImagen(array $archivo, string $carpetaDestino, string $prefijo): string {
        if (!isset($archivo['error']) || is_array($archivo['error'])) {
            throw new \RuntimeException('Archivo inválido.');
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Error al subir el archivo (código ' . $archivo['error'] . ').');
        }
        if (!is_uploaded_file($archivo['tmp_name'])) {
            throw new \RuntimeException('Archivo no válido.');
        }
        if ($archivo['size'] <= 0 || $archivo['size'] > self::TAMANO_MAXIMO) {
            throw new \RuntimeException('El archivo debe pesar entre 1 byte y 5 MB.');
        }

        // 1) Tipo MIME real, leyendo el contenido del archivo (no el nombre)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($archivo['tmp_name']);

        if (!isset(self::TIPOS_PERMITIDOS[$mime])) {
            throw new \RuntimeException('Solo se permiten imágenes JPG, PNG, GIF o WEBP.');
        }

        // 2) Verificación adicional: que realmente se pueda decodificar como imagen
        $info = @getimagesize($archivo['tmp_name']);
        if ($info === false) {
            throw new \RuntimeException('El archivo no es una imagen válida.');
        }

        // 3) Nombre nuevo, aleatorio. Nunca se usa el nombre original.
        $extension   = self::TIPOS_PERMITIDOS[$mime];
        $nombreFinal = $prefijo . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

        // En Docker el volumen puede existir sin sus subcarpetas. Se crean al
        // primer uso; si el usuario de Apache/PHP no tiene permisos, se
        // conserva un mensaje claro para corregir el montaje.
        if (!is_dir($carpetaDestino)
            && !mkdir($carpetaDestino, 0775, true)
            && !is_dir($carpetaDestino)
        ) {
            throw new \RuntimeException('No se pudo crear la carpeta de destino para la imagen.');
        }
        if (!is_writable($carpetaDestino)) {
            throw new \RuntimeException('La carpeta de destino no tiene permisos de escritura.');
        }

        if (!move_uploaded_file($archivo['tmp_name'], $carpetaDestino . $nombreFinal)) {
            throw new \RuntimeException('No se pudo guardar el archivo en el servidor.');
        }

        return $nombreFinal;
    }

    // true si el campo de archivo viene vacío (el usuario no seleccionó nada)
    public static function estaVacio(?array $archivo): bool {
        return empty($archivo['name']) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;
    }
}
