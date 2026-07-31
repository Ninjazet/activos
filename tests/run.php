<?php

declare(strict_types=1);

$raiz = dirname(__DIR__);
require_once __DIR__ . '/TestRunner.php';
require_once $raiz . '/bootstrap.php';

$suite = new TestRunner();

$suite->prueba('Estados de equipos centralizados', static function (): void {
    TestRunner::igual(5, count(EquipoEstado::opciones()));
    TestRunner::igual('Disponible', EquipoEstado::nombre(EquipoEstado::DISPONIBLE));
    TestRunner::igual('warning', EquipoEstado::badge(EquipoEstado::MANTENIMIENTO));
    TestRunner::igual(
        EquipoEstado::MANTENIMIENTO,
        EquipoEstado::desdeCondicionDevolucion('Con daño')
    );
});

$suite->prueba('Validación común de formularios', static function (): void {
    TestRunner::igual('persona@empresa.com', Validacion::correoOpcional(' PERSONA@EMPRESA.COM '));
    TestRunner::igual('SERIE-Á1', Validacion::numeroSerieOpcional(' serie-á1 '));
    TestRunner::igual('2026-07-28', Validacion::fechaOpcional('2026-07-28', 'Fecha'));
    TestRunner::igual(1250.5, Validacion::costoOpcional('1250.50'));
    TestRunner::lanza(RuntimeException::class, static function (): void {
        Validacion::correoOpcional('correo-inválido');
    });
    TestRunner::lanza(RuntimeException::class, static function (): void {
        Validacion::fechaOpcional('2026-02-31', 'Fecha');
    });
    TestRunner::lanza(RuntimeException::class, static function (): void {
        Validacion::costoOpcional('-1');
    });
});

$suite->prueba('Normalización del formulario de equipos', static function (): void {
    $datos = EquipoFormulario::crear([
        'idmarca' => '1',
        'idmodelo' => '2',
        'fecha_compra' => '2026-01-01',
        'vencimiento_garantia' => '2027-01-01',
        'costo' => '100.25',
        'factura' => ' FAC-1 ',
        'numero_serie' => ' sn-001 ',
        'tipo_equipo' => ' Laptop ',
    ]);
    TestRunner::igual(1, $datos['idmarca']);
    TestRunner::igual('SN-001', $datos['numero_serie']);
    TestRunner::igual(EquipoEstado::DISPONIBLE, $datos['estado']);
});

$suite->prueba('Resolución segura de imágenes', static function (): void {
    TestRunner::verdadero(str_ends_with(Imagen::empleado(null), '/avatar1.png'));
    TestRunner::verdadero(str_ends_with(Imagen::equipo('archivo-inexistente.png'), '/equipo.png'));
    TestRunner::verdadero(str_ends_with(Imagen::empleado('avatar2.png'), '/avatar2.png'));
});

$suite->prueba('Configuración mediante variables de entorno', static function () use ($raiz): void {
    $storage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gestactivos_storage_prueba';
    $resultado = TestRunner::proceso(
        [PHP_BINARY, $raiz . '/tests/config_worker.php'],
        [
            'APP_BASE_URL' => '/portal-activos/',
            'APP_TIMEZONE' => 'UTC',
            'APP_STORAGE_PATH' => $storage,
            'DB_HOST' => 'db',
            'DB_PORT' => '3307',
            'DB_NAME' => 'activos_prueba',
        ]
    );
    TestRunner::igual(0, $resultado['codigo'], $resultado['error']);
    $config = json_decode($resultado['salida'], true, 512, JSON_THROW_ON_ERROR);
    TestRunner::igual('/portal-activos', $config['base_url']);
    TestRunner::igual('UTC', $config['timezone']);
    TestRunner::igual($storage, $config['storage']);
    TestRunner::igual('db', $config['db_host']);
    TestRunner::igual(3307, $config['db_port']);
    TestRunner::igual('activos_prueba', $config['db_name']);
});

$db = Database::getInstance();

$suite->prueba('Conexión y esquema principal', static function () use ($db): void {
    TestRunner::igual(1, (int)$db->fila('SELECT 1 valor')['valor']);
    foreach (['equipo', 'empleados', 'asignacion', 'usuarios', 'permisos'] as $tabla) {
        TestRunner::verdadero(
            $db->contar(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',
                [$tabla]
            ) === 1,
            'Falta la tabla ' . $tabla
        );
    }
});

$suite->prueba('Integridad de asignaciones y estados', static function () use ($db): void {
    $revisiones = [
        'SELECT COUNT(*) FROM equipo eq WHERE eq.estado_equipo=2 AND NOT EXISTS
            (SELECT 1 FROM asignacion a WHERE a.idequipo=eq.idequipo AND a.activa=1)',
        'SELECT COUNT(*) FROM asignacion a INNER JOIN equipo eq ON eq.idequipo=a.idequipo
            WHERE a.activa=1 AND eq.estado_equipo<>2',
        'SELECT COUNT(*) FROM asignacion WHERE activa=1 AND fecha_devolucion IS NOT NULL',
        'SELECT COUNT(*) FROM asignacion WHERE activa=0 AND fecha_devolucion IS NULL',
        'SELECT COUNT(*) FROM (SELECT idequipo FROM asignacion WHERE activa=1
            GROUP BY idequipo HAVING COUNT(*)>1) duplicadas',
        'SELECT COUNT(*) FROM empleados e INNER JOIN asignacion a
            ON a.idempleado=e.idempleado AND a.activa=1 WHERE e.activo=0',
    ];
    foreach ($revisiones as $sql) {
        TestRunner::igual(0, $db->contar($sql), $sql);
    }
});

