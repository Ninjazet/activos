<?php if ($rows): ?>
<p class="responsive-table-note"><i class="fa fa-circle-info" aria-hidden="true"></i> En pantallas pequeñas, pulsa la primera celda para ver las columnas ocultas.</p>
<table class="table table-bordered table-striped nowrap" id="tablaSoftware">
  <thead><tr><th>ID</th><th>Producto</th><th>Fabricante</th><th>Versión / edición</th><th>Categoría</th><th>Licencias</th><th>Estado</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <?php $activo = (int)$row['activo'] === 1; ?>
    <tr class="<?= $activo ? '' : 'text-muted' ?>"
        data-id="<?= (int)$row['idsoftware'] ?>"
        data-nombre="<?= htmlspecialchars($row['nombre'], ENT_QUOTES) ?>"
        data-fabricante="<?= htmlspecialchars($row['fabricante'], ENT_QUOTES) ?>"
        data-version="<?= htmlspecialchars($row['version'], ENT_QUOTES) ?>"
        data-edicion="<?= htmlspecialchars($row['edicion'], ENT_QUOTES) ?>"
        data-categoria="<?= htmlspecialchars($row['categoria'] ?? '', ENT_QUOTES) ?>"
        data-descripcion="<?= htmlspecialchars($row['descripcion'] ?? '', ENT_QUOTES) ?>"
        data-activo="<?= $activo ? 1 : 0 ?>">
      <td><?= (int)$row['idsoftware'] ?></td>
      <td><strong><?= htmlspecialchars($row['nombre']) ?></strong></td>
      <td><?= htmlspecialchars($row['fabricante']) ?></td>
      <td><?= htmlspecialchars(trim(($row['version'] ?: '') . ' ' . ($row['edicion'] ?: '')) ?: '—') ?></td>
      <td><?= htmlspecialchars($row['categoria'] ?: '—') ?></td>
      <td><?= (int)$row['licencias'] ?><?php if ((int)$row['licencias_activas'] > 0): ?><small class="d-block text-muted"><?= (int)$row['licencias_activas'] ?> activa(s)</small><?php endif; ?></td>
      <td><span class="badge app-badge-<?= $activo ? 'success' : 'muted' ?>"><?= $activo ? 'Activo' : 'Inactivo' ?></span></td>
      <td class="table-actions">
        <a href="#" onclick="return editarSoftware(event)" data-bs-toggle="modal" data-bs-target="#editSoftwareModal" title="Editar" aria-label="Editar software"><i class="fa fa-edit" aria-hidden="true"></i></a>
        <a href="#" onclick="return estadoSoftware(event)" data-bs-toggle="modal" data-bs-target="#estadoSoftwareModal"
           title="<?= $activo ? 'Dar de baja' : 'Reactivar' ?>" aria-label="<?= $activo ? 'Dar de baja software' : 'Reactivar software' ?>"><i class="fa fa-<?= $activo ? 'trash' : 'undo' ?>" aria-hidden="true"></i></a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
$(function(){
  $('#tablaSoftware').DataTable({
    dom:'lrtip',order:[[0,'desc']],autoWidth:false,
    responsive:{details:{type:'inline',target:'td:first-child'}},
    columnDefs:[
      {targets:0,className:'dtr-control',responsivePriority:5},
      {targets:1,responsivePriority:1},
      {targets:7,responsivePriority:1,orderable:false}
    ]
  });
});
function llenarSoftware(tr,prefijo){
  $('#'+prefijo+'Id').val(tr.data('id'));
  $('#'+prefijo+'Nombre').val(tr.attr('data-nombre'));
  $('#'+prefijo+'Fabricante').val(tr.attr('data-fabricante'));
  $('#'+prefijo+'Version').val(tr.attr('data-version'));
  $('#'+prefijo+'Edicion').val(tr.attr('data-edicion'));
  $('#'+prefijo+'Categoria').val(tr.attr('data-categoria'));
  $('#'+prefijo+'Descripcion').val(tr.attr('data-descripcion'));
}
function editarSoftware(evento){ llenarSoftware($(evento.target).closest('tr'),'editSoftware'); return true; }
function estadoSoftware(evento){
  var tr=$(evento.target).closest('tr'), activo=String(tr.attr('data-activo'))==='1';
  $('#estadoSoftwareId').val(tr.data('id'));
  $('#estadoSoftwareNombre').text(tr.attr('data-fabricante')+' '+tr.attr('data-nombre'));
  $('#estadoSoftwareTitulo').text(activo?'Dar de baja software':'Reactivar software');
  $('#estadoSoftwareAyuda').text(activo
    ? 'Solo se permitirá si no tiene licencias activas. El historial se conservará.'
    : 'Volverá a estar disponible para registrar licencias nuevas.');
  $('#estadoSoftwareBoton').toggleClass('btn-danger',activo).toggleClass('btn-success',!activo).text(activo?'Dar de baja':'Reactivar');
  return true;
}
</script>
<?php else: ?><p class="lead"><em>No hay productos de software que coincidan con los filtros.</em></p><?php endif; ?>

