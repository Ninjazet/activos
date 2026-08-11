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
    $avatar = Imagen::empleado('avatar2.png');
    TestRunner::verdadero(str_contains($avatar, '/media.php?'));
    TestRunner::verdadero(str_contains($avatar, 'tipo=empleado'));
    TestRunner::verdadero(str_contains($avatar, 'archivo=avatar2.png'));
});

$suite->prueba('Imágenes desde almacenamiento externo', static function () use ($raiz): void {
    $directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gestactivos_media_' . bin2hex(random_bytes(5));
    $empleados = $directorio . DIRECTORY_SEPARATOR . 'empleados';
    if (!mkdir($empleados, 0775, true) && !is_dir($empleados)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento temporal.');
    }
    $origen = $raiz . '/public/img/empleados/avatar2.png';
    $destino = $empleados . DIRECTORY_SEPARATOR . 'avatar-prueba.png';
    if (!copy($origen, $destino)) {
        throw new RuntimeException('No se pudo preparar la imagen temporal.');
    }

    try {
        $resultado = TestRunner::proceso(
            [PHP_BINARY, $raiz . '/tests/media_worker.php', 'empleado', 'avatar-prueba.png'],
            ['APP_STORAGE_PATH' => $directorio]
        );
        TestRunner::igual(0, $resultado['codigo'], $resultado['error']);
        TestRunner::verdadero(str_starts_with($resultado['salida'], "\x89PNG\r\n\x1a\n"));
    } finally {
        if (is_file($destino)) {
            unlink($destino);
        }
        if (is_dir($empleados)) {
            rmdir($empleados);
        }
        if (is_dir($directorio)) {
            rmdir($directorio);
        }
    }
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
    foreach ([
        'equipo', 'empleados', 'asignacion', 'usuarios', 'permisos', 'proveedores', 'mantenimientos',
        'software', 'licencias', 'licencia_cupos', 'licencia_asignaciones',
        'licencia_instalaciones', 'licencia_renovaciones',
    ] as $tabla) {
        TestRunner::verdadero(
            $db->contar(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',
                [$tabla]
            ) === 1,
            'Falta la tabla ' . $tabla
        );
    }
});

$suite->prueba('Estados de mantenimiento centralizados', static function (): void {
    TestRunner::igual(4, count(MantenimientoEstado::estados()));
    TestRunner::igual('Preventivo', MantenimientoEstado::tipos()[MantenimientoEstado::PREVENTIVO]);
    TestRunner::igual('success', MantenimientoEstado::badge(MantenimientoEstado::COMPLETADO));
    TestRunner::verdadero(in_array(MantenimientoEstado::ABIERTO, MantenimientoEstado::estadosActivos(), true));
});

$suite->prueba('Modalidades y estados de licencias centralizados', static function (): void {
    $hoy = new DateTimeImmutable('2026-07-31');
    TestRunner::igual(3, count(LicenciaEstado::modalidades()));
    TestRunner::igual(5, count(LicenciaEstado::metricas()));
    TestRunner::igual(['empleado'], LicenciaEstado::destinosPermitidos(LicenciaEstado::POR_USUARIO));
    TestRunner::igual(['equipo'], LicenciaEstado::destinosPermitidos(LicenciaEstado::POR_DISPOSITIVO));
    TestRunner::igual(
        LicenciaEstado::VENCIDA,
        LicenciaEstado::estado(['activo' => 1, 'modalidad' => 'Suscripción', 'fecha_vencimiento' => '2026-07-30'], 0, $hoy)
    );
    TestRunner::igual(
        LicenciaEstado::PROXIMA_VENCER,
        LicenciaEstado::estado(['activo' => 1, 'modalidad' => 'Suscripción', 'fecha_vencimiento' => '2026-08-15'], 0, $hoy)
    );
    TestRunner::igual(
        LicenciaEstado::AGOTADA,
        LicenciaEstado::estado(['activo' => 1, 'modalidad' => 'Suscripción', 'fecha_vencimiento' => '2027-07-31', 'cantidad_total' => 10], 10, $hoy)
    );
    TestRunner::igual(
        LicenciaEstado::PERPETUA_VIGENTE,
        LicenciaEstado::estado(['activo' => 1, 'modalidad' => 'Perpetua', 'fecha_vencimiento' => null], 0, $hoy)
    );
});

