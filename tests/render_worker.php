<?php

$raiz = dirname(__DIR__);
$rutas = [
    'index' => 'index.php',
    'equipos' => 'equipos.php',
    'equipos_filtrados' => 'equipos.php',
    'empleados' => 'empleados.php',
    'asignaciones' => 'asignarequipo.php',
    'proveedores' => 'proveedores.php',
    'mantenimientos' => 'mantenimientos.php',
    'usuarios' => 'usuarios.php',
    'licencias' => 'licencias.php',
    'licencia_detalle' => 'licencia.php',
    'software' => 'software.php',
    'consulta_mantenimientos' => 'consultas/mantenimientos.php',
    'consulta_equipos_filtrados' => 'consultas/equipos.php',
    'reporte_mantenimientos' => 'reportes/mantenimientos.php',
    'areas_ajax' => 'app/ajax/maestros/areas.php',
    'cargos_ajax' => 'app/ajax/maestros/cargo.php',
    'marcas_ajax' => 'app/ajax/maestros/marcas.php',
    'modelos_ajax' => 'app/ajax/maestros/modelos.php',
    'equipos_ajax' => 'app/ajax/maestros/equipos.php',
    'empleados_ajax' => 'app/ajax/maestros/empleados.php',
    'asignaciones_ajax' => 'app/ajax/transacciones/asignarequipo.php',
    'proveedores_ajax' => 'app/ajax/maestros/proveedores.php',
    'mantenimientos_ajax' => 'app/ajax/transacciones/mantenimientos.php',
    'usuarios_ajax' => 'app/ajax/maestros/usuarios.php',
    'licencias_ajax' => 'app/ajax/transacciones/licencias.php',
    'software_ajax' => 'app/ajax/maestros/software.php',
    'consulta_mantenimientos_ajax' => 'app/ajax/consultas/mantenimientos.php',
    'reporte_mantenimientos_ajax' => 'app/ajax/reportes/mantenimientos.php',
];

$clave = $argv[1] ?? '';
if (!isset($rutas[$clave])) {
    fwrite(STDERR, 'Objetivo de renderizado no permitido.');
    exit(2);
}

require_once $raiz . '/bootstrap.php';

$sesiones = $raiz . '/tests/.tmp';
if (!is_dir($sesiones) && !mkdir($sesiones, 0775, true) && !is_dir($sesiones)) {
    fwrite(STDERR, 'No se pudo preparar la carpeta temporal.');
    exit(3);
}
session_save_path($sesiones);
session_id('regresion-' . substr(hash('sha256', $clave . microtime(true)), 0, 20));
session_start();
$_SESSION = [
    'idusuario' => '11',
    'usuario' => 'PRUEBAS',
    'nombre' => 'USUARIO PRUEBAS',
    'maestros' => '1',
    'transacciones' => '1',
    'consultas' => '1',
    'reportes' => '1',
    'actas' => '1',
    'seguridad' => '1',
    'mantenimientos' => '1',
    'licencias' => '1',
    'estado' => '0',
];

$esAjax = str_ends_with($clave, '_ajax');
$_SERVER['REQUEST_METHOD'] = $esAjax ? 'POST' : 'GET';
$_SERVER['REQUEST_URI'] = BASE_URL . '/' . $rutas[$clave];
$_POST = [];
$_GET = [];
if (in_array($clave, ['equipos_filtrados', 'consulta_equipos_filtrados'], true)) {
    $_GET = ['estado_equipo' => '4', 'activo' => '1', 'garantia' => 'vencida'];
    $_SERVER['REQUEST_URI'] .= '?estado_equipo=4&activo=1&garantia=vencida';
}
if ($clave === 'licencia_detalle') {
    $licencia = Database::getInstance()->fila('SELECT idlicencia FROM licencias ORDER BY idlicencia LIMIT 1');
    if (!$licencia) {
        fwrite(STDERR, 'No hay una licencia para renderizar la ficha.');
        exit(4);
    }
    $_GET['id'] = (int)$licencia['idlicencia'];
    $_SERVER['REQUEST_URI'] .= '?id=' . (int)$licencia['idlicencia'];
}

set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});

try {
    ob_start();
    include $raiz . '/' . $rutas[$clave];
    $html = ob_get_clean();
    restore_error_handler();
    session_write_close();
    echo json_encode(
        [
            'objetivo' => $clave,
            'bytes' => strlen($html),
            'filtros_aplicados' => in_array($clave, ['equipos_filtrados', 'consulta_equipos_filtrados'], true)
                ? str_contains($html, '<option value="4" selected>')
                    && str_contains($html, '<option value="1" selected>')
                    && str_contains($html, '<option value="vencida" selected>')
                : null,
        ],
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    fwrite(STDERR, get_class($e) . ': ' . $e->getMessage());
    exit(1);
}
