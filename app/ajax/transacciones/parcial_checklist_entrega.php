<?php
// Se incluye dentro de los formularios Nueva/Editar asignación.
$prefijoEntrega = $prefijoEntrega === 'editar' ? 'editar' : 'nueva';
$idCondicion = $prefijoEntrega . 'CondicionEntrega';
$idCargador = $prefijoEntrega . 'EntregaCargador';
$idMaletin = $prefijoEntrega . 'EntregaMaletin';
$idOtros = $prefijoEntrega . 'EntregaOtros';
$idObservaciones = $prefijoEntrega . 'ObservacionesEntrega';
?>
<fieldset class="checklist-entrega">
  <legend><i class="fa fa-clipboard-check"></i> Checklist de entrega</legend>
  <div class="form-group">
    <label for="<?= $idCondicion ?>">Condición física del equipo</label>
    <select name="condicion_entrega" id="<?= $idCondicion ?>" class="form-control" required>
      <option value="Nuevo">Nuevo</option>
      <option value="Excelente">Excelente</option>
      <option value="Bueno" selected>Bueno</option>
      <option value="Regular">Regular</option>
    </select>
  </div>
  <div class="form-group">
    <label>Accesorios entregados</label>
    <div class="checklist-accesorios">
      <label><input type="checkbox" name="entrega_cargador" id="<?= $idCargador ?>" value="1"> Cargador</label>
      <label><input type="checkbox" name="entrega_maletin" id="<?= $idMaletin ?>" value="1"> Maletín</label>
    </div>
  </div>
  <div class="form-group">
    <label for="<?= $idOtros ?>">Otros accesorios</label>
    <input type="text" name="entrega_otros" id="<?= $idOtros ?>" class="form-control"
           maxlength="255" placeholder="Ej. mouse, cable HDMI, adaptador">
  </div>
  <div class="form-group">
    <label for="<?= $idObservaciones ?>">Observaciones de entrega</label>
    <textarea name="observaciones_entrega" id="<?= $idObservaciones ?>" class="form-control"
              rows="2" maxlength="500" placeholder="Estado visible o detalles relevantes"></textarea>
  </div>
</fieldset>
