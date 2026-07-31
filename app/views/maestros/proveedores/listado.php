<?php if ($rows): ?>
<p class="responsive-table-note">
  <i class="fa fa-circle-info" aria-hidden="true"></i>
  En pantallas pequeñas, pulsa el indicador de la primera celda para ver las columnas ocultas.
</p>
<table class="table table-bordered table-striped nowrap" id="tablaProveedores">
  <thead>
    <tr>
      <th>ID</th><th>Proveedor</th><th>RTN</th><th>Contacto</th><th>Correo / teléfono</th>
      <th>Equipos</th><th>Estado</th><th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <?php $activo = (int)$row['activo'] === 1; ?>
    <tr class="<?= $activo ? '' : 'text-muted' ?>"
        data-id="<?= (int)$row['idproveedor'] ?>"
        data-nombre="<?= htmlspecialchars($row['nombre'], ENT_QUOTES) ?>"
        data-rtn="<?= htmlspecialchars($row['rtn'] ?? '', ENT_QUOTES) ?>"
        data-contacto="<?= htmlspecialchars($row['contacto'] ?? '', ENT_QUOTES) ?>"
        data-telefono="<?= htmlspecialchars($row['telefono'] ?? '', ENT_QUOTES) ?>"
        data-correo="<?= htmlspecialchars($row['correo'] ?? '', ENT_QUOTES) ?>"
        data-direccion="<?= htmlspecialchars($row['direccion'] ?? '', ENT_QUOTES) ?>"
        data-observaciones="<?= htmlspecialchars($row['observaciones'] ?? '', ENT_QUOTES) ?>"
        data-activo="<?= $activo ? 1 : 0 ?>">
      <td><?= (int)$row['idproveedor'] ?></td>
      <td><strong><?= htmlspecialchars($row['nombre']) ?></strong></td>
      <td><?= htmlspecialchars($row['rtn'] ?: '—') ?></td>
      <td><?= htmlspecialchars($row['contacto'] ?: '—') ?></td>
      <td>
        <?= htmlspecialchars($row['correo'] ?: '—') ?>
        <?php if (!empty($row['telefono'])): ?><small class="d-block text-muted"><?= htmlspecialchars($row['telefono']) ?></small><?php endif; ?>
      </td>
      <td><?= (int)$row['equipos'] ?></td>
      <td><?= $activo
        ? '<span class="badge app-badge-success">Activo</span>'
        : '<span class="badge app-badge-muted">Inactivo</span>' ?></td>
      <td class="table-actions">
        <a href="<?= BASE_URL ?>/proveedor.php?id=<?= (int)$row['idproveedor'] ?>" title="Ver ficha" aria-label="Ver ficha del proveedor">
          <i class="fa fa-eye" aria-hidden="true"></i>
        </a>
        <?php if ($activo): ?>
        <a href="#" onclick="return editarProveedor(event)" data-bs-toggle="modal" data-bs-target="#editProveedorModal"
           title="Editar" aria-label="Editar proveedor"><i class="fa fa-edit" aria-hidden="true"></i></a>
        <?php endif; ?>
        <a href="#" onclick="return estadoProveedor(event)" data-bs-toggle="modal" data-bs-target="#estadoProveedorModal"
           title="<?= $activo ? 'Dar de baja' : 'Reactivar' ?>" aria-label="<?= $activo ? 'Dar de baja proveedor' : 'Reactivar proveedor' ?>">
          <i class="fa fa-<?= $activo ? 'trash' : 'undo' ?>" aria-hidden="true"></i>
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
$(function () {
  $('#tablaProveedores').DataTable({
    dom: 'lrtip', order: [[0, 'desc']], autoWidth: false,
    responsive: { details: { type: 'inline', target: 'td:first-child' } },
    columnDefs: [
      { targets: 0, className: 'dtr-control', responsivePriority: 5 },
      { targets: 1, responsivePriority: 1 },
      { targets: 7, responsivePriority: 1, orderable: false }
    ]
  });
});

