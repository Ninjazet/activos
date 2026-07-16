<?php
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerir();
$db  = Database::getInstance();
$q   = trim($_POST['query'] ?? '');
$sql = "SELECT idcargo, descripcioncargo, activo FROM cargos";
$params = [];
if ($q !== '') {
    $sql   .= " WHERE (descripcioncargo LIKE ? OR idcargo LIKE ?)";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY idcargo DESC";
$rows = $db->consulta($sql, $params);
?>
<?php if ($rows): ?>
<table class="table table-bordered table-striped" id="tablacargo">
  <thead style="background-color:#D3E9F1">
    <tr><th>ID</th><th>Descripción</th><th>Estado</th><th>Acción</th></tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= (int)$r['activo'] === 0 ? 'text-muted' : '' ?>">
      <td><?= $r['idcargo'] ?></td>
      <td><?= htmlspecialchars($r['descripcioncargo']) ?></td>
      <td><?= (int)$r['activo'] === 1 ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>' ?></td>
      <td>
        <?php if ((int)$r['activo'] === 1): ?>
        <a href="#" onclick="editItem(event)" data-toggle="modal" data-target="#editModal">
          <i class="fa fa-edit"></i></a>
        <?php endif; ?>
        <a href="#" onclick="delItem(event)" title="<?= (int)$r['activo'] === 1 ? 'Dar de baja' : 'Reactivar' ?>"
           data-toggle="modal" data-target="#delModal">
          <i class="fa fa-<?= (int)$r['activo'] === 1 ? 'trash' : 'undo' ?>"
             style="color:<?= (int)$r['activo'] === 1 ? 'red' : '#28a745' ?>"></i></a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
$(document).ready(function(){ $('#tablacargo').DataTable({ dom: 'lrtip', order: [[0, 'desc']] }); });
function editItem(e){
  var tr=$(e.target).closest('tr');
  $('#editId').val(tr.find('td').eq(0).text());
  $('#editCampo').val(tr.find('td').eq(1).text());
}
function delItem(e){
  var tr=$(e.target).closest('tr');
  $('#delId').val(tr.find('td').eq(0).text());
  $('#lblDel').text(tr.find('td').eq(1).text());
}
</script>
<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>
<!-- MODAL NUEVO -->
<div class="modal fade" id="newModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/cargo.php" method="post">
      <?= Auth::csrfField() ?>
      <div class="modal-header"><h5 class="modal-title">Nuevo registro</h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button></div>
      <div class="modal-body">
        <div class="form-group"><label>Descripción</label>
          <input type="text" name="campo" class="form-control" required></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-success" value="Guardar" name="add">
      </div>
    </form>
  </div></div>
</div>
<!-- MODAL EDITAR -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/cargo.php" method="post">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="id" id="editId">
      <div class="modal-header"><h5 class="modal-title">Editar registro</h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button></div>
      <div class="modal-body">
        <div class="form-group"><label>Descripción</label>
          <input type="text" name="campo" id="editCampo" class="form-control" required></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-primary" value="Actualizar" name="edit">
      </div>
    </form>
  </div></div>
</div>
<!-- MODAL DAR DE BAJA / REACTIVAR -->
<div class="modal fade" id="delModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/cargo.php" method="post">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="id" id="delId">
      <div class="modal-header"><h5 class="modal-title">Cambiar estado</h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button></div>
      <div class="modal-body">
        <p>¿Cambiar el estado de <strong><span id="lblDel"></span></strong>?<br>
        <small class="text-muted">Si está activo, se dará de baja. Si está inactivo, se reactivará.</small></p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-warning" value="Confirmar" name="del">
      </div>
    </form>
  </div></div>
</div>
