<?php

/**
 * Adaptador HTTP para los cuatro catalogos simples.
 */
final class CatalogoController {
    public static function pagina(string $catalogo): void {
        Auth::requerirPermiso('maestros');
        Auth::guardarPagina();

        $db = Database::getInstance();
        $service = new CatalogoService($db);
        $config = CatalogoService::definicion($catalogo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::verificarCsrf();
            try {
                $resultado = $service->procesar($catalogo, $_POST);
                Auth::registrarBitacora(
                    (int)Auth::get('idusuario'),
                    Auth::get('usuario'),
                    $resultado['accion'],
                    $config['auditoria'],
                    $resultado['detalle']
                );
                Auth::flash('success', $resultado['mensaje']);
            } catch (RuntimeException $e) {
                Auth::flash('error', $e->getMessage());
            } catch (PDOException $e) {
                error_log('GestActivos - Error de catálogo: ' . $e->getMessage());
                Auth::flash('error', 'No se pudo completar la operación del catálogo.');
            }
            header('Location: ' . BASE_URL . '/' . $config['ruta']);
            exit;
        }

        $pageTitle = $config['plural'];
        require BASE_PATH . '/app/views/layouts/encabezado.php';
        Auth::imprimirFlash();
        require BASE_PATH . '/app/views/maestros/catalogo_pagina.php';
        require BASE_PATH . '/app/views/layouts/footer.php';
    }

    public static function ajax(string $catalogo): void {
        Auth::requerirPermiso('maestros');
        $service = new CatalogoService(Database::getInstance());
        $config = CatalogoService::definicion($catalogo);
        $busqueda = TableFilter::text('query');
        $rows = $service->listar($catalogo, $busqueda);
        require BASE_PATH . '/app/views/maestros/catalogo_listado.php';
    }
}
