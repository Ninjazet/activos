<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('maestros');
Auth::guardarPagina(__FILE__);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    // ---- INSERTAR ----
    if (isset($_POST['add'])) {
        $idmarca         = (int)($_POST['idmarca'] ?? 0);
        $idmodelo        = (int)($_POST['idmodelo'] ?? 0);
        $fechaCompra     = trim($_POST['fecha_compra'] ?? '') ?: null;
        $costo           = trim($_POST['costo'] ?? '') ?: null;
        $factura         = trim($_POST['factura'] ?? '') ?: null;
        $garantia        = trim($_POST['vencimiento_garantia'] ?? '') ?: null;
        $numeroSerie     = strtoupper(trim($_POST['numero_serie'] ?? '')) ?: null;
        $tipoEquipo      = trim($_POST['tipo_equipo'] ?? '') ?: 'Otro';
        $imagen          = '';

        if (($numeroSerie !== null && strlen($numeroSerie) > 100) || strlen($tipoEquipo) > 50) {
            Auth::flash('error', 'El número de serie o el tipo de equipo exceden el tamaño permitido.');
            header('Location: ' . BASE_URL . '/equipos.php');
            exit;
        }
        if ($costo !== null && (!is_numeric($costo) || (float)$costo < 0)) {
            Auth::flash('error', 'El costo debe ser un número mayor o igual a cero.');
            header('Location: ' . BASE_URL . '/equipos.php');
            exit;
        }
        if ($fechaCompra && $garantia && $garantia < $fechaCompra) {
            Auth::flash('error', 'El vencimiento de garantía no puede ser anterior a la fecha de compra.');
            header('Location: ' . BASE_URL . '/equipos.php');
            exit;
        }

        try {
            if (!Upload::estaVacio($_FILES['archivo'] ?? null)) {
                $archivoGuardado = Upload::guardarImagen($_FILES['archivo'], IMG_EQUIPOS, 'equipo');
                $imagen = 'public/img/equipos/' . $archivoGuardado;
            }

            $idEquipo = $db->transaccion(function (Database $db) use (
                $idmarca, $idmodelo, $imagen, $fechaCompra, $costo, $factura,
                $garantia, $numeroSerie, $tipoEquipo
            ) {
                $id = $db->ejecutar(
                    "INSERT INTO equipo
                        (idmarca_equipo, idmodelo_equipo, imagen, activo, fecha_compra, costo, factura,
                         vencimiento_garantia, estado_equipo, numero_serie, codigo_activo, tipo_equipo)
                     VALUES (?, ?, ?, 1, ?, ?, ?, ?, 1, ?, NULL, ?)",
                    [$idmarca, $idmodelo, $imagen, $fechaCompra, $costo, $factura, $garantia, $numeroSerie, $tipoEquipo]
                );
                $codigo = 'EQ-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
                $db->ejecutar("UPDATE equipo SET codigo_activo=? WHERE idequipo=?", [$codigo, $id]);
                return $id;
            });
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'crear', 'equipos', "#$idEquipo marca=$idmarca modelo=$idmodelo");
            Auth::flash('success', 'Equipo creado correctamente.');
        } catch (\RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
        } catch (PDOException $e) {
            Auth::flash('error', 'No se pudo crear: verifica la marca y el modelo seleccionados.');
        }
    }

    // ---- EDITAR ----
    if (isset($_POST['edit'])) {
        $id          = (int)($_POST['idequipo'] ?? 0);
        $idmarca     = (int)($_POST['marcaAct'] ?? 0);
        $idmodelo    = (int)($_POST['modeloAct'] ?? 0);
        $fechaCompra = trim($_POST['fecha_compraAct'] ?? '') ?: null;
        $costo       = trim($_POST['costoAct'] ?? '') ?: null;
        $factura     = trim($_POST['facturaAct'] ?? '') ?: null;
        $garantia    = trim($_POST['vencimiento_garantiaAct'] ?? '') ?: null;
        $numeroSerie = strtoupper(trim($_POST['numero_serieAct'] ?? '')) ?: null;
        $tipoEquipo  = trim($_POST['tipo_equipoAct'] ?? '') ?: 'Otro';
        $estado      = (int)($_POST['estado_equipoAct'] ?? 1);

        if (($numeroSerie !== null && strlen($numeroSerie) > 100) || strlen($tipoEquipo) > 50) {
            Auth::flash('error', 'El número de serie o el tipo de equipo exceden el tamaño permitido.');
            header('Location: ' . BASE_URL . '/equipos.php');
            exit;
        }
        if ($costo !== null && (!is_numeric($costo) || (float)$costo < 0)) {
            Auth::flash('error', 'El costo debe ser un número mayor o igual a cero.');
            header('Location: ' . BASE_URL . '/equipos.php');
            exit;
        }
        if ($fechaCompra && $garantia && $garantia < $fechaCompra) {
            Auth::flash('error', 'El vencimiento de garantía no puede ser anterior a la fecha de compra.');
            header('Location: ' . BASE_URL . '/equipos.php');
            exit;
        }
        if (!in_array($estado, [1, 2, 3, 4, 5], true)) {
            $estado = 1;
        }
        if ($db->fila("SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1", [$id])) {
            $estado = 2;
        } elseif ($estado === 2) {
            // "Asignado" solo puede establecerse desde el flujo de asignaciones.
            $estado = 1;
        }

        try {
            if (!Upload::estaVacio($_FILES['archivoAct'] ?? null)) {
                $archivoGuardado = Upload::guardarImagen($_FILES['archivoAct'], IMG_EQUIPOS, 'equipo');
                $db->ejecutar(
                    "UPDATE equipo SET idmarca_equipo=?, idmodelo_equipo=?, imagen=?, fecha_compra=?, costo=?, factura=?, vencimiento_garantia=?, numero_serie=?, tipo_equipo=?, estado_equipo=?, activo=IF(?=5,0,activo) WHERE idequipo=?",
                    [$idmarca, $idmodelo, 'public/img/equipos/' . $archivoGuardado, $fechaCompra, $costo, $factura, $garantia, $numeroSerie, $tipoEquipo, $estado, $estado, $id]
                );
            } else {
                $db->ejecutar(
                    "UPDATE equipo SET idmarca_equipo=?, idmodelo_equipo=?, fecha_compra=?, costo=?, factura=?, vencimiento_garantia=?, numero_serie=?, tipo_equipo=?, estado_equipo=?, activo=IF(?=5,0,activo) WHERE idequipo=?",
                    [$idmarca, $idmodelo, $fechaCompra, $costo, $factura, $garantia, $numeroSerie, $tipoEquipo, $estado, $estado, $id]
                );
            }
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'editar', 'equipos', "#$id");
            Auth::flash('success', 'Equipo actualizado correctamente.');
        } catch (\RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
        } catch (PDOException $e) {
            Auth::flash('error', 'No se pudo actualizar: verifica la marca y el modelo seleccionados.');
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
            if ($tieneAsignacion) {
                Auth::flash('error', 'No se puede dar de baja: este equipo tiene una asignación activa. Quita primero la asignación.');
            } else {
                $db->ejecutar("UPDATE equipo SET activo=0, estado_equipo=5 WHERE idequipo=?", [$idEquipoDel]);
                Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'eliminar', 'equipos', "#$idEquipoDel");
                Auth::flash('success', 'Equipo dado de baja correctamente.');
            }
        } else {
            $db->ejecutar("UPDATE equipo SET activo=1, estado_equipo=1 WHERE idequipo=?", [$idEquipoDel]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'reactivar', 'equipos', "#$idEquipoDel");
            Auth::flash('success', 'Equipo reactivado correctamente.');
        }
    }

    header('Location: ' . BASE_URL . '/equipos.php');
    exit;
}

