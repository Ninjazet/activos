<?php
// ============================================================
// GestActivos - Alta contextual de catálogos para formularios
// Devuelve JSON y nunca redirige, para que el formulario principal
// continúe abierto y seleccione inmediatamente el registro creado.
// ============================================================
require_once __DIR__ . '/../../../bootstrap.php';

Auth::iniciar();
header('Content-Type: application/json; charset=UTF-8');

function responderCatalogo(int $estado, array $datos): void {
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderCatalogo(405, ['success' => false, 'message' => 'Método no permitido.']);
}
if (empty($_SESSION['usuario'])) {
    responderCatalogo(401, ['success' => false, 'message' => 'Tu sesión expiró. Inicia sesión nuevamente.']);
}
if ((string)($_SESSION['maestros'] ?? '0') !== '1') {
    responderCatalogo(403, ['success' => false, 'message' => 'No tienes permiso para administrar catálogos.']);
}

$tokenSesion = $_SESSION['csrf_token'] ?? '';
$tokenRecibido = $_POST['csrf_token'] ?? '';
if (!is_string($tokenRecibido) || $tokenSesion === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
    responderCatalogo(419, ['success' => false, 'message' => 'El formulario expiró. Recarga la página e intenta de nuevo.']);
}

$catalogos = [
    'marca' => [
        'tabla' => 'marca', 'id' => 'idmarca', 'campo' => 'nombreMarca',
        'etiqueta' => 'Marca', 'modulo' => 'marca', 'maximo' => 50, 'femenino' => true,
    ],
    'modelo' => [
        'tabla' => 'modelo', 'id' => 'idmodelo', 'campo' => 'nombreModelo',
        'etiqueta' => 'Modelo', 'modulo' => 'modelo', 'maximo' => 50, 'femenino' => false,
    ],
    'area' => [
        'tabla' => 'areas', 'id' => 'idarea', 'campo' => 'descripcionarea',
        'etiqueta' => 'Área', 'modulo' => 'areas', 'maximo' => 100, 'femenino' => true,
    ],
    'cargo' => [
        'tabla' => 'cargos', 'id' => 'idcargo', 'campo' => 'descripcioncargo',
        'etiqueta' => 'Cargo', 'modulo' => 'cargos', 'maximo' => 100, 'femenino' => false,
    ],
];

$tipoRecibido = $_POST['tipo'] ?? '';
$nombreRecibido = $_POST['nombre'] ?? '';
if (!is_string($tipoRecibido) || !is_string($nombreRecibido)) {
    responderCatalogo(422, ['success' => false, 'message' => 'Los datos recibidos no son válidos.']);
}

$tipo = strtolower(trim($tipoRecibido));
if (!isset($catalogos[$tipo])) {
    responderCatalogo(422, ['success' => false, 'message' => 'El catálogo solicitado no es válido.']);
}

$nombre = trim($nombreRecibido);
$normalizado = preg_replace('/\s+/u', ' ', $nombre);
if (is_string($normalizado)) {
    $nombre = $normalizado;
}

$config = $catalogos[$tipo];
$longitud = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);
if ($nombre === '') {
    responderCatalogo(422, ['success' => false, 'message' => $config['etiqueta'] . ': escribe un nombre.']);
}
if ($longitud > $config['maximo']) {
    responderCatalogo(422, ['success' => false, 'message' => $config['etiqueta'] . ': el nombre supera el máximo permitido.']);
}

$db = Database::getInstance();

try {
    $resultado = $db->transaccion(function (Database $db) use ($config, $nombre): array {
        $existente = $db->fila(
            "SELECT {$config['id']} AS id, {$config['campo']} AS nombre, activo
             FROM {$config['tabla']}
             WHERE {$config['campo']} = ?
             LIMIT 1 FOR UPDATE",
            [$nombre]
        );

        if ($existente) {
            $reactivado = (int)$existente['activo'] !== 1;
            if ($reactivado) {
                $db->ejecutar(
                    "UPDATE {$config['tabla']} SET activo=1 WHERE {$config['id']}=?",
                    [(int)$existente['id']]
                );
            }
            return [
                'id' => (int)$existente['id'],
                'nombre' => (string)$existente['nombre'],
                'existente' => true,
                'reactivado' => $reactivado,
            ];
        }

        $id = (int)$db->ejecutar(
            "INSERT INTO {$config['tabla']} ({$config['campo']}, activo) VALUES (?, 1)",
            [$nombre]
        );
        return ['id' => $id, 'nombre' => $nombre, 'existente' => false, 'reactivado' => false];
    });

    if (!$resultado['existente'] || $resultado['reactivado']) {
        Auth::registrarBitacora(
            (int)Auth::get('idusuario'),
            Auth::get('usuario'),
            $resultado['reactivado'] ? 'reactivar' : 'crear',
            $config['modulo'],
            $resultado['nombre'] . ' (alta contextual)'
        );
    }

    $seleccionado = $config['femenino'] ? 'seleccionada' : 'seleccionado';
    if ($resultado['reactivado']) {
        $reactivado = $config['femenino'] ? 'reactivada' : 'reactivado';
        $mensaje = $config['etiqueta'] . " $reactivado y $seleccionado correctamente.";
    } elseif ($resultado['existente']) {
        $mensaje = $config['etiqueta'] . " existente $seleccionado correctamente.";
    } else {
        $creado = $config['femenino'] ? 'creada' : 'creado';
        $mensaje = $config['etiqueta'] . " $creado y $seleccionado correctamente.";
    }

    responderCatalogo(200, [
        'success' => true,
        'tipo' => $tipo,
        'id' => $resultado['id'],
        'nombre' => $resultado['nombre'],
        'message' => $mensaje,
    ]);
} catch (Throwable $e) {
    error_log('GestActivos - Error en alta contextual: ' . $e->getMessage());
    responderCatalogo(500, ['success' => false, 'message' => 'No se pudo guardar el catálogo. Intenta nuevamente.']);
}