$suite->prueba('Cifrado autenticado de claves de licencia', static function () use ($raiz): void {
    $resultado = TestRunner::proceso(
        [PHP_BINARY, $raiz . '/tests/licencia_crypto_worker.php'],
        ['APP_ENCRYPTION_KEY' => base64_encode(str_repeat(chr(73), 32))]
    );
    TestRunner::igual(0, $resultado['codigo'], $resultado['error']);
    $datos = json_decode($resultado['salida'], true, 512, JSON_THROW_ON_ERROR);
    TestRunner::igual('ABCD-EFGH-IJKL-9876', $datos['descifrado']);
    TestRunner::igual('••••-••••-9876', $datos['mascara']);
    TestRunner::verdadero($datos['huella_igual']);
    TestRunner::verdadero($datos['rechazo_alteracion']);
    TestRunner::verdadero(!str_contains($datos['cifrado'], 'ABCD-EFGH'));
});

$suite->prueba('Esquema de proveedores y mantenimientos', static function () use ($db): void {
    TestRunner::igual(
        1,
        $db->contar(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='equipo' AND COLUMN_NAME='idproveedor'"
        )
    );
    TestRunner::igual(
        1,
        $db->contar(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='permisos' AND COLUMN_NAME='mantenimientos'"
        )
    );
    TestRunner::igual(
        5,
        $db->contar(
            "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='mantenimientos'"
        )
    );
    TestRunner::igual(
        1,
        $db->contar(
            "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='equipo'
               AND CONSTRAINT_NAME='fk_equipo_proveedor'"
        )
    );
});

$suite->prueba('Esquema base de licencias', static function () use ($db): void {
    TestRunner::igual(
        1,
        $db->contar(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='permisos' AND COLUMN_NAME='licencias'"
        )
    );
    TestRunner::igual(
        17,
        $db->contar(
            "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'licencia%'"
        )
    );
    TestRunner::igual(
        2,
        $db->contar(
            "SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA=DATABASE()
               AND INDEX_NAME IN ('uq_licencia_cupo_asignado_activo','uq_licencia_instalacion_activa')"
        )
    );
});

$suite->prueba('Esquema de cupos y asignaciones de licencias', static function () use ($db): void {
    foreach (['motivo_devolucion', 'empleado_asignado_activo', 'equipo_asignado_activo'] as $columna) {
        TestRunner::igual(
            1,
            $db->contar(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='licencia_asignaciones' AND COLUMN_NAME=?",
                [$columna]
            ),
            'Falta la columna ' . $columna
        );
    }
    TestRunner::igual(
        2,
        $db->contar(
            "SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='licencia_asignaciones'
               AND INDEX_NAME IN ('uq_licencia_empleado_activo','uq_licencia_equipo_activo')"
        )
    );
});

$suite->prueba('Integridad de mantenimientos y equipos', static function () use ($db): void {
    $revisiones = [
        "SELECT COUNT(*) FROM equipo eq WHERE eq.activo=1 AND eq.estado_equipo=3
         AND (SELECT COUNT(*) FROM mantenimientos m WHERE m.idequipo=eq.idequipo AND m.estado IN ('Abierto','En proceso'))<>1",
        "SELECT COUNT(*) FROM mantenimientos m INNER JOIN equipo eq ON eq.idequipo=m.idequipo
         WHERE m.estado IN ('Abierto','En proceso') AND (eq.activo<>1 OR eq.estado_equipo<>3)",
        "SELECT COUNT(*) FROM mantenimientos m INNER JOIN asignacion a ON a.idequipo=m.idequipo
         WHERE m.estado IN ('Abierto','En proceso') AND a.activa=1",
        "SELECT COUNT(*) FROM mantenimientos WHERE estado IN ('Abierto','En proceso') AND fecha_cierre IS NOT NULL",
        "SELECT COUNT(*) FROM mantenimientos WHERE estado IN ('Completado','Cancelado') AND fecha_cierre IS NULL",
        "SELECT COUNT(*) FROM (SELECT idequipo FROM mantenimientos WHERE estado IN ('Abierto','En proceso')
         GROUP BY idequipo HAVING COUNT(*)>1) duplicados",
    ];
    foreach ($revisiones as $sql) {
        TestRunner::igual(0, $db->contar($sql), $sql);
    }
});

