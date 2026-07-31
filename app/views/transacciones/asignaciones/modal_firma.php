<!-- MODAL FIRMAR ENTREGA -->
<div class="modal fade" id="firmarModal" tabindex="-1" role="dialog" aria-labelledby="signAssignmentTitle" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form id="formFirmaEntrega" method="post" action="<?= BASE_URL ?>/reportes/acta_asignacion.php" target="_blank">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="idasignacion" id="idasignacionFirma">
      <input type="hidden" name="firma" id="firmaEntregaInput">
      <div class="modal-header">
        <h5 class="modal-title" id="signAssignmentTitle">Firmar acta de entrega</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p><strong>Empleado:</strong> <span id="lblEmpleadoFirma"></span></p>
        <p><strong>Equipo:</strong> <span id="lblEquipoFirma"></span></p>
        <p class="text-muted" id="firmaEntregaAyuda">El empleado confirma el equipo, condición y accesorios registrados. Puede firmar con mouse, pantalla táctil o teclas de flecha.</p>
        <div class="firma-lienzo"><canvas id="canvasFirmaEntrega" width="440" height="160" tabindex="0" role="application" aria-label="Lienzo para la firma de entrega. Use el mouse, la pantalla táctil o las teclas de flecha" aria-describedby="firmaEntregaAyuda"></canvas></div>
        <button type="button" class="btn btn-sm btn-secondary" id="btnLimpiarFirmaEntrega" style="margin-top:6px;">
          <i class="fa fa-eraser"></i> Limpiar firma
        </button>
        <div id="avisoFirmaEntrega" class="text-danger firma-aviso" role="status" aria-live="polite"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success"><i class="fa fa-file-pdf"></i> Firmar y generar acta</button>
      </div>
    </form>
  </div></div>
</div>