function llenarProveedor(tr, prefijo) {
  $('#' + prefijo + 'Id').val(tr.data('id'));
  $('#' + prefijo + 'Nombre').val(tr.attr('data-nombre'));
  $('#' + prefijo + 'Rtn').val(tr.attr('data-rtn'));
  $('#' + prefijo + 'Contacto').val(tr.attr('data-contacto'));
  $('#' + prefijo + 'Telefono').val(tr.attr('data-telefono'));
  $('#' + prefijo + 'Correo').val(tr.attr('data-correo'));
  $('#' + prefijo + 'Direccion').val(tr.attr('data-direccion'));
  $('#' + prefijo + 'Observaciones').val(tr.attr('data-observaciones'));
}
function editarProveedor(evento) {
  llenarProveedor($(evento.target).closest('tr'), 'editProveedor');
  return true;
}
function estadoProveedor(evento) {
  var tr = $(evento.target).closest('tr');
  var activo = String(tr.attr('data-activo')) === '1';
  $('#estadoProveedorId').val(tr.data('id'));
  $('#estadoProveedorNombre').text(tr.attr('data-nombre'));
  $('#estadoProveedorTitulo').text(activo ? 'Dar de baja proveedor' : 'Reactivar proveedor');
  $('#estadoProveedorAyuda').text(activo
    ? 'No aparecerá en compras nuevas, pero conservará todos sus equipos relacionados.'
    : 'Volverá a estar disponible para compras y mantenimientos nuevos.');
  $('#estadoProveedorBoton').toggleClass('btn-danger', activo).toggleClass('btn-success', !activo)
    .text(activo ? 'Dar de baja' : 'Reactivar');
  return true;
}
</script>
<?php else: ?>
<p class="lead"><em>No hay proveedores que coincidan con los filtros.</em></p>
<?php endif; ?>

<?php
$camposProveedor = static function (string $prefijo): void {
  $p = htmlspecialchars($prefijo, ENT_QUOTES);
  ?>
  <div class="form-grid">
    <div class="form-group form-span-2"><label for="<?= $p ?>Nombre">Nombre o razón social</label><input type="text" name="nombre" id="<?= $p ?>Nombre" class="form-control" maxlength="150" required></div>
    <div class="form-group"><label for="<?= $p ?>Rtn">RTN</label><input type="text" name="rtn" id="<?= $p ?>Rtn" class="form-control" maxlength="30"></div>
    <div class="form-group"><label for="<?= $p ?>Contacto">Persona de contacto</label><input type="text" name="contacto" id="<?= $p ?>Contacto" class="form-control" maxlength="120"></div>
    <div class="form-group"><label for="<?= $p ?>Telefono">Teléfono</label><input type="text" name="telefono" id="<?= $p ?>Telefono" class="form-control" maxlength="30"></div>
    <div class="form-group"><label for="<?= $p ?>Correo">Correo</label><input type="email" name="correo" id="<?= $p ?>Correo" class="form-control" maxlength="150"></div>
    <div class="form-group form-span-2"><label for="<?= $p ?>Direccion">Dirección</label><input type="text" name="direccion" id="<?= $p ?>Direccion" class="form-control" maxlength="255"></div>
    <div class="form-group form-span-2"><label for="<?= $p ?>Observaciones">Observaciones</label><textarea name="observaciones" id="<?= $p ?>Observaciones" class="form-control" rows="3" maxlength="500"></textarea></div>
  </div>
  <?php
};
?>

<div class="modal fade" id="newProveedorModal" tabindex="-1" aria-labelledby="newProveedorTitulo" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form action="<?= BASE_URL ?>/proveedores.php" method="post">
      <?= Auth::csrfField() ?>
      <div class="modal-header"><h5 class="modal-title" id="newProveedorTitulo">Nuevo proveedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body"><?php $camposProveedor('newProveedor'); ?></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success" name="add" value="1">Guardar proveedor</button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="editProveedorModal" tabindex="-1" aria-labelledby="editProveedorTitulo" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form action="<?= BASE_URL ?>/proveedores.php" method="post">
      <?= Auth::csrfField() ?><input type="hidden" name="idproveedor" id="editProveedorId">
      <div class="modal-header"><h5 class="modal-title" id="editProveedorTitulo">Editar proveedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body"><?php $camposProveedor('editProveedor'); ?></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="edit" value="1">Actualizar proveedor</button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="estadoProveedorModal" tabindex="-1" aria-labelledby="estadoProveedorTitulo" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/proveedores.php" method="post">
      <?= Auth::csrfField() ?><input type="hidden" name="idproveedor" id="estadoProveedorId">
      <div class="modal-header"><h5 class="modal-title" id="estadoProveedorTitulo">Cambiar estado</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body"><p><strong id="estadoProveedorNombre"></strong></p><p class="text-muted" id="estadoProveedorAyuda"></p></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn" id="estadoProveedorBoton" name="del" value="1">Confirmar</button></div>
    </form>
  </div></div>
</div>
