<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('maestros');
Auth::guardarPagina(__FILE__);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    // ---- INSERTAR ----
    if (isset($_POST['add'])) {
        $idmarca  = (int)($_POST['idmarca'] ?? 0);
        $idmodelo = (int)($_POST['idmodelo'] ?? 0);
        $imagen   = '';

        try {
            if (!Upload::estaVacio($_FILES['archivo'] ?? null)) {
                $archivoGuardado = Upload::guardarImagen($_FILES['archivo'], IMG_EQUIPOS, 'equipo');
                $imagen = 'public/img/equipos/' . $archivoGuardado;
            }

            $db->ejecutar(
                "INSERT INTO equipo (idmarca_equipo, idmodelo_equipo, imagen, activo) VALUES (?, ?, ?, 1)",
                [$idmarca, $idmodelo, $imagen]
            );
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'crear', 'equipos', "marca=$idmarca modelo=$idmodelo");
            Auth::flash('success', 'Equipo creado correctamente.');
        } catch (\RuntimeException $e) {
            Auth::flash('error', $e->getMessage());
        } catch (PDOException $e) {
            Auth::flash('error', 'No se pudo crear: verifica la marca y el modelo seleccionados.');
        }
    }

    // ---- EDITAR ----
    if (isset($_POST['edit'])) {
        $id       = (int)($_POST['idequipo'] ?? 0);
        $idmarca  = (int)($_POST['marcaAct'] ?? 0);
        $idmodelo = (int)($_POST['modeloAct'] ?? 0);

        try {
            if (!Upload::estaVacio($_FILES['archivoAct'] ?? null)) {
                $archivoGuardado = Upload::guardarImagen($_FILES['archivoAct'], IMG_EQUIPOS, 'equipo');
                $db->ejecutar(
                    "UPDATE equipo SET idmarca_equipo=?, idmodelo_equipo=?, imagen=? WHERE idequipo=?",
                    [$idmarca, $idmodelo, 'public/img/equipos/' . $archivoGuardado, $id]
                );
            } else {
                $db->ejecutar(
                    "UPDATE equipo SET idmarca_equipo=?, idmodelo_equipo=? WHERE idequipo=?",
                    [$idmarca, $idmodelo, $id]
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

        if ($filaActual && (int)$filaActual['activo'] === 1) {
            $tieneAsignacion = $db->fila("SELECT idasignacion FROM asignacion WHERE idequipo=? AND activa=1", [$idEquipoDel]);
            if ($tieneAsignacion) {
                Auth::flash('error', 'No se puede dar de baja: este equipo tiene una asignación activa. Quita primero la asignación.');
            } else {
                $db->ejecutar("UPDATE equipo SET activo=0 WHERE idequipo=?", [$idEquipoDel]);
                Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'eliminar', 'equipos', "#$idEquipoDel");
                Auth::flash('success', 'Equipo dado de baja correctamente.');
            }
        } else {
            $db->ejecutar("UPDATE equipo SET activo=1 WHERE idequipo=?", [$idEquipoDel]);
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
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js"></script>
<script>
$(document).ready(function(){ ajaxLoad('<?= BASE_URL ?>/app/ajax/maestros/equipos.php'); });
$(document).on('keyup','#buscar',function(){ ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/maestros/equipos.php',$(this).val()); });
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header clearfix">
      <h2 class="pull-left">Equipos</h2>
      <a href="#" class="btn btn-primary pull-right" data-toggle="modal" data-target="#newModal">+ Agregar</a>
    </div>
    <div class="form-group">
      <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
      <br><div id="datos"></div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
