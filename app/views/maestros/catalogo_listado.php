<?php if ($rows): ?>
<table class="table table-bordered table-striped" id="tablaCatalogo">
  <thead>
    <tr><th>ID</th><th>Descripción</th><th>Estado</th><th>Acción</th></tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <tr class="<?= (int)$row['activo'] === 0 ? 'text-muted' : '' ?>">
      <td><?= (int)$row['id'] ?></td>
      <td><?= htmlspecialchars($row['nombre']) ?></td>
      <td><?= (int)$row['activo'] === 1
          ? '<span class="badge app-badge-success">Activo</span>'
          : '<span class="badge app-badge-muted">Inactivo</span>' ?></td>
      <td>
        <?php if ((int)$row['activo'] === 1): ?>
        <a href="#" onclick="editItem(event)" data-bs-toggle="modal" data-bs-target="#editModal"
           title="Editar" aria-label="Editar <?= htmlspecialchars($row['nombre'], ENT_QUOTES) ?>">
          <i class="fa fa-edit" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
        <a href="#" onclick="delItem(event)"
           title="<?= (int)$row['activo'] === 1 ? 'Dar de baja' : 'Reactivar' ?>"
           aria-label="<?= (int)$row['activo'] === 1 ? 'Dar de baja' : 'Reactivar' ?> <?= htmlspecialchars($row['nombre'], ENT_QUOTES) ?>"
           data-bs-toggle="modal" data-bs-target="#delModal">
          <i class="fa fa-<?= (int)$row['activo'] === 1 ? 'trash' : 'undo' ?>"
             style="color:<?= (int)$row['activo'] === 1 ? 'red' : '#28a745' ?>" aria-hidden="true"></i>
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
$(function () {
  $('#tablaCatalogo').DataTable({ dom: 'lrtip', order: [[0, 'desc']] });
});
function editItem(evento) {
  var fila = $(evento.target).closest('tr');
  $('#editId').val(fila.find('td').eq(0).text());
  $('#editCampo').val(fila.find('td').eq(1).text());
}
function delItem(evento) {
  var fila = $(evento.target).closest('tr');
  $('#delId').val(fila.find('td').eq(0).text());
  $('#lblDel').text(fila.find('td').eq(1).text());
}
</script>
<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>

<div class="modal fade" id="newModal" tabindex="-1" aria-labelledby="nuevoCatalogoTitulo" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/<?= $config['ruta'] ?>" method="post">
      <?= Auth::csrfField() ?>
      <div class="modal-header"><h5 class="modal-title" id="nuevoCatalogoTitulo">Nuevo <?= htmlspecialchars($config['singular']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <div class="form-group"><label for="nuevoCampo">Descripción</label>
          <input type="text" name="campo" id="nuevoCampo" class="form-control" maxlength="100" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-success" value="Guardar" name="add">
      </div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editarCatalogoTitulo" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/<?= $config['ruta'] ?>" method="post">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="id" id="editId">
      <div class="modal-header"><h5 class="modal-title" id="editarCatalogoTitulo">Editar <?= htmlspecialchars($config['singular']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <div class="form-group"><label for="editCampo">Descripción</label>
          <input type="text" name="campo" id="editCampo" class="form-control" maxlength="100" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-primary" value="Actualizar" name="edit">
      </div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="delModal" tabindex="-1" aria-labelledby="estadoCatalogoTitulo" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/<?= $config['ruta'] ?>" method="post">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="id" id="delId">
      <div class="modal-header"><h5 class="modal-title" id="estadoCatalogoTitulo">Cambiar estado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <p>¿Cambiar el estado de <strong><span id="lblDel"></span></strong>?<br>
        <small class="text-muted">Si está activo, se dará de baja. Si está inactivo, se reactivará.</small></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-warning" value="Confirmar" name="del">
      </div>
    </form>
  </div></div>
</div>
