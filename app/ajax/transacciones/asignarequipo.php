<?php
// ============================================================
// GestActivos - Asignaciones activas, entrega y devolución
// ============================================================
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('transacciones');

$db = Database::getInstance();
$q = is_string($_POST['query'] ?? null) ? trim($_POST['query']) : '';
$preseleccionarEmpleado = max(0, (int)($_POST['preseleccionar_empleado'] ?? 0));
$preseleccionarEquipo = max(0, (int)($_POST['preseleccionar_equipo'] ?? 0));

$sql = "SELECT asg.idasignacion, asg.idempleado, asg.idequipo, asg.fecha_asignacion,
               asg.condicion_entrega, asg.entrega_cargador, asg.entrega_maletin,
               asg.entrega_otros, asg.observaciones_entrega, asg.firma, asg.requiere_firma_entrega,
               CONCAT(em.nombre, ' ', em.apellidos) AS empleado,
               CONCAT(COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)), ' - ', ma.nombreMarca, ' ', mo.nombreModelo) AS equipo
        FROM asignacion asg
        INNER JOIN empleados em ON asg.idempleado = em.idempleado
        INNER JOIN equipo eq ON asg.idequipo = eq.idequipo
        INNER JOIN marca ma ON eq.idmarca_equipo = ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo
        WHERE asg.activa = 1";
$params = [];
if ($q !== '') {
    $sql .= " AND (CONCAT(em.nombre, ' ', em.apellidos) LIKE ?
              OR CONCAT(ma.nombreMarca, ' ', mo.nombreModelo) LIKE ?
              OR eq.codigo_activo LIKE ? OR asg.idasignacion LIKE ?)";
    $like = "%$q%";
    $params = [$like, $like, $like, $like];
}
$sql .= " ORDER BY asg.fecha_asignacion DESC, asg.idasignacion DESC";
$resultado = $db->consulta($sql, $params);

$emps = $db->consulta(
    "SELECT idempleado, nombre, apellidos FROM empleados WHERE activo=1 ORDER BY nombre, apellidos"
);
$eqsDisponibles = $db->consulta(
    "SELECT eq.idequipo, eq.codigo_activo, ma.nombreMarca, mo.nombreModelo
     FROM equipo eq
     INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
     INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo
     WHERE eq.activo=1 AND eq.estado_equipo=1
       AND NOT EXISTS (SELECT 1 FROM asignacion asg WHERE asg.idequipo=eq.idequipo AND asg.activa=1)
     ORDER BY eq.codigo_activo, ma.nombreMarca, mo.nombreModelo"
);
$eqsTodos = $db->consulta(
    "SELECT eq.idequipo, eq.codigo_activo, ma.nombreMarca, mo.nombreModelo
     FROM equipo eq
     INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
     INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo
     WHERE eq.activo=1 AND eq.estado_equipo IN (1,2)
     ORDER BY eq.codigo_activo, ma.nombreMarca, mo.nombreModelo"
);

$empleadoPreseleccionable = false;
foreach ($emps as $empleadoOpcion) {
    if ((int)$empleadoOpcion['idempleado'] === $preseleccionarEmpleado) {
        $empleadoPreseleccionable = true;
        break;
    }
}
$equipoPreseleccionable = false;
foreach ($eqsDisponibles as $equipoOpcion) {
    if ((int)$equipoOpcion['idequipo'] === $preseleccionarEquipo) {
        $equipoPreseleccionable = true;
        break;
    }
}
$abrirModalNuevo = $empleadoPreseleccionable || $equipoPreseleccionable;
?>

