<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <div class="module-header-copy">
        <a href="<?= BASE_URL ?>/proveedores.php" class="small"><i class="fa fa-arrow-left" aria-hidden="true"></i> Volver a proveedores</a>
        <h2><?= htmlspecialchars($proveedor['nombre']) ?></h2>
        <p>Ficha comercial y activos adquiridos.</p>
      </div>
      <span class="badge app-badge-<?= (int)$proveedor['activo'] === 1 ? 'success' : 'muted' ?>">
        <?= (int)$proveedor['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
      </span>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">Equipos relacionados</small><h3><?= (int)$proveedor['equipos'] ?></h3></div></div></div>
      <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">Total registrado en compras</small><h3>L <?= number_format((float)$proveedor['total_compras'], 2) ?></h3></div></div></div>
      <div class="col-md-4"><div class="card h-100"><div class="card-body"><small class="text-muted">RTN</small><h3 class="h5"><?= htmlspecialchars($proveedor['rtn'] ?: 'No registrado') ?></h3></div></div></div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h3 class="h5">Contacto</h3><dl class="row mb-0">
        <dt class="col-sm-4">Persona</dt><dd class="col-sm-8"><?= htmlspecialchars($proveedor['contacto'] ?: '—') ?></dd>
        <dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8"><?= htmlspecialchars($proveedor['telefono'] ?: '—') ?></dd>
        <dt class="col-sm-4">Correo</dt><dd class="col-sm-8"><?= htmlspecialchars($proveedor['correo'] ?: '—') ?></dd>
      </dl></div></div></div>
      <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h3 class="h5">Ubicación y notas</h3><p><?= nl2br(htmlspecialchars($proveedor['direccion'] ?: 'Sin dirección registrada')) ?></p><p class="text-muted mb-0"><?= nl2br(htmlspecialchars($proveedor['observaciones'] ?: 'Sin observaciones')) ?></p></div></div></div>
    </div>

    <div class="card"><div class="card-body">
      <h3 class="h5">Equipos comprados a este proveedor</h3>
      <?php if ($equipos): ?>
      <div class="table-responsive"><table class="table table-striped table-bordered" id="equiposProveedor"><thead><tr><th>Código</th><th>Equipo</th><th>Serie</th><th>Compra</th><th>Factura</th><th>Costo</th><th>Estado</th></tr></thead><tbody>
      <?php foreach ($equipos as $equipo): ?>
        <tr class="<?= (int)$equipo['activo'] === 1 ? '' : 'text-muted' ?>">
          <td><strong><?= htmlspecialchars($equipo['codigo_activo'] ?: 'EQ-' . $equipo['idequipo']) ?></strong></td>
          <td><?= htmlspecialchars($equipo['tipo_equipo'] . ' · ' . $equipo['nombreMarca'] . ' ' . $equipo['nombreModelo']) ?></td>
          <td><?= htmlspecialchars($equipo['numero_serie'] ?: '—') ?></td>
          <td><?= $equipo['fecha_compra'] ? date('d/m/Y', strtotime($equipo['fecha_compra'])) : '—' ?></td>
          <td><?= htmlspecialchars($equipo['factura'] ?: '—') ?></td>
          <td><?= $equipo['costo'] !== null ? 'L ' . number_format((float)$equipo['costo'], 2) : '—' ?></td>
          <td><?= htmlspecialchars(EquipoEstado::nombre((int)$equipo['estado_equipo'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
      <script>$(function(){ $('#equiposProveedor').DataTable({dom:'lrtip',order:[[3,'desc']]}); });</script>
      <?php else: ?><p class="text-muted mb-0">Todavía no hay equipos relacionados.</p><?php endif; ?>
    </div></div>
  </div>
</div>
