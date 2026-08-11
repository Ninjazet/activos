<?php

final class LicenciaController {
    public static function pagina(): void {
        Auth::requerirPermiso('licencias');
        Auth::guardarPagina();
        $db = Database::getInstance();
        $service = new LicenciaService($db);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            self::procesarPost($service, 'licencias', '/licencias.php');
        }

        $software = (new SoftwareService($db))->opciones(true);
        $proveedores = $service->proveedores();
        $metricasResumen = $service->metricas();
        $pageTitle = 'Licencias';
        require BASE_PATH . '/app/views/layouts/encabezado.php';
        require_once BASE_PATH . '/app/views/layouts/table_filters.php';
        Auth::imprimirFlash();
        require BASE_PATH . '/app/views/licencias/pagina.php';
        require BASE_PATH . '/app/views/layouts/footer.php';
    }

    public static function ajax(): void {
        Auth::requerirPermiso('licencias');
        $modalidad = TableFilter::enum('modalidad', array_keys(LicenciaEstado::modalidades()));
        $metrica = TableFilter::enum('metrica', array_keys(LicenciaEstado::metricas()));
        $estado = TableFilter::enum('estado', ['activa', 'inactiva', 'vigente', 'proxima', 'vencida']);
        $db = Database::getInstance();
        $service = new LicenciaService($db);
        $rows = $service->listar([
            'busqueda' => TableFilter::text('query'),
            'idsoftware' => TableFilter::positiveInt('idsoftware'),
            'idproveedor' => TableFilter::positiveInt('idproveedor'),
            'modalidad' => $modalidad,
            'metrica' => $metrica,
            'estado' => $estado,
        ]);
        $software = (new SoftwareService($db))->opciones(true);
        $proveedores = $service->proveedores();
        require BASE_PATH . '/app/views/licencias/listado.php';
    }

    public static function detalle(): void {
        Auth::requerirPermiso('licencias');
        Auth::guardarPagina();
        $db = Database::getInstance();
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $id = $id !== false ? (int)$id : 0;
        if ($id <= 0) {
            http_response_code(404);
            die('Licencia no encontrada.');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Auth::verificarCsrf();
            try {
                $datos = $_POST;
                $datos['idlicencia_contexto'] = $id;
                $resultado = (new LicenciaAsignacionService($db))->procesar(
                    $datos,
                    (int)Auth::get('idusuario')
                );
                Auth::registrarBitacora(
                    (int)Auth::get('idusuario'),
                    Auth::get('usuario'),
                    $resultado['accion'],
                    'licencia_asignaciones',
                    $resultado['detalle']
                );
                Auth::flash('success', $resultado['mensaje']);
            } catch (PDOException $e) {
                error_log('GestActivos - Error en asignacion de licencia: ' . $e->getMessage());
                Auth::flash('error', 'No se pudo completar la operacion. Intenta nuevamente.');
            } catch (RuntimeException $e) {
                Auth::flash('error', $e->getMessage());
            }
            header('Location: ' . BASE_URL . '/licencia.php?id=' . $id);
            exit;
        }

        $licencia = (new LicenciaService($db))->obtener($id);
        if (!$licencia) {
            http_response_code(404);
            die('Licencia no encontrada.');
        }
        $asignacionService = new LicenciaAsignacionService($db);
        $asignaciones = $asignacionService->listarAsignaciones($id);
        $cupos = $asignacionService->listarCupos($id);
        $destinosPermitidos = LicenciaEstado::destinosPermitidos((string)$licencia['metrica']);
        $empleadosDisponibles = in_array('empleado', $destinosPermitidos, true)
            ? $asignacionService->empleadosDisponibles($id)
            : [];
        $equiposDisponibles = in_array('equipo', $destinosPermitidos, true)
            ? $asignacionService->equiposDisponibles($id)
            : [];
        $pageTitle = 'Ficha de Licencia';
        require BASE_PATH . '/app/views/layouts/encabezado.php';
        Auth::imprimirFlash();
        require BASE_PATH . '/app/views/licencias/detalle.php';
        require BASE_PATH . '/app/views/layouts/footer.php';
    }

    public static function softwarePagina(): void {
        Auth::requerirPermiso('licencias');
        Auth::guardarPagina();
        $service = new SoftwareService(Database::getInstance());

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            self::procesarPost($service, 'software', '/software.php');
        }

        $categorias = $service->categorias();
        $pageTitle = 'Catálogo de Software';
        require BASE_PATH . '/app/views/layouts/encabezado.php';
        require_once BASE_PATH . '/app/views/layouts/table_filters.php';
        Auth::imprimirFlash();
        require BASE_PATH . '/app/views/licencias/software_pagina.php';
        require BASE_PATH . '/app/views/layouts/footer.php';
    }

    public static function softwareAjax(): void {
        Auth::requerirPermiso('licencias');
        $activoTexto = TableFilter::enum('activo', ['0', '1']);
        $service = new SoftwareService(Database::getInstance());
        $rows = $service->listar(
            TableFilter::text('query'),
            $activoTexto === '' ? null : (int)$activoTexto,
            TableFilter::text('categoria', 80)
        );
        $categorias = $service->categorias();
        require BASE_PATH . '/app/views/licencias/software_listado.php';
    }

    private static function procesarPost(object $service, string $tabla, string $redireccion): void {
        Auth::verificarCsrf();
        try {
            $resultado = $service->procesar($_POST);
            Auth::registrarBitacora(
                (int)Auth::get('idusuario'),
                Auth::get('usuario'),
                $resultado['accion'],
                $tabla,
                $resultado['detalle']
            );
            Auth::flash('success', $resultado['mensaje']);
        } catch (PDOException $e) {
            error_log('GestActivos - Error en ' . $tabla . ': ' . $e->getMessage());
            Auth::flash('error', 'No se pudo completar la operación. Intenta nuevamente.');
        } catch (RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
        }
        header('Location: ' . BASE_URL . $redireccion);
        exit;
    }
}