$suite->prueba('Restricciones, charset y textos limpios', static function () use ($db): void {
    $indices = $db->contar(
        "SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA=DATABASE()
           AND INDEX_NAME IN ('uq_permisos_idusuario','uq_usuarios_idempleado','uq_equipo_numero_serie')"
    );
    TestRunner::igual(3, $indices);
    TestRunner::igual(
        0,
        $db->contar(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_COLLATION NOT LIKE 'utf8mb4%'"
        )
    );
    TestRunner::igual(
        0,
        $db->contar(
            "SELECT COUNT(*) FROM (
                SELECT descripcionarea valor FROM areas
                UNION ALL SELECT descripcioncargo FROM cargos
                UNION ALL SELECT tipo_equipo FROM equipo
             ) datos WHERE HEX(valor) LIKE '%E2949C%'"
        )
    );
});

$suite->prueba('Imágenes y firmas referenciadas existen', static function () use ($db, $raiz): void {
    foreach (['equipo' => IMG_EQUIPOS, 'empleados' => IMG_EMPLEADOS] as $tabla => $directorio) {
        foreach ($db->consulta("SELECT imagen FROM $tabla WHERE imagen IS NOT NULL AND imagen<>''") as $fila) {
            TestRunner::verdadero(
                is_file($directorio . basename($fila['imagen'])),
                'Falta ' . $fila['imagen']
            );
        }
    }
    foreach ($db->consulta('SELECT firma, firma_devolucion FROM asignacion') as $fila) {
        foreach (['firma', 'firma_devolucion'] as $campo) {
            if (!empty($fila[$campo])) {
                TestRunner::verdadero(is_file($raiz . '/' . $fila[$campo]), 'Falta ' . $fila[$campo]);
            }
        }
    }
});

$suite->prueba('Servicio común de catálogos consulta datos', static function () use ($db): void {
    $servicio = new CatalogoService($db);
    foreach (['areas', 'cargos', 'marcas', 'modelos'] as $catalogo) {
        $config = CatalogoService::definicion($catalogo);
        TestRunner::verdadero($config['ruta'] !== '');
        TestRunner::verdadero(count($servicio->listar($catalogo)) > 0);
    }
});

$suite->prueba('Endpoints AJAX conservan control de permisos', static function () use ($raiz): void {
    $excluir = ['parcial_checklist_entrega.php'];
    $iterador = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($raiz . '/app/ajax', FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterador as $archivo) {
        if ($archivo->getExtension() !== 'php' || in_array($archivo->getFilename(), $excluir, true)) {
            continue;
        }
        $codigo = file_get_contents($archivo->getPathname());
        $protegido = str_contains($codigo, 'Auth::requerir')
            || str_contains($codigo, 'CatalogoController::ajax')
            || (
                $archivo->getFilename() === 'catalogos_contextuales.php'
                && str_contains($codigo, "['maestros']")
            );
        TestRunner::verdadero($protegido, 'Sin permiso explícito: ' . $archivo->getPathname());
    }
});

foreach ([
    'index', 'equipos', 'empleados', 'asignaciones',
    'areas_ajax', 'cargos_ajax', 'marcas_ajax', 'modelos_ajax',
    'equipos_ajax', 'empleados_ajax', 'asignaciones_ajax',
] as $objetivo) {
    $suite->prueba('Renderizado: ' . $objetivo, static function () use ($raiz, $objetivo): void {
        $resultado = TestRunner::proceso([
            PHP_BINARY,
            '-d',
            'display_errors=1',
            '-d',
            'error_reporting=32767',
            $raiz . '/tests/render_worker.php',
            $objetivo,
        ]);
        TestRunner::igual(0, $resultado['codigo'], $resultado['error']);
        $datos = json_decode($resultado['salida'], true, 512, JSON_THROW_ON_ERROR);
        TestRunner::verdadero($datos['bytes'] > 500, 'La salida fue demasiado pequeña.');
    });
}

$suite->prueba('Generación de PDF sin avisos PHP', static function () use ($raiz): void {
    require_once $raiz . '/reportes/pdf_layout.php';
    set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
        throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
    });
    try {
        $pdf = new GestActivosPDF('L', 'mm', 'Letter', true, 'UTF-8', false);
        $columnas = [['label' => 'Prueba', 'width' => 50, 'align' => 'L']];
        $pdf->configureReport('REGRESIÓN', 'TEST', '1 registro', $columnas, 14);
        $pdf->SetMargins(14, 60, 14);
        $pdf->AddPage();
        $pdf->tableRow(['Dato'], 0);
        $contenido = $pdf->Output('regresion.pdf', 'S');
    } finally {
        restore_error_handler();
    }
    TestRunner::verdadero(str_starts_with($contenido, '%PDF-'));
    TestRunner::verdadero(strlen($contenido) > 10000);
});

$suite->prueba('Sintaxis PHP de todo el proyecto', static function () use ($raiz): void {
    $iterador = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterador as $archivo) {
        $ruta = $archivo->getPathname();
        if (
            $archivo->getExtension() !== 'php'
            || str_contains($ruta, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)
            || str_contains($ruta, DIRECTORY_SEPARATOR . 'backups_local' . DIRECTORY_SEPARATOR)
        ) {
            continue;
        }
        $resultado = TestRunner::proceso([PHP_BINARY, '-l', $ruta]);
        TestRunner::igual(0, $resultado['codigo'], $resultado['salida'] . $resultado['error']);
    }
});

exit($suite->finalizar());
