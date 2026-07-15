<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('maestros');
Auth::guardarPagina(__FILE__);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    if (isset($_POST['add'])) {
        $val = trim($_POST['campo'] ?? '');
        if ($val !== '') {
            $db->ejecutar("INSERT INTO modelo (nombreModelo, activo) VALUES (?, 1)", [$val]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'crear', 'modelo', $val);
            Auth::flash('success', 'Modelo creado correctamente.');
        }
    }

    if (isset($_POST['edit'])) {
        $val = trim($_POST['campo'] ?? '');
        $db->ejecutar("UPDATE modelo SET nombreModelo=? WHERE idmodelo=?", [$val, (int)($_POST['id'] ?? 0)]);
        Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'editar', 'modelo', $val);
        Auth::flash('success', 'Modelo actualizado correctamente.');
    }

    // Alterna activo/inactivo (baja lógica reversible: nunca se borra el dato)
    if (isset($_POST['del'])) {
        $id  = (int)($_POST['id'] ?? 0);
        $fila = $db->fila("SELECT activo FROM modelo WHERE idmodelo=?", [$id]);

        if ($fila && (int)$fila['activo'] === 1) {
            $db->ejecutar("UPDATE modelo SET activo=0 WHERE idmodelo=?", [$id]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'eliminar', 'modelo', "#$id");
            Auth::flash('success', 'Modelo dado de baja correctamente.');
        } else {
            $db->ejecutar("UPDATE modelo SET activo=1 WHERE idmodelo=?", [$id]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'reactivar', 'modelo', "#$id");
            Auth::flash('success', 'Modelo reactivado correctamente.');
        }
    }

    header('Location: ' . BASE_URL . '/modelos.php');
    exit;
}

$pageTitle = 'Modelos';
require BASE_PATH . '/app/views/layouts/encabezado.php';
Auth::imprimirFlash();
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js"></script>
<script>
$(document).ready(function(){ ajaxLoad('<?= BASE_URL ?>/app/ajax/maestros/modelos.php'); });
$(document).on('keyup','#buscar',function(){ ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/maestros/modelos.php',$(this).val()); });
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header clearfix">
      <h2 class="pull-left">Modelos</h2>
      <a href="#" class="btn btn-primary pull-right" data-toggle="modal" data-target="#newModal">+ Agregar</a>
    </div>
    <div class="form-group">
      <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
      <br><div id="datos"></div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
