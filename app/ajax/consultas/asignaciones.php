<?php
// GestActivos - Consulta de historial completo de asignaciones.
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('consultas');

$db = Database::getInstance();
$puedeVerActas = (string)Auth::get('actas') === '1' || (string)Auth::get('transacciones') === '1';
$filtro = TableFilter::text('query');
$estadoAsignacionFiltro = TableFilter::enum('estado_asignacion', ['activa', 'cerrada']);
$resultadoEquipoFiltro = TableFilter::enum('resultado_equipo', ['1', '3', '4', '5']);
$fechaDesdeFiltro = TableFilter::date('fecha_desde');
$fechaHastaFiltro = TableFilter::date('fecha_hasta');

$sql = "SELECT asg.idasignacion, asg.activa, asg.fecha_asignacion, asg.fecha_devolucion,
               asg.condicion_entrega, asg.condicion_devolucion,
               asg.estado_equipo_devolucion, asg.firma, asg.firma_devolucion,
               CONCAT(em.nombre,' ',em.apellidos) AS empleado,
               CONCAT(COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)), ' - ', ma.nombreMarca, ' ', mo.nombreModelo) AS equipo,
               ar.descripcionarea AS area, ca.descripcioncargo AS cargo
        FROM asignacion asg
        INNER JOIN empleados em ON asg.idempleado=em.idempleado
        INNER JOIN equipo eq ON asg.idequipo=eq.idequipo
        INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo
        LEFT JOIN areas ar ON em.idarea=ar.idarea
        LEFT JOIN cargos ca ON em.idcargo=ca.idcargo";
$conditions = [];
$params = [];
if ($filtro !== '') {
    $conditions[] = "(CONCAT(em.nombre,' ',em.apellidos) LIKE ?
              OR CONCAT(ma.nombreMarca,' ',mo.nombreModelo) LIKE ?
              OR eq.codigo_activo LIKE ? OR ar.descripcionarea LIKE ?)";
    $like = "%$filtro%";
    $params = [$like, $like, $like, $like];
}
if ($estadoAsignacionFiltro !== '') { $conditions[] = 'asg.activa = ?'; $params[] = $estadoAsignacionFiltro === 'activa' ? 1 : 0; }
if ($resultadoEquipoFiltro !== '') { $conditions[] = 'asg.estado_equipo_devolucion = ?'; $params[] = (int)$resultadoEquipoFiltro; }
if ($fechaDesdeFiltro !== '') { $conditions[] = 'DATE(asg.fecha_asignacion) >= ?'; $params[] = $fechaDesdeFiltro; }
if ($fechaHastaFiltro !== '') { $conditions[] = 'DATE(asg.fecha_asignacion) <= ?'; $params[] = $fechaHastaFiltro; }
if ($conditions) { $sql .= ' WHERE ' . implode(' AND ', $conditions); }
$sql .= ' ORDER BY asg.fecha_asignacion DESC, asg.idasignacion DESC';
$resultado = $db->consulta($sql, $params);
?>

<?php if ($resultado): ?>
<div class="table-responsive">
<table class="table table-bordered table-striped" id="tablaAsg">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th>#</th><th>Empleado</th><th>Equipo</th><th>Área</th><th>Cargo</th>
            <th>Asignado</th><th>Condición entrega</th><th>Devuelto</th>
            <th>Condición devolución</th><th>Resultado</th><th>Estado</th>
            <?php if ($puedeVerActas): ?><th>Actas</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resultado as $r): ?>
        <?php $resultadoEquipo = EquipoEstado::nombre((int)$r['estado_equipo_devolucion'], '—'); ?>
        <tr class="<?= (int)$r['activa'] === 0 ? 'text-muted' : '' ?>">
            <td><?= (int)$r['idasignacion'] ?></td>
            <td><?= htmlspecialchars($r['empleado']) ?></td>
            <td><?= htmlspecialchars($r['equipo']) ?></td>
            <td><?= htmlspecialchars($r['area'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['cargo'] ?? '') ?></td>
            <td><?= $r['fecha_asignacion'] ? date('d/m/Y', strtotime($r['fecha_asignacion'])) : '—' ?></td>
            <td><?= htmlspecialchars($r['condicion_entrega'] ?: 'Bueno') ?></td>
            <td><?= $r['fecha_devolucion'] ? date('d/m/Y', strtotime($r['fecha_devolucion'])) : '—' ?></td>
            <td><?= htmlspecialchars($r['condicion_devolucion'] ?: '—') ?></td>
            <td><?= htmlspecialchars($resultadoEquipo) ?></td>
            <td><?= (int)$r['activa'] === 1
                ? '<span class="badge app-badge-success">Activa</span>'
                : '<span class="badge app-badge-muted">Devuelta</span>' ?></td>
            <?php if ($puedeVerActas): ?>
            <td class="acciones-actas">
                <?php if (!empty($r['firma'])): ?>
                <a href="<?= BASE_URL ?>/reportes/acta_asignacion.php?idasignacion=<?= (int)$r['idasignacion'] ?>"
                   target="_blank" title="Acta de entrega"><span class="fa fa-file-circle-check"></span></a>
                <?php endif; ?>
                <?php if (!empty($r['firma_devolucion'])): ?>
                <a href="<?= BASE_URL ?>/reportes/acta_devolucion.php?idasignacion=<?= (int)$r['idasignacion'] ?>"
                   target="_blank" title="Acta de devolución"><span class="fa fa-file-export"></span></a>
                <?php endif; ?>
                <?php if (empty($r['firma']) && empty($r['firma_devolucion'])): ?>—<?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<script>
$(function () { $('#tablaAsg').DataTable({ dom: 'lrtip', order: [[0, 'desc']] }); });
</script>
<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>
