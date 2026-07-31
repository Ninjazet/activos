<?php if ($rows): ?>
<p class="responsive-table-note">
  <i class="fa fa-circle-info" aria-hidden="true"></i>
  Pulsa el indicador de la primera celda para consultar el diagnóstico y otros detalles ocultos.
</p>
<table class="table table-bordered table-striped nowrap" id="tablaMantenimientos">
  <thead><tr>
    <th>#</th><th>Equipo</th><th>Tipo</th><th>Estado</th><th>Ingreso</th><th>Proveedor</th>
    <th>Problema</th><th>Diagnóstico</th><th>Trabajo realizado</th><th>Costo</th><th>Cierre</th><th>Resultado</th><th>Acciones</th>
  </tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <?php $abierto = in_array($row['estado'], MantenimientoEstado::estadosActivos(), true); ?>
    <tr class="<?= $row['estado'] === MantenimientoEstado::CANCELADO ? 'text-muted' : '' ?>"
        data-id="<?= (int)$row['idmantenimiento'] ?>"
        data-idproveedor="<?= (int)($row['idproveedor'] ?? 0) ?>"
        data-tipo="<?= htmlspecialchars($row['tipo'], ENT_QUOTES) ?>"
        data-estado="<?= htmlspecialchars($row['estado'], ENT_QUOTES) ?>"
        data-problema="<?= htmlspecialchars($row['descripcion_problema'], ENT_QUOTES) ?>"
        data-diagnostico="<?= htmlspecialchars($row['diagnostico'] ?? '', ENT_QUOTES) ?>"
        data-trabajo="<?= htmlspecialchars($row['trabajo_realizado'] ?? '', ENT_QUOTES) ?>"
        data-costo="<?= htmlspecialchars($row['costo'] ?? '', ENT_QUOTES) ?>"
        data-observaciones="<?= htmlspecialchars($row['observaciones'] ?? '', ENT_QUOTES) ?>">
      <td><?= (int)$row['idmantenimiento'] ?></td>
      <td><strong><?= htmlspecialchars($row['codigo_activo'] ?: 'EQ-' . $row['idequipo']) ?></strong><small class="d-block text-muted"><?= htmlspecialchars($row['tipo_equipo'] . ' · ' . $row['nombreMarca'] . ' ' . $row['nombreModelo']) ?></small></td>
      <td><?= htmlspecialchars($row['tipo']) ?></td>
      <td><span class="badge app-badge-<?= MantenimientoEstado::badge($row['estado']) ?>"><?= htmlspecialchars($row['estado']) ?></span></td>
      <td><?= date('d/m/Y h:i A', strtotime($row['fecha_ingreso'])) ?></td>
      <td><?= htmlspecialchars($row['proveedor'] ?: 'Soporte interno') ?></td>
      <td><?= nl2br(htmlspecialchars($row['descripcion_problema'])) ?></td>
      <td><?= nl2br(htmlspecialchars($row['diagnostico'] ?: '—')) ?></td>
      <td><?= nl2br(htmlspecialchars($row['trabajo_realizado'] ?: '—')) ?></td>
      <td><?= $row['costo'] !== null ? 'L ' . number_format((float)$row['costo'], 2) : '—' ?></td>
      <td><?= $row['fecha_cierre'] ? date('d/m/Y h:i A', strtotime($row['fecha_cierre'])) : '—' ?></td>
      <td><?= htmlspecialchars($row['resultado'] ?: '—') ?></td>
      <td class="table-actions">
        <?php if ($abierto): ?>
        <a href="#" onclick="return editarMantenimiento(event)" data-bs-toggle="modal" data-bs-target="#editMantenimientoModal" title="Actualizar seguimiento"><i class="fa fa-edit" aria-hidden="true"></i></a>
        <a href="#" onclick="return cerrarMantenimiento(event)" data-bs-toggle="modal" data-bs-target="#closeMantenimientoModal" title="Cerrar mantenimiento"><i class="fa fa-circle-check" aria-hidden="true"></i></a>
        <a href="#" onclick="return cancelarMantenimiento(event)" data-bs-toggle="modal" data-bs-target="#cancelMantenimientoModal" title="Cancelar mantenimiento"><i class="fa fa-ban" aria-hidden="true"></i></a>
        <?php else: ?><span class="text-muted" aria-label="Historial cerrado">Historial</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
$(function () {
  $('#tablaMantenimientos').DataTable({
    dom: 'lrtip', order: [[4, 'desc']], autoWidth: false,
    responsive: { details: { type: 'inline', target: 'td:first-child' } },
    columnDefs: [
      { targets: 0, className: 'dtr-control', responsivePriority: 5 },
      { targets: 1, responsivePriority: 1 },
      { targets: 3, responsivePriority: 2 },
      { targets: 12, responsivePriority: 1, orderable: false }
    ]
  });
});