<?php if ($resultado): ?>
<table class="table table-bordered table-striped" id="datosE">
    <thead>
        <tr>
            <th>ID</th><th>Empleado</th><th>Equipo</th><th>Condición entrega</th>
            <th>Accesorios</th><th>Asignado desde</th><th>Acción</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <?php
        $accesorios = [];
        if ((int)$r['entrega_cargador'] === 1) { $accesorios[] = 'Cargador'; }
        if ((int)$r['entrega_maletin'] === 1) { $accesorios[] = 'Maletín'; }
        if (!empty($r['entrega_otros'])) { $accesorios[] = $r['entrega_otros']; }
        $textoAccesorios = $accesorios ? implode(', ', $accesorios) : 'Sin accesorios';
        ?>
        <tr data-idempleado="<?= (int)$r['idempleado'] ?>"
            data-idequipo="<?= (int)$r['idequipo'] ?>"
            data-condicion-entrega="<?= htmlspecialchars($r['condicion_entrega'], ENT_QUOTES) ?>"
            data-entrega-cargador="<?= (int)$r['entrega_cargador'] ?>"
            data-entrega-maletin="<?= (int)$r['entrega_maletin'] ?>"
            data-entrega-otros="<?= htmlspecialchars($r['entrega_otros'] ?? '', ENT_QUOTES) ?>"
            data-observaciones-entrega="<?= htmlspecialchars($r['observaciones_entrega'] ?? '', ENT_QUOTES) ?>">
            <td><?= (int)$r['idasignacion'] ?></td>
            <td><?= htmlspecialchars($r['empleado']) ?></td>
            <td><?= htmlspecialchars($r['equipo']) ?></td>
            <td><span class="label label-info"><?= htmlspecialchars($r['condicion_entrega']) ?></span></td>
            <td><?= htmlspecialchars($textoAccesorios) ?></td>
            <td><?= $r['fecha_asignacion'] ? date('d/m/Y', strtotime($r['fecha_asignacion'])) : '—' ?></td>
            <td>
                <?php if (empty($r['firma'])): ?>
                <a href="#" title="Editar asignación y checklist" onclick="return modalEdit(event);"
                   data-toggle="modal" data-target="#editModal"><span class="fa fa-edit"></span></a>
                <?php endif; ?>
                <?php if ((int)$r['requiere_firma_entrega'] === 1 && empty($r['firma'])): ?>
                <span title="Debe firmar el acta de entrega antes de devolver este equipo">
                    <span class="fa fa-rotate-left" style="color:#9ca3af"></span>
                </span>
                <?php else: ?>
                <a href="#" title="Devolver equipo" onclick="return modalDevolver(event);"
                   data-toggle="modal" data-target="#devolucionModal">
                    <span class="fa fa-rotate-left" style="color:#c56b08"></span>
                </a>
                <?php endif; ?>
                <?php if (!empty($r['firma'])): ?>
                <a href="<?= BASE_URL ?>/reportes/acta_asignacion.php?idasignacion=<?= (int)$r['idasignacion'] ?>"
                   target="_blank" title="Ver acta de entrega firmada">
                    <span class="fa fa-file-circle-check" style="color:#1a7c3e"></span>
                </a>
                <?php else: ?>
                <a href="#" title="Firmar acta de entrega" onclick="return modalFirmarEntrega(event);"
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
$(function () {
    $('#datosE').DataTable({ dom: 'lrtip', order: [[0, 'desc']] });
});
</script>
<?php else: ?>
<p class="lead"><em>No hay asignaciones activas.</em></p>
<?php endif; ?>

<!-- MODAL NUEVA ASIGNACIÓN -->
<div class="modal fade" id="newModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form action="<?= BASE_URL ?>/asignarequipo.php" method="post">
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title">Nueva Asignación</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="empleado">Empleado</label>
          <select name="empleado" id="empleado" class="form-control" required>
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
          <select name="equipo" id="equipo" class="form-control" required>
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
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success" name="add"><i class="fa fa-check"></i> Crear asignación</button>
      </div>
    </form>
  </div></div>
</div>

<!-- MODAL EDITAR ASIGNACIÓN NO FIRMADA -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form action="<?= BASE_URL ?>/asignarequipo.php" method="post">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="idasignacion" id="idasignacion">
      <div class="modal-header">
        <h5 class="modal-title">Editar Asignación</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info" style="padding:9px 12px;">Solo pueden editarse asignaciones que todavía no tienen acta firmada.</div>
        <div class="form-group">
          <label for="empleadoAct">Empleado</label>
          <select name="empleado" id="empleadoAct" class="form-control" required>
            <option value="0">-- Seleccione un empleado --</option>
            <?php foreach ($emps as $e): ?>
            <option value="<?= (int)$e['idempleado'] ?>"><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellidos']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="equipoAct">Equipo</label>
          <select name="equipo" id="equipoAct" class="form-control" required>
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
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" name="edit">Actualizar</button>
      </div>
    </form>
  </div></div>
</div>

