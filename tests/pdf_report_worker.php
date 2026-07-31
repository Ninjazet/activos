<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/bootstrap.php';

$sessionPath = $root . '/tests/.tmp';
if (!is_dir($sessionPath) && !mkdir($sessionPath, 0775, true) && !is_dir($sessionPath)) {
    fwrite(STDERR, 'No se pudo preparar la carpeta temporal.');
    exit(2);
}

session_save_path($sessionPath);
session_id('pdf-mantenimientos-' . bin2hex(random_bytes(6)));
session_start();
$_SESSION = [
    'idusuario' => '1',
    'usuario' => 'PRUEBAS',
    'nombre' => 'USUARIO PRUEBAS',
    'reportes' => '1',
];

$output = $sessionPath . '/reporte_mantenimientos_' . bin2hex(random_bytes(6)) . '.pdf';
define('PDF_TEST_FILE', $output);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];

set_error_handler(static function (int $level, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $level, $file, $line);
});

try {
    include $root . '/reportes/descargar_mantenimientos.php';
    restore_error_handler();
    session_write_close();
    $content = is_file($output) ? file_get_contents($output) : false;
    if (!is_string($content) || !str_starts_with($content, '%PDF-') || strlen($content) < 10000) {
        throw new RuntimeException('El reporte de mantenimientos no generó un PDF válido.');
    }
    echo json_encode(['bytes' => strlen($content)], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    restore_error_handler();
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage());
    exit(1);
} finally {
    if (is_file($output)) {
        unlink($output);
    }
}
