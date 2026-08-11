<?php
$filtroSoftware = [];
foreach ($software as $producto) {
    $version = trim(($producto['version'] ?: '') . ' ' . ($producto['edicion'] ?: ''));
    $filtroSoftware[$producto['idsoftware']] = $producto['fabricante'] . ' · ' . $producto['nombre'] . ($version !== '' ? ' · ' . $version : '');
}
$filtroProveedores = [];
foreach ($proveedores as $proveedor) {
    $filtroProveedores[$proveedor['idproveedor']] = $proveedor['nombre'];
}
$haySoftwareActivo = count(array_filter($software, static fn(array $item): bool => (int)$item['activo'] === 1)) > 0;
?>
<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/transacciones/licencias.php');</script>

<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <div class="module-header-copy"><h2>Licencias de software</h2><p>Compras, vigencias, titulares y disponibilidad del licenciamiento.</p></div>
      <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/software.php" class="btn btn-light"><i class="fa fa-box" aria-hidden="true"></i> Catálogo de software</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLicenciaModal" <?= $haySoftwareActivo ? '' : 'disabled' ?>><i class="fa fa-plus" aria-hidden="true"></i> Registrar licencia</button>
      </div>
    </div>

    <?php if (!$haySoftwareActivo): ?>
      <div class="alert alert-info">Primero agrega o reactiva un producto en el <a href="<?= BASE_URL ?>/software.php" class="alert-link">catálogo de software</a>.</div>
    <?php elseif (!SecretoLicencia::disponible()): ?>
      <div class="alert alert-warning"><strong>Claves de producto deshabilitadas:</strong> puedes registrar licencias, pero para guardar una clave debes configurar <code>APP_ENCRYPTION_KEY</code> en esta instalación.</div>
    <?php endif; ?>

    <section class="dashboard-metrics mb-4" aria-label="Resumen de licencias">
      <article class="metric-card"><div><span>Registradas</span><strong><?= (int)$metricasResumen['total'] ?></strong><small>Historial completo</small></div></article>
      <article class="metric-card"><div><span>Activas</span><strong><?= (int)$metricasResumen['activas'] ?></strong><small>Habilitadas para uso</small></div></article>
      <article class="metric-card"><div><span>Próximas a vencer</span><strong><?= (int)$metricasResumen['proximas'] ?></strong><small>Durante los siguientes 30 días</small></div></article>
      <article class="metric-card"><div><span>Vencidas</span><strong><?= (int)$metricasResumen['vencidas'] ?></strong><small>Requieren revisión</small></div></article>
    </section>

    <?php renderTableFilters([
      'search_label' => 'Buscar licencias',
      'search_placeholder' => 'Código, software, fabricante, proveedor, factura o titular',
      'table_id' => 'tablaLicencias',
      'filters' => [
        ['name' => 'estado', 'label' => 'Estado', 'options' => ['activa'=>'Activas','vigente'=>'Vigentes','proxima'=>'Próximas a vencer','vencida'=>'Vencidas','inactiva'=>'Inactivas']],
        ['name' => 'modalidad', 'label' => 'Modalidad', 'options' => LicenciaEstado::modalidades()],
        ['name' => 'metrica', 'label' => 'Métrica', 'options' => LicenciaEstado::metricas()],
        ['name' => 'idsoftware', 'label' => 'Software', 'options' => $filtroSoftware],
        ['name' => 'idproveedor', 'label' => 'Proveedor', 'options' => $filtroProveedores],
      ],
    ]); ?>
    <div id="datos" aria-live="polite"></div>
  </div>
</div>
