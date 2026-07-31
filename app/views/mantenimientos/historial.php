<?php if ($rows): ?>
<p class="responsive-table-note"><i class="fa fa-circle-info" aria-hidden="true"></i> Pulsa la primera celda para consultar detalles ocultos en pantallas pequeñas.</p>
<table class="table table-bordered table-striped nowrap" id="<?= htmlspecialchars($tablaId, ENT_QUOTES) ?>">
  <thead><tr><th>#</th><th>Equipo</th><th>Tipo</th><th>Estado</th><th>Ingreso</th><th>Cierre</th><th>Proveedor</th><th>Problema</th><th>Diagnóstico</th><th>Trabajo</th><th>Resultado</th><th>Costo</th><th>Origen</th></tr></thead>
  <tbody><?php foreach ($rows as $row): ?><tr class="<?= $row['estado'] === MantenimientoEstado::CANCELADO ? 'text-muted' : '' ?>">
    <td><?= (int)$row['idmantenimiento'] ?></td>
    <td><strong><?= htmlspecialchars($row['codigo_activo'] ?: 'EQ-' . $row['idequipo']) ?></strong><small class="d-block text-muted"><?= htmlspecialchars($row['tipo_equipo'] . ' · ' . $row['nombreMarca'] . ' ' . $row['nombreModelo']) ?></small></td>
    <td><?= htmlspecialchars($row['tipo']) ?></td>
    <td><span class="badge app-badge-<?= MantenimientoEstado::badge($row['estado']) ?>"><?= htmlspecialchars($row['estado']) ?></span></td>
    <td><?= date('d/m/Y', strtotime($row['fecha_ingreso'])) ?></td>
    <td><?= $row['fecha_cierre'] ? date('d/m/Y', strtotime($row['fecha_cierre'])) : '—' ?></td>
    <td><?= htmlspecialchars($row['proveedor'] ?: 'Soporte interno') ?></td>
    <td><?= nl2br(htmlspecialchars($row['descripcion_problema'])) ?></td>
    <td><?= nl2br(htmlspecialchars($row['diagnostico'] ?: '—')) ?></td>
    <td><?= nl2br(htmlspecialchars($row['trabajo_realizado'] ?: '—')) ?></td>
    <td><?= htmlspecialchars($row['resultado'] ?: '—') ?></td>
    <td><?= $row['costo'] !== null ? 'L ' . number_format((float)$row['costo'], 2) : '—' ?></td>
    <td><?= htmlspecialchars($row['origen']) ?></td>
  </tr><?php endforeach; ?></tbody>
</table>
<script>
$(function(){
  $('#<?= addslashes($tablaId) ?>').DataTable({
    dom:'lrtip',order:[[4,'desc']],autoWidth:false,
    responsive:{details:{type:'inline',target:'td:first-child'}},
    columnDefs:[{targets:0,className:'dtr-control',responsivePriority:5},{targets:1,responsivePriority:1},{targets:3,responsivePriority:2}]
  });
});
</script>
<?php else: ?><p class="lead"><em>No hay mantenimientos que coincidan con los filtros.</em></p><?php endif; ?>
