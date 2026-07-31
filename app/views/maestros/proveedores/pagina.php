<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/maestros/proveedores.php');
</script>

<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <div class="module-header-copy">
        <h2>Proveedores</h2>
        <p>Contactos comerciales vinculados con la compra y soporte de los activos.</p>
      </div>
      <div class="page-header-actions">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProveedorModal">
          <i class="fa fa-plus" aria-hidden="true"></i> Agregar proveedor
        </button>
      </div>
    </div>

    <?php renderTableFilters([
      'search_label' => 'Buscar proveedores',
      'search_placeholder' => 'Nombre, RTN, contacto, correo o teléfono',
      'table_id' => 'tablaProveedores',
      'filters' => [
        ['name' => 'activo', 'label' => 'Estado', 'options' => [1 => 'Activo', 0 => 'Inactivo']],
      ],
    ]); ?>
    <div id="datos" aria-live="polite"></div>
  </div>
</div>
