<?php
// Utilidades compartidas para las firmas de entrega y devolución.

function guardarFirmaDigital(string $dataUri, string $prefijo): array {
    if (strlen($dataUri) > 2 * 1024 * 1024) {
        throw new RuntimeException('La firma recibida excede el tamaño permitido.');
    }
    if (!preg_match('#^data:image/(jpeg|png);base64,([A-Za-z0-9+/=]+)$#', $dataUri, $coincidencia)) {
        throw new RuntimeException('El formato de la firma no es válido.');
    }

    $binaria = base64_decode($coincidencia[2], true);
    $info = $binaria !== false ? @getimagesizefromstring($binaria) : false;
    if ($binaria === false || strlen($binaria) < 100 || $info === false ||
        !in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
        throw new RuntimeException('La firma recibida no es una imagen válida.');
    }
    if ((int)$info[0] < 50 || (int)$info[1] < 30 || (int)$info[0] > 4000 || (int)$info[1] > 2000) {
        throw new RuntimeException('Las dimensiones de la firma no son válidas.');
    }

    if (!is_dir(IMG_FIRMAS) && !mkdir(IMG_FIRMAS, 0755, true) && !is_dir(IMG_FIRMAS)) {
        throw new RuntimeException('No se pudo preparar la carpeta de firmas.');
    }

    $prefijoSeguro = preg_replace('/[^A-Za-z0-9_-]/', '_', $prefijo) ?: 'firma';
    $extension = $info['mime'] === 'image/png' ? 'png' : 'jpg';
    $nombre = $prefijoSeguro . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $absoluta = IMG_FIRMAS . $nombre;

    if (file_put_contents($absoluta, $binaria, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo guardar la firma.');
    }

    return [
        'absoluta' => $absoluta,
        'relativa' => 'public/img/firmas/' . $nombre,
    ];
}

function eliminarFirmaDigitalTemporal(?string $ruta): void {
    if (!$ruta || !is_file($ruta)) {
        return;
    }

    $base = realpath(IMG_FIRMAS);
    $archivo = realpath($ruta);
    if ($base === false || $archivo === false) {
        return;
    }

    $baseNormalizada = rtrim(str_replace('\\', '/', $base), '/') . '/';
    $archivoNormalizado = str_replace('\\', '/', $archivo);
    if (strpos($archivoNormalizado, $baseNormalizada) === 0) {
        @unlink($archivo);
    }
}
