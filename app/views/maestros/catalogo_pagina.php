<script src="<?= BASE_URL ?>/public/js/ajax-loader.js?v=<?= @filemtime(BASE_PATH . '/public/js/ajax-loader.js') ?: APP_VERSION ?>"></script>
<script>
$(function () {
    ajaxLoad('<?= BASE_URL ?>/app/ajax/maestros/<?= $config['ajax'] ?>');
});
$(document).on('keyup', '#buscar', function () {
    ajaxLoadDebounced(
        '<?= BASE_URL ?>/app/ajax/maestros/<?= $config['ajax'] ?>',
        $(this).val()
    );
});
</script>
<div class="wrapper">
  <div class="container-fluid">
    <div class="page-header clearfix">
      <h2><?= htmlspecialchars($config['plural']) ?></h2>
      <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newModal">+ Agregar</a>
    </div>
    <div class="form-group">
      <label for="buscar" class="visually-hidden">Buscar <?= htmlspecialchars(strtolower($config['plural'])) ?></label>
      <input type="text" id="buscar" class="form-control" placeholder="Buscar...">
      <br><div id="datos"></div>
    </div>
  </div>
</div>
