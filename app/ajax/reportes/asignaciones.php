<?php
// ============================================================
// GestActivos - Reportes: Asignaciones (SOLO LECTURA)
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerir();

$db = Database::getInstance();

$sql = "SELECT asg.idasignacion, asg.activa, asg.fecha_asignacion, asg.fecha_devolucion,
               CONCAT(em.nombre,' ',em.apellidos) AS empleado,
               CONCAT(COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)), ' - ', ma.nombreMarca, ' ', mo.nombreModelo) AS equipo,
               ar.descripcionarea AS area,
               ca.descripcioncargo AS cargo
        FROM asignacion asg
        INNER JOIN empleados em ON asg.idempleado = em.idempleado
        INNER JOIN equipo eq    ON asg.idequipo   = eq.idequipo
        INNER JOIN marca ma     ON eq.idmarca_equipo  = ma.idmarca
        INNER JOIN modelo mo    ON eq.idmodelo_equipo = mo.idmodelo
        LEFT  JOIN areas ar     ON em.idarea  = ar.idarea
        LEFT  JOIN cargos ca    ON em.idcargo = ca.idcargo";

$params = [];
if (isset($_POST['query']) && trim($_POST['query']) != '') {
    $f      = trim($_POST['query']);
    $sql   .= " WHERE CONCAT(em.nombre,' ',em.apellidos) LIKE ?
              OR CONCAT(ma.nombreMarca,' ',mo.nombreModelo) LIKE ?
              OR ar.descripcionarea LIKE ?";
    $params = ["%$f%", "%$f%", "%$f%"];
}
$sql .= " ORDER BY asg.fecha_asignacion DESC, asg.idasignacion DESC";

$resultado = $db->consulta($sql, $params);
?>

<?php if (count($resultado) > 0): ?>
<table class="table table-bordered table-striped" id="tablaAsgRep">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th>#</th><th>Empleado</th><th>Equipo</th><th>Área</th><th>Cargo</th>
            <th>Asignado</th><th>Devuelto</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resultado as $r): ?>
        <tr class="<?= (int)$r['activa'] === 0 ? 'text-muted' : '' ?>">
            <td><?= $r['idasignacion'] ?></td>
            <td><?= htmlspecialchars($r['empleado']) ?></td>
            <td><?= htmlspecialchars($r['equipo']) ?></td>
            <td><?= htmlspecialchars($r['area'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['cargo'] ?? '') ?></td>
            <td><?= $r['fecha_asignacion'] ? date('d/m/Y', strtotime($r['fecha_asignacion'])) : '—' ?></td>
            <td><?= $r['fecha_devolucion'] ? date('d/m/Y', strtotime($r['fecha_devolucion'])) : '—' ?></td>
            <td><?= (int)$r['activa'] === 1 ? '<span class="label label-success">Activa</span>' : '<span class="label label-default">Devuelta</span>' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<script>
$(document).ready(function(){ $("#tablaAsgRep").DataTable({ dom: 'lrtip', order: [[0, 'desc']] }); });
</script>
<?php else: ?>
<p class="lead"><em>No hay registros</em></p>
<?php endif; ?>
