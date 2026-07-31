<?php

final class MantenimientoController {
    public static function pagina(): void {
        Auth::requerirPermiso('mantenimientos');
        Auth::guardarPagina();
        $db = Database::getInstance();
        $service = new MantenimientoService($db);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Auth::verificarCsrf();
            try {
                $resultado = $service->procesar($_POST, (int)Auth::get('idusuario'));
                Auth::registrarBitacora(
                    (int)Auth::get('idusuario'), Auth::get('usuario'),
                    $resultado['accion'], 'mantenimientos', $resultado['detalle']
                );
                Auth::flash('success', $resultado['mensaje']);
            } catch (RuntimeException $e) {
                Auth::flash('error', $e->getMessage());
            } catch (PDOException $e) {
                error_log('GestActivos - Error de mantenimiento: ' . $e->getMessage());
                Auth::flash('error', 'No se pudo completar la operación de mantenimiento.');
            }
            header('Location: ' . BASE_URL . '/mantenimientos.php');
            exit;
        }

        $metricas = $service->metricas();
        $proveedoresFiltro = array_column(
            $db->consulta('SELECT idproveedor AS valor,nombre AS etiqueta FROM proveedores ORDER BY nombre'),
            'etiqueta',
            'valor'
        );
        $preseleccionarEquipo = max(0, (int)($_GET['idequipo'] ?? 0));
        $pageTitle = 'Mantenimientos';
        require BASE_PATH . '/app/views/layouts/encabezado.php';
        require_once BASE_PATH . '/app/views/layouts/table_filters.php';
        Auth::imprimirFlash();
        require BASE_PATH . '/app/views/mantenimientos/pagina.php';
        require BASE_PATH . '/app/views/layouts/footer.php';
    }

    public static function ajax(): void {
        Auth::requerirPermiso('mantenimientos');
        $db = Database::getInstance();
        $service = new MantenimientoService($db);
        $rows = $service->listar(MantenimientoService::leerFiltros());
        $equiposDisponibles = $service->equiposDisponibles();
        $proveedoresActivos = $db->consulta('SELECT * FROM proveedores WHERE activo=1 ORDER BY nombre');
        $proveedoresTodos = $db->consulta('SELECT * FROM proveedores ORDER BY activo DESC,nombre');
        $preseleccionarEquipo = TableFilter::positiveInt('preseleccionar_equipo');
        require BASE_PATH . '/app/views/mantenimientos/listado.php';
    }
}
