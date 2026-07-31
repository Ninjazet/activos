<!-- MODAL DEVOLUCIÓN -->
<div class="modal fade" id="devolucionModal" tabindex="-1" role="dialog" aria-labelledby="returnAssignmentTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <form id="formDevolucion" method="post" action="<?= BASE_URL ?>/reportes/acta_devolucion.php" target="_blank">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="idasignacion" id="idasignacionDevolucion">
      <input type="hidden" name="firma_devolucion" id="firmaDevolucionInput">
      <div class="modal-header">
        <h5 class="modal-title" id="returnAssignmentTitle">Recibir y devolver equipo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p><strong>Empleado:</strong> <span id="lblEmpleadoDevolucion"></span></p>
        <p><strong>Equipo:</strong> <span id="lblEquipoDevolucion"></span></p>
        <div class="assignment-summary"><strong>Entregado originalmente:</strong> <span id="lblChecklistEntrega"></span></div>
        <div class="form-group">
          <label for="condicionDevolucion">Condición física al recibir</label>
          <select name="condicion_devolucion" id="condicionDevolucion" class="form-select" required>
            <option value="Bueno">Bueno - volverá a <?= EquipoEstado::nombre(EquipoEstado::DISPONIBLE) ?></option>
            <option value="Con daño">Con daño - pasará a <?= EquipoEstado::nombre(EquipoEstado::MANTENIMIENTO) ?></option>
            <option value="No funcional">No funcional - pasará a <?= EquipoEstado::nombre(EquipoEstado::MANTENIMIENTO) ?></option>
          </select>
        </div>
        <div class="form-group">
          <label>Accesorios recibidos</label>
          <div class="checklist-accesorios">
            <label><input type="checkbox" name="devolucion_cargador" id="devolucionCargador" value="1"> Cargador</label>
            <label><input type="checkbox" name="devolucion_maletin" id="devolucionMaletin" value="1"> Maletín</label>
          </div>
        </div>
        <div class="form-group">
          <label for="devolucionOtros">Otros accesorios recibidos</label>
          <input type="text" name="devolucion_otros" id="devolucionOtros" class="form-control" maxlength="255">
        </div>
        <div class="form-group">
          <label for="observacionesDevolucion">Observaciones de devolución</label>
          <textarea name="observaciones_devolucion" id="observacionesDevolucion" class="form-control" rows="3" maxlength="500" placeholder="Daños, accesorios faltantes o comentarios de recepción"></textarea>
        </div>
        <p class="text-muted" id="firmaDevolucionAyuda">Firma del responsable de IT que recibe el equipo. Puede firmar con mouse, pantalla táctil o teclas de flecha.</p>
        <div class="firma-lienzo"><canvas id="canvasFirmaDevolucion" width="440" height="160" tabindex="0" role="application" aria-label="Lienzo para la firma de devolución. Use el mouse, la pantalla táctil o las teclas de flecha" aria-describedby="firmaDevolucionAyuda"></canvas></div>
        <button type="button" class="btn btn-sm btn-secondary" id="btnLimpiarFirmaDevolucion" style="margin-top:6px;">
          <i class="fa fa-eraser"></i> Limpiar firma
        </button>
        <div id="avisoFirmaDevolucion" class="text-danger firma-aviso" role="status" aria-live="polite"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-warning"><i class="fa fa-file-pdf"></i> Confirmar y generar acta</button>
      </div>
    </form>
  </div></div>
</div>