$suite->prueba('Integridad de historiales de licencias', static function () use ($db): void {
    $revisiones = [
        "SELECT COUNT(*) FROM licencia_asignaciones
         WHERE ((idempleado IS NOT NULL) + (idequipo IS NOT NULL)) <> 1",
        "SELECT COUNT(*) FROM licencia_asignaciones
         WHERE (activa=1 AND fecha_devolucion IS NOT NULL)
            OR (activa=0 AND fecha_devolucion IS NULL)",
        "SELECT COUNT(*) FROM licencia_instalaciones
         WHERE (activa=1 AND fecha_desinstalacion IS NOT NULL)
            OR (activa=0 AND fecha_desinstalacion IS NULL)",
        "SELECT COUNT(*) FROM (
             SELECT lc.idlicencia
             FROM licencia_cupos lc
             INNER JOIN licencias l ON l.idlicencia=lc.idlicencia
             WHERE lc.activo=1 AND l.cantidad_total IS NOT NULL
             GROUP BY lc.idlicencia, l.cantidad_total
             HAVING COUNT(*) > l.cantidad_total
         ) excesos",
        "SELECT COUNT(*) FROM (
             SELECT idcupo
             FROM licencia_asignaciones
             WHERE activa=1 AND idcupo IS NOT NULL
             GROUP BY idcupo
             HAVING COUNT(*) > 1
         ) duplicados",
        "SELECT COUNT(*) FROM licencia_asignaciones la
         INNER JOIN licencias l ON l.idlicencia=la.idlicencia
         LEFT JOIN licencia_cupos lc ON lc.idcupo=la.idcupo
         WHERE la.activa=1 AND l.cantidad_total IS NOT NULL
           AND (la.idcupo IS NULL OR lc.activo<>1)",
        "SELECT COUNT(*) FROM licencia_asignaciones la
         INNER JOIN empleados e ON e.idempleado=la.idempleado
         WHERE la.activa=1 AND e.activo<>1",
        "SELECT COUNT(*) FROM licencia_asignaciones la
         INNER JOIN equipo eq ON eq.idequipo=la.idequipo
         WHERE la.activa=1 AND eq.activo<>1",
    ];
    foreach ($revisiones as $sql) {
        TestRunner::igual(0, $db->contar($sql), $sql);
    }
});

$suite->prueba('Servicios de proveedores y mantenimientos consultan datos', static function () use ($db): void {
    TestRunner::verdadero(is_array((new ProveedorService($db))->listar()));
    $mantenimientos = new MantenimientoService($db);
    TestRunner::verdadero(is_array($mantenimientos->listar()));
    $metricas = $mantenimientos->metricas();
    foreach (['abiertos', 'en_proceso', 'cerrados_mes', 'costo_mes'] as $campo) {
        TestRunner::verdadero(array_key_exists($campo, $metricas), 'Falta la mÃ©trica ' . $campo);
    }
});

$suite->prueba('Servicios de software y licencias consultan datos', static function () use ($db): void {
    $software = new SoftwareService($db);
    TestRunner::verdadero(is_array($software->listar()));
    TestRunner::verdadero(is_array($software->opciones()));
    $licencias = new LicenciaService($db);
    TestRunner::verdadero(is_array($licencias->listar()));
    $metricas = $licencias->metricas();
    foreach (['total', 'activas', 'vencidas', 'proximas'] as $campo) {
        TestRunner::verdadero(array_key_exists($campo, $metricas), 'Falta la métrica ' . $campo);
    }
});

