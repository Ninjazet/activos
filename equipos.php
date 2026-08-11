<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('maestros');
Auth::guardarPagina();

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    // ---- INSERTAR ----
    if (isset($_POST['add'])) {
        try {
            $datos = EquipoFormulario::crear($_POST);
            if ($datos['numero_serie'] !== null && $db->fila(
                'SELECT idequipo FROM equipo WHERE numero_serie=?',
                [$datos['numero_serie']]
            )) {
                throw new RuntimeException('Ya existe un equipo registrado con ese número de serie.');
            }

            if ($datos['idproveedor'] !== null && !$db->fila(
                'SELECT idproveedor FROM proveedores WHERE idproveedor=? AND activo=1',
                [$datos['idproveedor']]
            )) {
                throw new RuntimeException('El proveedor seleccionado no está activo o no existe.');
            }

            $imagen = '';
            if (!Upload::estaVacio($_FILES['archivo'] ?? null)) {
                $archivoGuardado = Upload::guardarImagen($_FILES['archivo'], IMG_EQUIPOS, 'equipo');
                $imagen = 'public/img/equipos/' . $archivoGuardado;
            }

            $idEquipo = $db->transaccion(function (Database $db) use ($datos, $imagen) {
                $id = $db->ejecutar(
                    "INSERT INTO equipo
                        (idmarca_equipo, idmodelo_equipo, idproveedor, imagen, activo, fecha_compra, costo, factura,
                         vencimiento_garantia, estado_equipo, numero_serie, codigo_activo, tipo_equipo)
                     VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, NULL, ?)",
                    [
                        $datos['idmarca'], $datos['idmodelo'], $datos['idproveedor'], $imagen, $datos['fecha_compra'],
                        $datos['costo'], $datos['factura'], $datos['garantia'], EquipoEstado::DISPONIBLE,
                        $datos['numero_serie'], $datos['tipo_equipo'],
                    ]
                );
                $codigo = 'EQ-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
                $db->ejecutar("UPDATE equipo SET codigo_activo=? WHERE idequipo=?", [$codigo, $id]);
                return $id;
            });
            Auth::registrarBitacora(
                (int)Auth::get('idusuario'),
                Auth::get('usuario'),
                'crear',
                'equipos',
                "#$idEquipo marca={$datos['idmarca']} modelo={$datos['idmodelo']}"
            );
            Auth::flash('success', 'Equipo creado correctamente.');
        } catch (\RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
        } catch (PDOException $e) {
            Auth::flash('error', 'No se pudo crear: verifica la marca, el modelo y que el número de serie no esté repetido.');
        }
    }

    // ---- EDITAR ----
    if (isset($_POST['edit'])) {
        try {
            $datos = EquipoFormulario::editar($_POST);
            if ($datos['numero_serie'] !== null && $db->fila(
                'SELECT idequipo FROM equipo WHERE numero_serie=? AND idequipo<>?',
                [$datos['numero_serie'], $datos['id']]
            )) {
                throw new RuntimeException('Ya existe otro equipo registrado con ese número de serie.');
            }
            $equipoActual = $db->fila('SELECT idproveedor FROM equipo WHERE idequipo=?', [$datos['id']]);
            if (!$equipoActual) {
                throw new RuntimeException('El equipo indicado no existe.');
            }
            if ($datos['idproveedor'] !== null) {
                $proveedor = $db->fila('SELECT activo FROM proveedores WHERE idproveedor=?', [$datos['idproveedor']]);
                $conservaProveedor = (int)($equipoActual['idproveedor'] ?? 0) === $datos['idproveedor'];
                if (!$proveedor || ((int)$proveedor['activo'] !== 1 && !$conservaProveedor)) {
                    throw new RuntimeException('El proveedor seleccionado no está disponible.');
                }
            }
            if ($db->fila(
                'SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1',
                [$datos['id']]
            )) {
                $datos['estado'] = EquipoEstado::ASIGNADO;
            } elseif ($db->fila(
                "SELECT idmantenimiento FROM mantenimientos
                 WHERE idequipo=? AND estado IN ('Abierto','En proceso')",
                [$datos['id']]
            )) {
                $datos['estado'] = EquipoEstado::MANTENIMIENTO;
            } elseif (in_array($datos['estado'], [EquipoEstado::ASIGNADO, EquipoEstado::MANTENIMIENTO], true)) {
                $datos['estado'] = EquipoEstado::DISPONIBLE;
            }
            if ($datos['estado'] === EquipoEstado::BAJA && $db->fila(
                'SELECT idasignacion_licencia FROM licencia_asignaciones
                 WHERE idequipo=? AND activa=1 LIMIT 1',
                [$datos['id']]
            )) {
                throw new RuntimeException('No se puede dar de baja: devuelve primero las licencias de software asignadas al equipo.');
            }

            if (!Upload::estaVacio($_FILES['archivoAct'] ?? null)) {
                $archivoGuardado = Upload::guardarImagen($_FILES['archivoAct'], IMG_EQUIPOS, 'equipo');
                $db->ejecutar(
                    "UPDATE equipo SET idmarca_equipo=?, idmodelo_equipo=?, idproveedor=?, imagen=?, fecha_compra=?, costo=?, factura=?, vencimiento_garantia=?, numero_serie=?, tipo_equipo=?, estado_equipo=?, activo=IF(?=5,0,activo) WHERE idequipo=?",
                    [
                        $datos['idmarca'], $datos['idmodelo'], $datos['idproveedor'], 'public/img/equipos/' . $archivoGuardado,
                        $datos['fecha_compra'], $datos['costo'], $datos['factura'], $datos['garantia'],
                        $datos['numero_serie'], $datos['tipo_equipo'], $datos['estado'],
                        $datos['estado'], $datos['id'],
                    ]
                );
            } else {
                $db->ejecutar(
                    "UPDATE equipo SET idmarca_equipo=?, idmodelo_equipo=?, idproveedor=?, fecha_compra=?, costo=?, factura=?, vencimiento_garantia=?, numero_serie=?, tipo_equipo=?, estado_equipo=?, activo=IF(?=5,0,activo) WHERE idequipo=?",
                    [
                        $datos['idmarca'], $datos['idmodelo'], $datos['idproveedor'], $datos['fecha_compra'], $datos['costo'],
                        $datos['factura'], $datos['garantia'], $datos['numero_serie'],
                        $datos['tipo_equipo'], $datos['estado'], $datos['estado'], $datos['id'],
                    ]
                );
            }
            Auth::registrarBitacora(
                (int)Auth::get('idusuario'),
                Auth::get('usuario'),
                'editar',
                'equipos',
                '#' . $datos['id']
            );
            Auth::flash('success', 'Equipo actualizado correctamente.');
        } catch (\RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
        } catch (PDOException $e) {
            Auth::flash('error', 'No se pudo actualizar: verifica la marca, el modelo y que el número de serie no esté repetido.');
        }
    }

    // ---- ELIMINAR / REACTIVAR (baja lógica reversible) ----
    if (isset($_POST['del'])) {
        $idEquipoDel = (int)($_POST['idEquipoDel'] ?? 0);
        $filaActual  = $db->fila("SELECT activo FROM equipo WHERE idequipo=?", [$idEquipoDel]);

        if (!$filaActual) {
            Auth::flash('error', 'El equipo indicado no existe.');
        } elseif ((int)$filaActual['activo'] === 1) {
            $tieneAsignacion = $db->fila("SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1", [$idEquipoDel]);
            $tieneMantenimiento = $db->fila(
                'SELECT idmantenimiento FROM mantenimientos WHERE idequipo=? AND estado IN (?,?)',
                [$idEquipoDel, MantenimientoEstado::ABIERTO, MantenimientoEstado::EN_PROCESO]
            );
            $tieneLicencia = $db->fila(
                'SELECT idasignacion_licencia FROM licencia_asignaciones
                 WHERE idequipo=? AND activa=1 LIMIT 1',
                [$idEquipoDel]
            );
            if ($tieneAsignacion) {
                Auth::flash('error', 'No se puede dar de baja: este equipo tiene una asignación activa. Quita primero la asignación.');
            } elseif ($tieneLicencia) {
                Auth::flash('error', 'No se puede dar de baja: devuelve primero las licencias de software asignadas al equipo.');
            } elseif ($tieneMantenimiento) {
                Auth::flash('error', 'No se puede dar de baja directamente: cierra el mantenimiento con resultado No reparable.');
            } else {
                $db->ejecutar(
                    'UPDATE equipo SET activo=0, estado_equipo=? WHERE idequipo=?',
                    [EquipoEstado::BAJA, $idEquipoDel]
                );
                Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'eliminar', 'equipos', "#$idEquipoDel");
                Auth::flash('success', 'Equipo dado de baja correctamente.');
            }
        } else {
            $db->ejecutar(
                'UPDATE equipo SET activo=1, estado_equipo=? WHERE idequipo=?',
                [EquipoEstado::DISPONIBLE, $idEquipoDel]
            );
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'reactivar', 'equipos', "#$idEquipoDel");
            Auth::flash('success', 'Equipo reactivado correctamente.');
        }
    }

    header('Location: ' . BASE_URL . '/equipos.php');
    exit;
}

