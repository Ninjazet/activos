<?php
// ============================================================
// GestActivos - AJAX: Tabla + modales de Asignación de Equipos
// Solo muestra asignaciones ACTIVAS (las devueltas quedan en
// el historial, visibles desde Consultas y Reportes).
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerir();

$db = Database::getInstance();

$sql = "SELECT asg.idasignacion,
               CONCAT(em.nombre, ' ', em.apellidos) AS empleado,
               CONCAT(ma.nombreMarca, ' ', mo.nombreModelo) AS equipo,
               asg.idempleado,
               asg.idequipo,
               asg.fecha_asignacion
        FROM asignacion asg
        INNER JOIN empleados em ON asg.idempleado = em.idempleado
        INNER JOIN equipo eq    ON asg.idequipo   = eq.idequipo
        INNER JOIN marca ma     ON eq.idmarca_equipo  = ma.idmarca
        INNER JOIN modelo mo    ON eq.idmodelo_equipo = mo.idmodelo
        WHERE asg.activa = 1";

$params = [];
$q = trim($_POST['query'] ?? '');
if ($q !== '') {
    $sql .= " AND (CONCAT(em.nombre, ' ', em.apellidos) LIKE ?
              OR CONCAT(ma.nombreMarca, ' ', mo.nombreModelo) LIKE ?
              OR asg.idasignacion LIKE ?)";
    $params = ["%$q%", "%$q%", "%$q%"];
}
$sql .= " ORDER BY asg.fecha_asignacion DESC";

$resultado = $db->consulta($sql, $params);

// Empleados activos (un empleado puede tener varios equipos a la vez)
$emps = $db->consulta("SELECT idempleado, nombre, apellidos FROM empleados WHERE activo=1 ORDER BY nombre");

// Equipos activos y SIN asignación vigente (para el modal de Nueva asignación)
$eqsDisponibles = $db->consulta(
    "SELECT eq.idequipo, ma.nombreMarca, mo.nombreModelo
     FROM equipo eq
     INNER JOIN marca  ma ON eq.idmarca_equipo  = ma.idmarca
     INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo
     WHERE eq.activo = 1
       AND eq.idequipo NOT IN (SELECT idequipo FROM asignacion WHERE activa = 1)
     ORDER BY ma.nombreMarca"
);

// Todos los equipos activos (para el modal de Editar: incluye el que ya tiene esa asignación)
$eqsTodos = $db->consulta(
    "SELECT eq.idequipo, ma.nombreMarca, mo.nombreModelo
     FROM equipo eq
     INNER JOIN marca  ma ON eq.idmarca_equipo  = ma.idmarca
     INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo
     WHERE eq.activo = 1
     ORDER BY ma.nombreMarca"
);
?>

<?php if (count($resultado) > 0): ?>
<table class="table table-bordered table-striped" id="datosE">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th>ID</th>
            <th>Empleado</th>
            <th>Equipo</th>
            <th>Asignado desde</th>
            <th>idempleado</th>
            <th>idequipo</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resultado as $r): ?>
        <tr>
            <td><?= $r['idasignacion'] ?></td>
            <td><?= htmlspecialchars($r['empleado']) ?></td>
            <td><?= htmlspecialchars($r['equipo']) ?></td>
            <td><?= $r['fecha_asignacion'] ? date('d/m/Y', strtotime($r['fecha_asignacion'])) : '—' ?></td>
            <td style="display:none"><?= $r['idempleado'] ?></td>
            <td style="display:none"><?= $r['idequipo'] ?></td>
            <td>
                <a href="#" title="Editar"
                   onclick="return modalEdit(event);"
                   data-toggle="modal" data-target="#editModal">
                    <span class="fa fa-edit"></span>
                </a>
                <a href="#" title="Devolver equipo"
                   onclick="return modalDelete(event);"
                   data-toggle="modal" data-target="#deleteModal">
                    <span class="fa fa-undo" style="color:#e88e14"></span>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