$suite->prueba('Resumen ampliado del inicio es coherente', static function () use ($db): void {
    $empleados = $db->fila(
        "SELECT COALESCE(SUM(e.activo=1),0) activos,
                COALESCE(SUM(e.activo=1 AND COALESCE(a.equipos,0)>0),0) con_equipo,
                COALESCE(SUM(e.activo=1 AND COALESCE(a.equipos,0)=0),0) sin_equipo,
                COALESCE(SUM(e.activo=1 AND COALESCE(a.equipos,0)>1),0) varios_equipos
         FROM empleados e
         LEFT JOIN (
           SELECT idempleado,COUNT(*) equipos
           FROM asignacion WHERE activa=1 GROUP BY idempleado
         ) a ON a.idempleado=e.idempleado"
    );
    TestRunner::igual(
        (int)$empleados['activos'],
        (int)$empleados['con_equipo'] + (int)$empleados['sin_equipo']
    );
    TestRunner::verdadero((int)$empleados['varios_equipos'] <= (int)$empleados['con_equipo']);
    TestRunner::igual(
        $db->contar('SELECT COUNT(DISTINCT idempleado) FROM asignacion WHERE activa=1'),
        (int)$empleados['con_equipo']
    );
    TestRunner::igual(
        0,
        $db->contar('SELECT COUNT(*) FROM equipo WHERE estado_equipo NOT IN (1,2,3,4,5)')
    );
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

$suite->prueba('Imágenes y firmas referenciadas existen', static function () use ($db): void {
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
                TestRunner::verdadero(is_file(Imagen::firmaRuta($fila[$campo])), 'Falta ' . $fila[$campo]);
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
    'proveedores', 'mantenimientos', 'usuarios', 'licencias', 'licencia_detalle', 'software',
    'consulta_mantenimientos', 'reporte_mantenimientos',
    'areas_ajax', 'cargos_ajax', 'marcas_ajax', 'modelos_ajax',
    'equipos_ajax', 'empleados_ajax', 'asignaciones_ajax',
    'proveedores_ajax', 'mantenimientos_ajax', 'usuarios_ajax', 'licencias_ajax', 'software_ajax',
    'consulta_mantenimientos_ajax', 'reporte_mantenimientos_ajax',
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

$suite->prueba('Alertas abren equipos con filtros aplicados', static function () use ($raiz): void {
    $inicio = file_get_contents($raiz . '/index.php');
    TestRunner::verdadero(str_contains($inicio, "['garantia' => 'vencida', 'activo' => '1']"));
    TestRunner::verdadero(str_contains($inicio, "['garantia' => 'vence_30', 'activo' => '1']"));
    TestRunner::verdadero(str_contains($inicio, "['estado_equipo' => (string)EquipoEstado::PERDIDO_ROBADO, 'activo' => '1']"));

    foreach (['equipos_filtrados', 'consulta_equipos_filtrados'] as $objetivo) {
        $resultado = TestRunner::proceso([PHP_BINARY, $raiz . '/tests/render_worker.php', $objetivo]);
        TestRunner::igual(0, $resultado['codigo'], $resultado['error']);
        $datos = json_decode($resultado['salida'], true, 512, JSON_THROW_ON_ERROR);
        TestRunner::verdadero($datos['filtros_aplicados'] === true, 'No se seleccionaron los filtros en ' . $objetivo);
    }
});

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

$suite->prueba('Reporte PDF de mantenimientos', static function () use ($raiz): void {
    $resultado = TestRunner::proceso([
        PHP_BINARY,
        '-d',
        'display_errors=1',
        '-d',
        'error_reporting=32767',
        $raiz . '/tests/pdf_report_worker.php',
    ]);
    TestRunner::igual(0, $resultado['codigo'], $resultado['error']);
    $datos = json_decode($resultado['salida'], true, 512, JSON_THROW_ON_ERROR);
    TestRunner::verdadero($datos['bytes'] > 10000, 'El PDF de mantenimientos fue demasiado pequeño.');
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
