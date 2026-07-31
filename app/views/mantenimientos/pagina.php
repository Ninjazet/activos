<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
const mantenimientoAjaxUrl = '<?= BASE_URL ?>/app/ajax/transacciones/mantenimientos.php';
let aplicarPreseleccionMantenimiento = true;
initAjaxTableFilters(mantenimientoAjaxUrl, function () {
  if (!aplicarPreseleccionMantenimiento) return {};
  aplicarPreseleccionMantenimiento = false;
  return { preseleccionar_equipo: <?= json_encode($preseleccionarEquipo) ?> };
});
</script>

<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <div class="module-header-copy">
        <h2>Mantenimientos</h2>
        <p>Seguimiento de diagnósticos, reparaciones, costos y resultado final de cada activo.</p>
      </div>
      <div class="page-header-actions">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMantenimientoModal">
          <i class="fa fa-screwdriver-wrench" aria-hidden="true"></i> Enviar a mantenimiento
        </button>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Abiertos</small><h3 class="mb-0 text-warning"><?= (int)$metricas['abiertos'] ?></h3></div></div></div>
      <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><small class="text-muted">En proceso</small><h3 class="mb-0 text-primary"><?= (int)$metricas['en_proceso'] ?></h3></div></div></div>
      <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Cerrados este mes</small><h3 class="mb-0 text-success"><?= (int)$metricas['cerrados_mes'] ?></h3></div></div></div>
      <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Costo del mes</small><h3 class="h5 mb-0">L <?= number_format((float)$metricas['costo_mes'], 2) ?></h3></div></div></div>
    </div>

    <?php renderTableFilters([
      'search_label' => 'Buscar mantenimientos',
      'search_placeholder' => 'Código, serie, equipo, problema o proveedor',
      'table_id' => 'tablaMantenimientos',
      'filters' => [
        ['name' => 'tipo', 'label' => 'Tipo', 'options' => MantenimientoEstado::tipos()],
        ['name' => 'estado', 'label' => 'Estado', 'options' => MantenimientoEstado::estados()],
        ['name' => 'idproveedor', 'label' => 'Proveedor', 'options' => $proveedoresFiltro],
        ['name' => 'fecha_desde', 'label' => 'Ingreso desde', 'type' => 'date'],
        ['name' => 'fecha_hasta', 'label' => 'Ingreso hasta', 'type' => 'date'],
      ],
    ]); ?>
    <div id="datos" aria-live="polite"></div>
  </div>
</div>