function datosMantenimiento(evento) {
  return $(evento.target).closest('tr');
}
function editarMantenimiento(evento) {
  var tr = datosMantenimiento(evento);
  $('#editMantenimientoId').val(tr.data('id'));
  $('#editMantenimientoProveedor').val(String(tr.data('idproveedor') || 0));
  $('#editMantenimientoTipo').val(tr.attr('data-tipo'));
  $('#editMantenimientoEstado').val(tr.attr('data-estado'));
  $('#editMantenimientoProblema').val(tr.attr('data-problema'));
  $('#editMantenimientoDiagnostico').val(tr.attr('data-diagnostico'));
  $('#editMantenimientoTrabajo').val(tr.attr('data-trabajo'));
  $('#editMantenimientoCosto').val(tr.attr('data-costo'));
  $('#editMantenimientoObservaciones').val(tr.attr('data-observaciones'));
  return true;
}
function cerrarMantenimiento(evento) {
  var tr = datosMantenimiento(evento);
  $('#closeMantenimientoId').val(tr.data('id'));
  $('#closeMantenimientoEquipo').text(tr.find('td').eq(1).text().trim());
  $('#closeMantenimientoDiagnostico').val(tr.attr('data-diagnostico'));
  $('#closeMantenimientoTrabajo').val(tr.attr('data-trabajo'));
  $('#closeMantenimientoCosto').val(tr.attr('data-costo'));
  $('#closeMantenimientoObservaciones').val(tr.attr('data-observaciones'));
  return true;
}
function cancelarMantenimiento(evento) {
  var tr = datosMantenimiento(evento);
  $('#cancelMantenimientoId').val(tr.data('id'));
  $('#cancelMantenimientoEquipo').text(tr.find('td').eq(1).text().trim());
  $('#cancelMantenimientoMotivo').val('');
  return true;
}
</script>
<?php else: ?>
<p class="lead"><em>No hay mantenimientos que coincidan con los filtros.</em></p>
<?php endif; ?>