$pageTitle = 'Equipos';
require BASE_PATH . '/app/views/layouts/encabezado.php';
Auth::imprimirFlash();
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script src="<?= BASE_URL ?>/public/js/catalogos-contextuales.js?v=<?= @filemtime(BASE_PATH . '/public/js/catalogos-contextuales.js') ?: APP_VERSION ?>"></script>
<script>
$(document).ready(function(){ ajaxLoad('<?= BASE_URL ?>/app/ajax/maestros/equipos.php'); });
$(document).on('input','#buscar',function(){ ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/maestros/equipos.php',$(this).val()); });
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <div class="module-header-copy">
        <h2>Equipos</h2>
        <p>Consulta y administra la identificación, compra y estado del inventario.</p>
      </div>
      <div class="page-header-actions">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#newModal">
          <i class="fa fa-plus" aria-hidden="true"></i>
          <span>Agregar equipo</span>
        </button>
      </div>
    </div>
    <div class="page-toolbar" role="search">
      <label for="buscar" class="sr-only">Buscar equipos</label>
      <input type="search" id="buscar" class="form-control"
             placeholder="Buscar por código, serie, tipo, marca o modelo"
             autocomplete="off" aria-controls="tablaEquipo">
    </div>
    <div id="datos" aria-live="polite"></div>
  </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
