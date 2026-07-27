<?php
// ============================================================
// GestActivos - Reportes: Empleados (SOLO LECTURA)
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerirPermiso('reportes');

$db = Database::getInstance();
$q  = trim($_POST['query'] ?? '');

$sql = "SELECT em.idempleado, em.nombre, em.apellidos, em.edad, em.telefono,
               em.direccion, em.activo, ar.descripcionarea, ca.descripcioncargo
        FROM empleados em
        LEFT JOIN areas  ar ON em.idarea  = ar.idarea
        LEFT JOIN cargos ca ON em.idcargo = ca.idcargo";
$params = [];
if ($q !== '') {
    $sql   .= " WHERE em.nombre LIKE ? OR em.apellidos LIKE ? OR em.idempleado LIKE ? OR em.telefono LIKE ?";
    $like   = "%$q%";
    $params = [$like, $like, $like, $like];
}
$sql .= " ORDER BY em.idempleado DESC";

$resultado = $db->consulta($sql, $params);
?>
<?php if (count($resultado) > 0): ?>
<table class="table table-bordered table-striped" id="datosE">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th>ID</th><th>Nombre</th><th>Apellidos</th><th>Edad</th>
            <th>Teléfono</th><th>Dirección</th><th>Área</th><th>Cargo</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <tr class="<?= (int)$r['activo'] === 0 ? 'text-muted' : '' ?>">
            <td><?= $r['idempleado'] ?></td>
            <td><?= htmlspecialchars($r['nombre']) ?></td>
            <td><?= htmlspecialchars($r['apellidos']) ?></td>
            <td><?= $r['edad'] ?></td>
            <td><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['direccion'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcionarea'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcioncargo'] ?? '') ?></td>
            <td><?= (int)$r['activo'] === 1 ? '<span class="badge app-badge-success">Activo</span>' : '<span class="badge app-badge-muted">Inactivo</span>' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
$(document).ready(function(){ $("#datosE").DataTable({ dom: 'lrtip', order: [[0, 'desc']] }); });
</script>
<?php else: ?>
<p class="lead"><em>No hay registros</em></p>
<?php endif; ?>