<div class="modal fade" id="newMantenimientoModal" tabindex="-1" aria-labelledby="newMantenimientoTitulo" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form action="<?= BASE_URL ?>/mantenimientos.php" method="post">
      <?= Auth::csrfField() ?>
      <div class="modal-header"><h5 class="modal-title" id="newMantenimientoTitulo">Enviar equipo a mantenimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body"><div class="form-grid">
        <div class="form-group form-span-2"><label for="newMantenimientoEquipo">Equipo disponible</label><select name="idequipo" id="newMantenimientoEquipo" class="form-select" required><option value="">Seleccione un equipo</option><?php foreach ($equiposDisponibles as $equipo): ?><option value="<?= (int)$equipo['idequipo'] ?>" <?= $preseleccionarEquipo === (int)$equipo['idequipo'] ? 'selected' : '' ?>><?= htmlspecialchars(($equipo['codigo_activo'] ?: 'EQ-' . $equipo['idequipo']) . ' · ' . $equipo['tipo_equipo'] . ' · ' . $equipo['nombreMarca'] . ' ' . $equipo['nombreModelo']) ?></option><?php endforeach; ?></select><?php if (!$equiposDisponibles): ?><small class="text-danger">No hay equipos disponibles para enviar a mantenimiento.</small><?php endif; ?></div>
        <div class="form-group"><label for="newMantenimientoTipo">Tipo</label><select name="tipo" id="newMantenimientoTipo" class="form-select" required><?php foreach (MantenimientoEstado::tipos() as $valor => $etiqueta): ?><option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($etiqueta) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="newMantenimientoProveedor">Proveedor o taller</label><select name="idproveedor" id="newMantenimientoProveedor" class="form-select"><option value="0">Soporte interno</option><?php foreach ($proveedoresActivos as $proveedor): ?><option value="<?= (int)$proveedor['idproveedor'] ?>"><?= htmlspecialchars($proveedor['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group form-span-2"><label for="newMantenimientoProblema">Descripción del problema o trabajo preventivo</label><textarea name="descripcion_problema" id="newMantenimientoProblema" class="form-control" rows="3" maxlength="1000" required></textarea></div>
        <div class="form-group"><label for="newMantenimientoCosto">Costo inicial (opcional)</label><input type="number" name="costo" id="newMantenimientoCosto" class="form-control" min="0" step="0.01"></div>
        <div class="form-group"><label for="newMantenimientoObservaciones">Observaciones</label><textarea name="observaciones" id="newMantenimientoObservaciones" class="form-control" rows="2" maxlength="1000"></textarea></div>
      </div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="add" value="1" <?= !$equiposDisponibles ? 'disabled' : '' ?>>Abrir mantenimiento</button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="editMantenimientoModal" tabindex="-1" aria-labelledby="editMantenimientoTitulo" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form action="<?= BASE_URL ?>/mantenimientos.php" method="post">
      <?= Auth::csrfField() ?><input type="hidden" name="idmantenimiento" id="editMantenimientoId">
      <div class="modal-header"><h5 class="modal-title" id="editMantenimientoTitulo">Actualizar seguimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body"><div class="form-grid">
        <div class="form-group"><label for="editMantenimientoEstado">Estado</label><select name="estado" id="editMantenimientoEstado" class="form-select"><option value="Abierto">Abierto</option><option value="En proceso">En proceso</option></select></div>
        <div class="form-group"><label for="editMantenimientoTipo">Tipo</label><select name="tipo" id="editMantenimientoTipo" class="form-select"><?php foreach (MantenimientoEstado::tipos() as $valor => $etiqueta): ?><option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($etiqueta) ?></option><?php endforeach; ?></select></div>
        <div class="form-group form-span-2"><label for="editMantenimientoProveedor">Proveedor o taller</label><select name="idproveedor" id="editMantenimientoProveedor" class="form-select"><option value="0">Soporte interno</option><?php foreach ($proveedoresTodos as $proveedor): ?><option value="<?= (int)$proveedor['idproveedor'] ?>"><?= htmlspecialchars($proveedor['nombre']) ?><?= (int)$proveedor['activo'] === 0 ? ' (inactivo)' : '' ?></option><?php endforeach; ?></select></div>
        <div class="form-group form-span-2"><label for="editMantenimientoProblema">Problema</label><textarea name="descripcion_problema" id="editMantenimientoProblema" class="form-control" rows="3" maxlength="1000" required></textarea></div>
        <div class="form-group form-span-2"><label for="editMantenimientoDiagnostico">Diagnóstico</label><textarea name="diagnostico" id="editMantenimientoDiagnostico" class="form-control" rows="3" maxlength="1000"></textarea></div>
        <div class="form-group form-span-2"><label for="editMantenimientoTrabajo">Trabajo realizado</label><textarea name="trabajo_realizado" id="editMantenimientoTrabajo" class="form-control" rows="3" maxlength="1000"></textarea></div>
        <div class="form-group"><label for="editMantenimientoCosto">Costo (L)</label><input type="number" name="costo" id="editMantenimientoCosto" class="form-control" min="0" step="0.01"></div>
        <div class="form-group"><label for="editMantenimientoObservaciones">Observaciones</label><textarea name="observaciones" id="editMantenimientoObservaciones" class="form-control" rows="2" maxlength="1000"></textarea></div>
      </div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="save" value="1">Guardar seguimiento</button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="closeMantenimientoModal" tabindex="-1" aria-labelledby="closeMantenimientoTitulo" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form action="<?= BASE_URL ?>/mantenimientos.php" method="post">
      <?= Auth::csrfField() ?><input type="hidden" name="idmantenimiento" id="closeMantenimientoId">
      <div class="modal-header"><h5 class="modal-title" id="closeMantenimientoTitulo">Cerrar mantenimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body"><p>Equipo: <strong id="closeMantenimientoEquipo"></strong></p><div class="form-grid">
        <div class="form-group form-span-2"><label for="closeMantenimientoResultado">Resultado</label><select name="resultado" id="closeMantenimientoResultado" class="form-select" required><option value="">Seleccione</option><option value="Reparado">Reparado · volverá a Disponible</option><option value="No reparable">No reparable · quedará Dado de baja</option></select></div>
        <div class="form-group form-span-2"><label for="closeMantenimientoDiagnostico">Diagnóstico final</label><textarea name="diagnostico" id="closeMantenimientoDiagnostico" class="form-control" rows="3" maxlength="1000" required></textarea></div>
        <div class="form-group form-span-2"><label for="closeMantenimientoTrabajo">Trabajo realizado</label><textarea name="trabajo_realizado" id="closeMantenimientoTrabajo" class="form-control" rows="3" maxlength="1000"></textarea></div>
        <div class="form-group"><label for="closeMantenimientoCosto">Costo final (L)</label><input type="number" name="costo" id="closeMantenimientoCosto" class="form-control" min="0" step="0.01"></div>
        <div class="form-group"><label for="closeMantenimientoObservaciones">Observaciones</label><textarea name="observaciones" id="closeMantenimientoObservaciones" class="form-control" rows="2" maxlength="1000"></textarea></div>
      </div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success" name="close" value="1">Cerrar mantenimiento</button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="cancelMantenimientoModal" tabindex="-1" aria-labelledby="cancelMantenimientoTitulo" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/mantenimientos.php" method="post">
      <?= Auth::csrfField() ?><input type="hidden" name="idmantenimiento" id="cancelMantenimientoId">
      <div class="modal-header"><h5 class="modal-title" id="cancelMantenimientoTitulo">Cancelar mantenimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body"><p>Equipo: <strong id="cancelMantenimientoEquipo"></strong></p><label for="cancelMantenimientoMotivo">Motivo</label><textarea name="motivo_cancelacion" id="cancelMantenimientoMotivo" class="form-control" rows="3" maxlength="1000" required></textarea><small class="text-muted">El equipo recuperará su estado anterior y el historial permanecerá registrado.</small></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button><button type="submit" class="btn btn-warning" name="cancel" value="1">Cancelar mantenimiento</button></div>
    </form>
  </div></div>
</div>

<?php if ($preseleccionarEquipo > 0): ?>
<script>
setTimeout(function () {
  var modal = document.getElementById('newMantenimientoModal');
  if (modal && document.getElementById('newMantenimientoEquipo').value) {
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }
}, 0);
</script>
<?php endif; ?>