<!-- MODAL FIRMAR ENTREGA -->
<div class="modal fade" id="firmarModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form id="formFirmaEntrega" method="post" action="<?= BASE_URL ?>/reportes/acta_asignacion.php" target="_blank">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="idasignacion" id="idasignacionFirma">
      <input type="hidden" name="firma" id="firmaEntregaInput">
      <div class="modal-header">
        <h5 class="modal-title">Firmar Acta de Entrega</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p><strong>Empleado:</strong> <span id="lblEmpleadoFirma"></span></p>
        <p><strong>Equipo:</strong> <span id="lblEquipoFirma"></span></p>
        <p class="text-muted">El empleado confirma el equipo, condición y accesorios registrados.</p>
        <div class="firma-lienzo"><canvas id="canvasFirmaEntrega" width="440" height="160"></canvas></div>
        <button type="button" class="btn btn-sm btn-default" id="btnLimpiarFirmaEntrega" style="margin-top:6px;">
          <i class="fa fa-eraser"></i> Limpiar firma
        </button>
        <div id="avisoFirmaEntrega" class="text-danger firma-aviso"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success"><i class="fa fa-file-pdf"></i> Firmar y generar acta</button>
      </div>
    </form>
  </div></div>
</div>

<!-- MODAL DEVOLUCIÓN -->
<div class="modal fade" id="devolucionModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form id="formDevolucion" method="post" action="<?= BASE_URL ?>/reportes/acta_devolucion.php" target="_blank">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="idasignacion" id="idasignacionDevolucion">
      <input type="hidden" name="firma_devolucion" id="firmaDevolucionInput">
      <div class="modal-header">
        <h5 class="modal-title">Recibir y Devolver Equipo</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p><strong>Empleado:</strong> <span id="lblEmpleadoDevolucion"></span></p>
        <p><strong>Equipo:</strong> <span id="lblEquipoDevolucion"></span></p>
        <div class="well well-sm"><strong>Entregado originalmente:</strong> <span id="lblChecklistEntrega"></span></div>
        <div class="form-group">
          <label for="condicionDevolucion">Condición física al recibir</label>
          <select name="condicion_devolucion" id="condicionDevolucion" class="form-control" required>
            <option value="Bueno">Bueno - volverá a Disponible</option>
            <option value="Con daño">Con daño - pasará a En mantenimiento</option>
            <option value="No funcional">No funcional - pasará a En mantenimiento</option>
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
        <p class="text-muted">Firma del responsable de IT que recibe el equipo:</p>
        <div class="firma-lienzo"><canvas id="canvasFirmaDevolucion" width="440" height="160"></canvas></div>
        <button type="button" class="btn btn-sm btn-default" id="btnLimpiarFirmaDevolucion" style="margin-top:6px;">
          <i class="fa fa-eraser"></i> Limpiar firma
        </button>
        <div id="avisoFirmaDevolucion" class="text-danger firma-aviso"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-warning"><i class="fa fa-file-pdf"></i> Confirmar y generar acta</button>
      </div>
    </form>
  </div></div>
</div>

<script>
function modalEdit(evento) {
    var fila = $(evento.target).closest('tr');
    $('#idasignacion').val(fila.find('td').eq(0).text());
    $('#empleadoAct').val(String(fila.data('idempleado')));
    $('#equipoAct').val(String(fila.data('idequipo')));
    $('#editarCondicionEntrega').val(fila.attr('data-condicion-entrega'));
    $('#editarEntregaCargador').prop('checked', fila.data('entrega-cargador') == 1);
    $('#editarEntregaMaletin').prop('checked', fila.data('entrega-maletin') == 1);
    $('#editarEntregaOtros').val(fila.attr('data-entrega-otros'));
    $('#editarObservacionesEntrega').val(fila.attr('data-observaciones-entrega'));
}

function modalFirmarEntrega(evento) {
    var fila = $(evento.target).closest('tr');
    $('#idasignacionFirma').val(fila.find('td').eq(0).text());
    $('#lblEmpleadoFirma').text(fila.find('td').eq(1).text());
    $('#lblEquipoFirma').text(fila.find('td').eq(2).text());
    limpiarFirmaCanvas('canvasFirmaEntrega', 'avisoFirmaEntrega');
}