<?php
$camposSoftware = static function(string $prefijo): void {
  $p=htmlspecialchars($prefijo,ENT_QUOTES); ?>
  <div class="form-grid">
    <div class="form-group"><label for="<?= $p ?>Nombre">Producto</label><input type="text" name="nombre" id="<?= $p ?>Nombre" class="form-control" maxlength="150" required></div>
    <div class="form-group"><label for="<?= $p ?>Fabricante">Fabricante</label><input type="text" name="fabricante" id="<?= $p ?>Fabricante" class="form-control" maxlength="120" required></div>
    <div class="form-group"><label for="<?= $p ?>Version">Versión</label><input type="text" name="version" id="<?= $p ?>Version" class="form-control" maxlength="60" placeholder="Ej. 2024, 16.0"></div>
    <div class="form-group"><label for="<?= $p ?>Edicion">Edición</label><input type="text" name="edicion" id="<?= $p ?>Edicion" class="form-control" maxlength="100" placeholder="Ej. Business, Pro"></div>
    <div class="form-group form-span-2"><label for="<?= $p ?>Categoria">Categoría</label><input type="text" name="categoria" id="<?= $p ?>Categoria" class="form-control" maxlength="80" list="categoriasSoftware" placeholder="Ej. Ofimática, Seguridad"></div>
    <div class="form-group form-span-2"><label for="<?= $p ?>Descripcion">Descripción</label><textarea name="descripcion" id="<?= $p ?>Descripcion" class="form-control" rows="3" maxlength="500"></textarea></div>
  </div>
<?php }; ?>
<datalist id="categoriasSoftware"><?php foreach ($categorias as $categoria): ?><option value="<?= htmlspecialchars($categoria, ENT_QUOTES) ?>"><?php endforeach; ?></datalist>

<div class="modal fade" id="newSoftwareModal" tabindex="-1" aria-labelledby="newSoftwareTitulo" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form action="<?= BASE_URL ?>/software.php" method="post"><?= Auth::csrfField() ?>
    <div class="modal-header"><h5 class="modal-title" id="newSoftwareTitulo">Nuevo software</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><?php $camposSoftware('newSoftware'); ?></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success" name="add" value="1">Guardar software</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="editSoftwareModal" tabindex="-1" aria-labelledby="editSoftwareTitulo" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form action="<?= BASE_URL ?>/software.php" method="post"><?= Auth::csrfField() ?><input type="hidden" name="idsoftware" id="editSoftwareId">
    <div class="modal-header"><h5 class="modal-title" id="editSoftwareTitulo">Editar software</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><?php $camposSoftware('editSoftware'); ?></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="edit" value="1">Actualizar software</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="estadoSoftwareModal" tabindex="-1" aria-labelledby="estadoSoftwareTitulo" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
  <form action="<?= BASE_URL ?>/software.php" method="post"><?= Auth::csrfField() ?><input type="hidden" name="idsoftware" id="estadoSoftwareId">
    <div class="modal-header"><h5 class="modal-title" id="estadoSoftwareTitulo">Cambiar estado</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><p><strong id="estadoSoftwareNombre"></strong></p><p class="text-muted" id="estadoSoftwareAyuda"></p></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn" id="estadoSoftwareBoton" name="del" value="1">Confirmar</button></div>
  </form>
</div></div></div>
