<!-- MODAL NUEVA ASIGNACIÓN -->
<div class="modal fade" id="newModal" tabindex="-1" role="dialog" aria-labelledby="newAssignmentTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <form action="<?= BASE_URL ?>/asignarequipo.php" method="post">
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="newAssignmentTitle">Nueva asignación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="empleado">Empleado</label>
          <select name="empleado" id="empleado" class="form-select" required>
            <option value="0">-- Seleccione un empleado --</option>
            <?php foreach ($emps as $e): ?>
            <option value="<?= (int)$e['idempleado'] ?>" <?= $empleadoPreseleccionable && (int)$e['idempleado'] === $preseleccionarEmpleado ? 'selected' : '' ?>>
              <?= htmlspecialchars($e['nombre'] . ' ' . $e['apellidos']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="equipo">Equipo disponible</label>
          <select name="equipo" id="equipo" class="form-select" required>
            <option value="0">-- Seleccione un equipo --</option>
            <?php foreach ($eqsDisponibles as $eq): ?>
            <option value="<?= (int)$eq['idequipo'] ?>" <?= $equipoPreseleccionable && (int)$eq['idequipo'] === $preseleccionarEquipo ? 'selected' : '' ?>>
              <?= htmlspecialchars(($eq['codigo_activo'] ?: ('EQ-' . $eq['idequipo'])) . ' - ' . $eq['nombreMarca'] . ' ' . $eq['nombreModelo']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Solo se muestran equipos activos y disponibles.</small>
        </div>
        <?php $prefijoEntrega = 'nueva'; require __DIR__ . '/parcial_checklist_entrega.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success" name="add"><i class="fa fa-check"></i> Crear asignación</button>
      </div>
    </form>
  </div></div>
</div>

