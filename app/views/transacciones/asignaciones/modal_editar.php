<!-- MODAL EDITAR ASIGNACIÓN NO FIRMADA -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editAssignmentTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <form action="<?= BASE_URL ?>/asignarequipo.php" method="post">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="idasignacion" id="idasignacion">
      <div class="modal-header">
        <h5 class="modal-title" id="editAssignmentTitle">Editar asignación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info" style="padding:9px 12px;">Solo pueden editarse asignaciones que todavía no tienen acta firmada.</div>
        <div class="form-group">
          <label for="empleadoAct">Empleado</label>
          <select name="empleado" id="empleadoAct" class="form-select" required>
            <option value="0">-- Seleccione un empleado --</option>
            <?php foreach ($emps as $e): ?>
            <option value="<?= (int)$e['idempleado'] ?>"><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellidos']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="equipoAct">Equipo</label>
          <select name="equipo" id="equipoAct" class="form-select" required>
            <option value="0">-- Seleccione un equipo --</option>
            <?php foreach ($eqsTodos as $eq): ?>
            <option value="<?= (int)$eq['idequipo'] ?>">
              <?= htmlspecialchars(($eq['codigo_activo'] ?: ('EQ-' . $eq['idequipo'])) . ' - ' . $eq['nombreMarca'] . ' ' . $eq['nombreModelo']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php $prefijoEntrega = 'editar'; require __DIR__ . '/parcial_checklist_entrega.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" name="edit">Actualizar</button>
      </div>
    </form>
  </div></div>
</div>

