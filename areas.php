<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerirPermiso('maestros');
Auth::guardarPagina(__FILE__);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();

    if (isset($_POST['add'])) {
        $val = trim($_POST['campo'] ?? '');
        if ($val === '') {
            Auth::flash('error', 'Área: el nombre no puede estar vacío.');
        } elseif ($db->fila("SELECT idarea FROM areas WHERE descripcionarea=?", [$val])) {
            Auth::flash('error', 'Área: ya existe un registro con ese nombre; si está inactivo, puedes reactivarlo.');
        } else {
            $db->ejecutar("INSERT INTO areas (descripcionarea, activo) VALUES (?, 1)", [$val]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'crear', 'areas', $val);
            Auth::flash('success', 'Área creado correctamente.');
        }
    }
    if (isset($_POST['edit'])) {
        $id  = (int)($_POST['id'] ?? 0);
        $val = trim($_POST['campo'] ?? '');
        if ($val === '') {
            Auth::flash('error', 'Área: el nombre no puede quedar vacío.');
        } elseif ($db->fila("SELECT idarea FROM areas WHERE descripcionarea=? AND idarea<>?", [$val, $id])) {
            Auth::flash('error', 'Área: ya existe otro registro con ese nombre.');
        } else {
            $db->ejecutar("UPDATE areas SET descripcionarea=? WHERE idarea=?", [$val, $id]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'editar', 'areas', $val);
            Auth::flash('success', 'Área actualizado correctamente.');
        }
    }
    // Alterna activo/inactivo (baja lógica reversible: nunca se borra el dato)
    if (isset($_POST['del'])) {
        $id  = (int)($_POST['id'] ?? 0);
        $fila = $db->fila("SELECT activo FROM areas WHERE idarea=?", [$id]);

        if (!$fila) {
            Auth::flash('error', 'El registro indicado no existe.');
        } elseif ((int)$fila['activo'] === 1) {
            $db->ejecutar("UPDATE areas SET activo=0 WHERE idarea=?", [$id]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'eliminar', 'areas', "#$id");
            Auth::flash('success', 'Área dado de baja correctamente.');
        } else {
            $db->ejecutar("UPDATE areas SET activo=1 WHERE idarea=?", [$id]);
            Auth::registrarBitacora((int)Auth::get('idusuario'), Auth::get('usuario'), 'reactivar', 'areas', "#$id");
            Auth::flash('success', 'Área reactivado correctamente.');
        }
    }

    header('Location: ' . BASE_URL . '/areas.php');
    exit;
}

$pageTitle = 'Áreas';
require BASE_PATH . '/app/views/layouts/encabezado.php';
Auth::imprimirFlash();
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
$(document).ready(function(){ ajaxLoad('<?= BASE_URL ?>/app/ajax/maestros/areas.php'); });
$(document).on('keyup','#buscar',function(){ ajaxLoadDebounced('<?= BASE_URL ?>/app/ajax/maestros/areas.php',$(this).val()); });
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header clearfix">
      <h2>Áreas</h2>
      <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newModal">+ Agregar</a>
    </div>
    <div class="form-group">
      <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
      <br><div id="datos"></div>
    </div>
  </div>
</div>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
