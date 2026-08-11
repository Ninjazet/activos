<?php

require_once __DIR__ . '/bootstrap.php';
Auth::requerir();

$tipo = (string)($_GET['tipo'] ?? '');
$archivo = trim((string)($_GET['archivo'] ?? ''));
$directorios = [
    'empleado' => IMG_EMPLEADOS,
    'equipo' => IMG_EQUIPOS,
];

if (!isset($directorios[$tipo])
    || $archivo === ''
    || $archivo === '.'
    || $archivo === '..'
    || !preg_match('/\A[a-zA-Z0-9._-]+\z/', $archivo)
) {
    http_response_code(404);
    exit;
}

$ruta = $directorios[$tipo] . $archivo;
if (!is_file($ruta) || !is_readable($ruta)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $tiposPermitidos, true)) {
    http_response_code(404);
    exit;
}

$modificado = (int)filemtime($ruta);
$etag = '"' . hash('sha256', $tipo . '|' . $archivo . '|' . $modificado . '|' . filesize($ruta)) . '"';
if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
    http_response_code(304);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($ruta));
header('Content-Disposition: inline; filename="' . $archivo . '"');
header('Cache-Control: private, max-age=86400');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
    readfile($ruta);
}