function modalDevolver(evento) {
    var fila = $(evento.target).closest('tr');
    var accesorios = [];
    if (fila.data('entrega-cargador') == 1) { accesorios.push('Cargador'); }
    if (fila.data('entrega-maletin') == 1) { accesorios.push('Maletín'); }
    if (fila.attr('data-entrega-otros')) { accesorios.push(fila.attr('data-entrega-otros')); }

    $('#idasignacionDevolucion').val(fila.find('td').eq(0).text());
    $('#lblEmpleadoDevolucion').text(fila.find('td').eq(1).text());
    $('#lblEquipoDevolucion').text(fila.find('td').eq(2).text());
    $('#lblChecklistEntrega').text(accesorios.length ? accesorios.join(', ') : 'Sin accesorios');
    $('#condicionDevolucion').val('Bueno');
    $('#devolucionCargador').prop('checked', fila.data('entrega-cargador') == 1);
    $('#devolucionMaletin').prop('checked', fila.data('entrega-maletin') == 1);
    $('#devolucionOtros').val(fila.attr('data-entrega-otros') || '');
    $('#observacionesDevolucion').val('');
    limpiarFirmaCanvas('canvasFirmaDevolucion', 'avisoFirmaDevolucion');
}

function limpiarFirmaCanvas(canvasId, avisoId) {
    var canvas = document.getElementById(canvasId);
    if (!canvas || !canvas._firmaContexto) { return; }
    canvas._firmaContexto.fillStyle = '#ffffff';
    canvas._firmaContexto.fillRect(0, 0, canvas.width, canvas.height);
    canvas._firmaConTrazo = false;
    $('#' + avisoId).text('');
}

function configurarFirmaCanvas(config) {
    var canvas = document.getElementById(config.canvasId);
    var ctx = canvas.getContext('2d');
    var dibujando = false;
    canvas._firmaContexto = ctx;
    canvas._firmaConTrazo = false;
    canvas.style.touchAction = 'none';
    canvas.style.cursor = 'crosshair';
    limpiarFirmaCanvas(config.canvasId, config.avisoId);

    function posicion(evento) {
        var rect = canvas.getBoundingClientRect();
        return {
            x: (evento.clientX - rect.left) * (canvas.width / rect.width),
            y: (evento.clientY - rect.top) * (canvas.height / rect.height)
        };
    }
    canvas.onpointerdown = function (evento) {
        evento.preventDefault();
        dibujando = true;
        canvas._firmaConTrazo = true;
        canvas.setPointerCapture(evento.pointerId);
        var punto = posicion(evento);
        ctx.beginPath();
        ctx.moveTo(punto.x, punto.y);
    };
    canvas.onpointermove = function (evento) {
        if (!dibujando) { return; }
        evento.preventDefault();
        var punto = posicion(evento);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1e1e2d';
        ctx.lineTo(punto.x, punto.y);
        ctx.stroke();
    };
    canvas.onpointerup = canvas.onpointercancel = function () { dibujando = false; };

    $('#' + config.limpiarId).off('click.firma').on('click.firma', function () {
        limpiarFirmaCanvas(config.canvasId, config.avisoId);
    });
    $('#' + config.formId).off('submit.firma').on('submit.firma', function (evento) {
        if (!canvas._firmaConTrazo) {
            evento.preventDefault();
            $('#' + config.avisoId).text('Debe dibujar la firma antes de continuar.');
            return;
        }
        $('#' + config.inputId).val(canvas.toDataURL('image/jpeg', 0.9));
        if (typeof config.alEnviar === 'function') { config.alEnviar(); }
    });
}

$(function () {
    configurarFirmaCanvas({
        canvasId: 'canvasFirmaEntrega', limpiarId: 'btnLimpiarFirmaEntrega',
        formId: 'formFirmaEntrega', inputId: 'firmaEntregaInput', avisoId: 'avisoFirmaEntrega'
    });
    configurarFirmaCanvas({
        canvasId: 'canvasFirmaDevolucion', limpiarId: 'btnLimpiarFirmaDevolucion',
        formId: 'formDevolucion', inputId: 'firmaDevolucionInput', avisoId: 'avisoFirmaDevolucion',
        alEnviar: function () {
            setTimeout(function () {
                $('#devolucionModal').modal('hide');
                ajaxLoad('<?= BASE_URL ?>/app/ajax/transacciones/asignarequipo.php');
            }, 1400);
        }
    });
    <?php if ($abrirModalNuevo): ?>
    setTimeout(function () { $('#newModal').modal('show'); }, 80);
    <?php endif; ?>
});
</script>