$filtroMarcas = array_column(
    $db->consulta("SELECT idmarca AS valor, nombreMarca AS etiqueta FROM marca ORDER BY nombreMarca"),
    'etiqueta',
    'valor'
);
$filtroModelos = array_column(
    $db->consulta("SELECT idmodelo AS valor, nombreModelo AS etiqueta FROM modelo ORDER BY nombreModelo"),
    'etiqueta',
    'valor'
);
$filtroTipos = array_column(
    $db->consulta("SELECT DISTINCT tipo_equipo AS valor, tipo_equipo AS etiqueta FROM equipo WHERE tipo_equipo IS NOT NULL AND tipo_equipo<>'' ORDER BY tipo_equipo"),
    'etiqueta',
    'valor'
);
$filtroProveedores = array_column(
    $db->consulta("SELECT idproveedor AS valor, nombre AS etiqueta FROM proveedores ORDER BY nombre"),
    'etiqueta',
    'valor'
);

$pageTitle = 'Equipos';
require BASE_PATH . '/app/views/layouts/encabezado.php';
require_once BASE_PATH . '/app/views/layouts/table_filters.php';
Auth::imprimirFlash();
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script src="<?= BASE_URL ?>/public/js/catalogos-contextuales.js?v=<?= @filemtime(BASE_PATH . '/public/js/catalogos-contextuales.js') ?: APP_VERSION ?>"></script>
<script>
initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/maestros/equipos.php');
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <div class="module-header-copy">
        <h2>Equipos</h2>
        <p>Consulta y administra la identificación, compra y estado del inventario.</p>
      </div>
      <div class="page-header-actions">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newModal">
          <i class="fa fa-plus" aria-hidden="true"></i>
          <span>Agregar equipo</span>
        </button>
      </div>
    </div>
    <?php renderTableFilters([
      'search_label' => 'Buscar equipos',
      'search_placeholder' => 'Código, serie, tipo, marca o modelo',
      'table_id' => 'tablaEquipo',
      'filters' => [
        ['name' => 'estado_equipo', 'label' => 'Estado operativo', 'options' => EquipoEstado::opciones()],
        ['name' => 'tipo_equipo', 'label' => 'Tipo', 'options' => $filtroTipos],
        ['name' => 'idmarca', 'label' => 'Marca', 'options' => $filtroMarcas],
        ['name' => 'idmodelo', 'label' => 'Modelo', 'options' => $filtroModelos],
        ['name' => 'idproveedor', 'label' => 'Proveedor', 'options' => $filtroProveedores],
        ['name' => 'activo', 'label' => 'Registro', 'options' => [1 => 'Activo', 0 => 'Inactivo']],
        ['name' => 'garantia', 'label' => 'Garantía', 'options' => ['vigente' => 'Vigente', 'vence_30' => 'Vence en 30 días', 'vencida' => 'Vencida', 'sin_fecha' => 'Sin fecha']],
      ],
    ]); ?>
    <div id="datos" aria-live="polite"></div>
  </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
