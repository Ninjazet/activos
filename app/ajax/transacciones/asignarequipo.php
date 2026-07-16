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
               asg.fecha_asignacion,
               asg.firma
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
                <?php if (!empty($r['firma'])): ?>
                <a href="<?= BASE_URL ?>/reportes/acta_asignacion.php?idasignacion=<?= $r['idasignacion'] ?>"
                   target="_blank" title="Ver Acta Firmada">
                    <span class="fa fa-file-circle-check" style="color:#1a7c3e"></span>
                </a>
                <?php else: ?>
                <a href="#" title="Firmar Acta"
                   onclick="return modalFirmar(event);"
                   data-toggle="modal" data-target="#firmarModal">
                    <span class="fa fa-file-signature" style="color:#22648e"></span>
                </a>
                <?php endif; ?>
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

// ====================
// Firmar Acta (canvas)
// ====================
var dibujandoFirma = false;
var hayTrazoFirma  = false;
var canvasFirma, ctxFirma;

function modalFirmar(evento) {
    var fila = $(evento.target).parents("tr");
    var id       = fila.find("td").eq(0).text();
    var empleado = fila.find("td").eq(1).text();
    var equipo   = fila.find("td").eq(2).text();

    $("#idasignacionFirma").val(id);
    $("#lblEmpleadoFirma").text(empleado);
    $("#lblEquipoFirma").text(equipo);
    $("#avisoFirma").text("");

    hayTrazoFirma = false;
    if (ctxFirma) {
        ctxFirma.fillStyle = "#ffffff";
        ctxFirma.fillRect(0, 0, canvasFirma.width, canvasFirma.height);
    }
}

$(document).ready(function () {

    canvasFirma = document.getElementById("canvasFirma");
    ctxFirma    = canvasFirma.getContext("2d");

    function posicionFirma(e) {
        var r = canvasFirma.getBoundingClientRect();
        if (e.touches && e.touches[0]) {
            return { x: e.touches[0].clientX - r.left, y: e.touches[0].clientY - r.top };
        }
        return { x: e.clientX - r.left, y: e.clientY - r.top };
    }

    function iniciarFirma(e) {
        e.preventDefault();
        dibujandoFirma = true;
        hayTrazoFirma  = true;
        var p = posicionFirma(e);
        ctxFirma.beginPath();
        ctxFirma.moveTo(p.x, p.y);
    }

    function trazarFirma(e) {
        if (!dibujandoFirma) return;
        e.preventDefault();
        var p = posicionFirma(e);
        ctxFirma.lineWidth   = 2;
        ctxFirma.lineCap     = "round";
        ctxFirma.strokeStyle = "#1e1e2d";
        ctxFirma.lineTo(p.x, p.y);
        ctxFirma.stroke();
    }

    function terminarFirma() {
        dibujandoFirma = false;
    }

    canvasFirma.addEventListener("mousedown",  iniciarFirma);
    canvasFirma.addEventListener("mousemove",  trazarFirma);
    window.addEventListener("mouseup",         terminarFirma);
    canvasFirma.addEventListener("touchstart", iniciarFirma);
    canvasFirma.addEventListener("touchmove",  trazarFirma);
    canvasFirma.addEventListener("touchend",   terminarFirma);

    $("#btnLimpiarFirma").on("click", function () {
        ctxFirma.fillStyle = "#ffffff";
        ctxFirma.fillRect(0, 0, canvasFirma.width, canvasFirma.height);
        hayTrazoFirma = false;
    });

    $("#formFirma").on("submit", function (e) {
        if (!hayTrazoFirma) {
            e.preventDefault();
            $("#avisoFirma").text("Debe dibujar su firma antes de continuar.");
            return;
        }
        $("#firmaInput").val(canvasFirma.toDataURL("image/jpeg"));
    });

});
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

<!-- MODAL FIRMAR ACTA -->
<div class="modal fade" id="firmarModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formFirma" method="POST"
                  action="<?= BASE_URL ?>/reportes/acta_asignacion.php" target="_blank">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="idasignacion" id="idasignacionFirma">
                <input type="hidden" name="firma"        id="firmaInput">
                <div class="modal-header">
                    <h5 class="modal-title">Firmar Acta de Entrega</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p><strong>Empleado:</strong> <span id="lblEmpleadoFirma"></span></p>
                    <p><strong>Equipo:</strong>   <span id="lblEquipoFirma"></span></p>
                    <p class="text-muted" style="margin-bottom:6px;">Dibuje su firma en el recuadro:</p>
                    <div style="border:2px solid #d8d8e8; border-radius:8px; display:inline-block; background:#fff; overflow:hidden;">
                        <canvas id="canvasFirma" width="440" height="160"
                                style="display:block; touch-action:none; cursor:crosshair;"></canvas>
                    </div>
                    <br>
                    <button type="button" class="btn btn-sm btn-default" id="btnLimpiarFirma"
                            style="margin-top:6px;">
                        <i class="fa fa-eraser"></i> Limpiar Firma
                    </button>
                    <div id="avisoFirma" class="text-danger" style="min-height:20px; margin-top:6px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-file-pdf"></i> Generar Acta Firmada
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
