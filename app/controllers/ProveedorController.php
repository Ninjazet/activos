<?php

final class ProveedorController {
    public static function pagina(): void {
        Auth::requerirPermiso('maestros');
        Auth::guardarPagina();
        $db = Database::getInstance();
        $service = new ProveedorService($db);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Auth::verificarCsrf();
            try {
                $resultado = $service->procesar($_POST);
                Auth::registrarBitacora(
                    (int)Auth::get('idusuario'), Auth::get('usuario'),
                    $resultado['accion'], 'proveedores', $resultado['detalle']
                );
                Auth::flash('success', $resultado['mensaje']);
            } catch (RuntimeException $e) {
                Auth::flash('error', $e->getMessage());
            } catch (PDOException $e) {
                error_log('GestActivos - Error de proveedor: ' . $e->getMessage());
                Auth::flash('error', 'No se pudo completar la operación del proveedor.');
            }
            header('Location: ' . BASE_URL . '/proveedores.php');
            exit;
        }

        $pageTitle = 'Proveedores';
        require BASE_PATH . '/app/views/layouts/encabezado.php';
        require_once BASE_PATH . '/app/views/layouts/table_filters.php';
        Auth::imprimirFlash();
        require BASE_PATH . '/app/views/maestros/proveedores/pagina.php';
        require BASE_PATH . '/app/views/layouts/footer.php';
    }

    public static function ajax(): void {
        Auth::requerirPermiso('maestros');
        $activoTexto = TableFilter::enum('activo', ['0', '1']);
        $activo = $activoTexto === '' ? null : (int)$activoTexto;
        $rows = (new ProveedorService(Database::getInstance()))->listar(
            TableFilter::text('query'),
            $activo
        );
        require BASE_PATH . '/app/views/maestros/proveedores/listado.php';
    }

    public static function detalle(): void {
        Auth::requerirPermiso('maestros');
        Auth::guardarPagina();
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || (int)$id <= 0) {
            http_response_code(404);
            die('Proveedor no encontrado.');
        }
        $service = new ProveedorService(Database::getInstance());
        $proveedor = $service->obtener((int)$id);
        if (!$proveedor) {
            http_response_code(404);
            die('Proveedor no encontrado.');
        }
        $equipos = $service->equipos((int)$id);
        $pageTitle = 'Ficha del Proveedor';
        require BASE_PATH . '/app/views/layouts/encabezado.php';
        require BASE_PATH . '/app/views/maestros/proveedores/detalle.php';
        require BASE_PATH . '/app/views/layouts/footer.php';
    }
}
