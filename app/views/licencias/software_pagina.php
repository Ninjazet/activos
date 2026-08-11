<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>initAjaxTableFilters('<?= BASE_URL ?>/app/ajax/maestros/software.php');</script>

<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header">
      <div class="module-header-copy">
        <h2>Catálogo de software</h2>
        <p>Productos, fabricantes, versiones y ediciones disponibles para registrar licencias.</p>
      </div>
      <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/licencias.php" class="btn btn-light"><i class="fa fa-key" aria-hidden="true"></i> Ver licencias</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSoftwareModal">
          <i class="fa fa-plus" aria-hidden="true"></i> Agregar software
        </button>
      </div>
    </div>

    <?php renderTableFilters([
      'search_label' => 'Buscar software',
      'search_placeholder' => 'Producto, fabricante, versión, edición o categoría',
      'table_id' => 'tablaSoftware',
      'filters' => [
        ['name' => 'categoria', 'label' => 'Categoría', 'options' => array_combine($categorias, $categorias) ?: []],
        ['name' => 'activo', 'label' => 'Estado', 'options' => [1 => 'Activo', 0 => 'Inactivo']],
      ],
    ]); ?>
    <div id="datos" aria-live="polite"></div>
  </div>
</div>