$(document).ready(function () {
    $("#datosE").DataTable({ dom: 'lrtip' });
    $("#datosE th:nth-child(5), #datosE td:nth-child(5)").hide();
    $("#datosE th:nth-child(6), #datosE td:nth-child(6)").hide();
});

function modalEdit(evento) {
    var fila = $(evento.target).parents("tr");
    var id         = fila.find("td").eq(0).text();
    var idempleado = fila.find("td").eq(4).text();
    var idequipo   = fila.find("td").eq(5).text();

    $("#idasignacion").val(id);
    $("#empleadoAct option").each(function () {
        $(this).prop("selected", $(this).val() == idempleado);
    });
    $("#equipoAct option").each(function () {
        $(this).prop("selected", $(this).val() == idequipo);
    });
}

function modalDelete(evento) {
    var fila = $(evento.target).parents("tr");
    var id       = fila.find("td").eq(0).text();
    var empleado = fila.find("td").eq(1).text();
    var equipo   = fila.find("td").eq(2).text();

    $("#idAsignacionDel").val(id);
    $("#lblAsignacion").text(id);
    $("#lblEmpleadoDel").text(empleado);
    $("#lblEquipoDel").text(equipo);
}
</script>
<?php else: ?>
<p class="lead"><em>No hay asignaciones activas.</em></p>
<?php endif; ?>

<!-- MODAL NUEVO -->
<div class="modal fade" id="newModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/asignarequipo.php" method="post">
                <?= Auth::csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Asignación</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Empleado:</label>
                        <select name="empleado" id="empleado" class="form-control" required>
                            <option value="0">-- Seleccione un empleado --</option>
                            <?php foreach ($emps as $e): ?>
                            <option value="<?= $e['idempleado'] ?>">
                                <?= htmlspecialchars($e['nombre'] . ' ' . $e['apellidos']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Equipo:</label>
                        <select name="equipo" id="equipo" class="form-control" required>
                            <option value="0">-- Seleccione un equipo --</option>
                            <?php foreach ($eqsDisponibles as $eq): ?>
                            <option value="<?= $eq['idequipo'] ?>">
                                <?= htmlspecialchars($eq['nombreMarca'] . ' ' . $eq['nombreModelo']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Solo se muestran equipos activos y sin asignar.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <input type="submit" class="btn btn-success" value="Guardar" name="add">
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/asignarequipo.php" method="post">
                <input type="hidden" name="idasignacion" id="idasignacion">
                <?= Auth::csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Editar Asignación</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Empleado:</label>
                        <select name="empleado" id="empleadoAct" class="form-control" required>
                            <option value="0">-- Seleccione un empleado --</option>
                            <?php foreach ($emps as $e): ?>
                            <option value="<?= $e['idempleado'] ?>">
                                <?= htmlspecialchars($e['nombre'] . ' ' . $e['apellidos']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Equipo:</label>
                        <select name="equipo" id="equipoAct" class="form-control" required>
                            <option value="0">-- Seleccione un equipo --</option>
                            <?php foreach ($eqsTodos as $eq): ?>
                            <option value="<?= $eq['idequipo'] ?>">
                                <?= htmlspecialchars($eq['nombreMarca'] . ' ' . $eq['nombreModelo']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <input type="submit" class="btn btn-primary" value="Actualizar" name="edit">
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DEVOLVER EQUIPO -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/asignarequipo.php" method="post">
                <input type="hidden" name="idAsignacionDel" id="idAsignacionDel">
                <?= Auth::csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Devolver Equipo</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>¿Confirmas que <strong><span id="lblEmpleadoDel"></span></strong> devuelve el equipo
                       <strong><span id="lblEquipoDel"></span></strong>?</p>
                    <p><small class="text-muted">La asignación quedará registrada en el historial con la fecha de hoy.</small></p>
                    <p class="d-none"><strong>ID:</strong> <span id="lblAsignacion"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <input type="submit" class="btn btn-warning" value="Confirmar devolución" name="del">
                </div>
            </form>
        </div>
    </div>
</div>
