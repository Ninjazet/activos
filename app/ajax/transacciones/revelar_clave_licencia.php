<?php

require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('licencias');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$responder = static function (int $estado, array $datos): void {
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    $responder(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}
if (!Auth::csrfValido($_POST['csrf_token'] ?? null)) {
    $responder(419, ['ok' => false, 'mensaje' => 'La sesión o el formulario expiraron. Recarga la página.']);
}

$id = filter_var($_POST['idlicencia'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || (int)$id <= 0) {
    $responder(422, ['ok' => false, 'mensaje' => 'La licencia indicada no es válida.']);
}

try {
    $resultado = (new LicenciaService(Database::getInstance()))->revelarClave((int)$id);
    Auth::registrarBitacora(
        (int)Auth::get('idusuario'),
        Auth::get('usuario'),
        'revelar_clave',
        'licencias',
        $resultado['codigo'] . ' (#' . (int)$id . ')'
    );
    $responder(200, ['ok' => true, 'clave' => $resultado['clave']]);
} catch (RuntimeException $e) {
    $responder(422, ['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('GestActivos - Error al revelar clave de licencia: ' . $e->getMessage());
    $responder(500, ['ok' => false, 'mensaje' => 'No se pudo consultar la clave de producto.']);
}
