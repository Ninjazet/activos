<?php
// ============================================================
// GestActivos - Consultas: Empleados (SOLO LECTURA)
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerirPermiso('consultas');

$db = Database::getInstance();
$q = TableFilter::text('query');
$estadoEmpleadoFiltro = TableFilter::enum('estado_empleado', ['0', '1']);
$areaFiltro = TableFilter::positiveInt('idarea');
$cargoFiltro = TableFilter::positiveInt('idcargo');

$sql = "SELECT em.idempleado, em.nombre, em.apellidos, em.edad, em.telefono,
               em.direccion, em.imagen, em.activo,
               ar.descripcionarea, ca.descripcioncargo
        FROM empleados em
        LEFT JOIN areas  ar ON em.idarea  = ar.idarea
        LEFT JOIN cargos ca ON em.idcargo = ca.idcargo";
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = '(em.nombre LIKE ? OR em.apellidos LIKE ? OR em.telefono LIKE ? OR em.correo LIKE ? OR ar.descripcionarea LIKE ? OR ca.descripcioncargo LIKE ?)';
    $like   = "%$q%";
    $params = [$like, $like, $like, $like, $like, $like];
}
if ($estadoEmpleadoFiltro !== '') { $conditions[] = 'em.activo = ?'; $params[] = (int)$estadoEmpleadoFiltro; }
if ($areaFiltro > 0) { $conditions[] = 'em.idarea = ?'; $params[] = $areaFiltro; }
if ($cargoFiltro > 0) { $conditions[] = 'em.idcargo = ?'; $params[] = $cargoFiltro; }
if ($conditions) { $sql .= ' WHERE ' . implode(' AND ', $conditions); }
$sql .= " ORDER BY em.idempleado DESC";

$resultado = $db->consulta($sql, $params);
?>
<?php if (count($resultado) > 0): ?>
<table class="table table-bordered table-striped" id="tablaConsEmp">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th>Foto</th><th>ID</th><th>Nombre</th><th>Apellidos</th><th>Edad</th>
            <th>Teléfono</th><th>Área</th><th>Cargo</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <?php
            $imgUrl = Imagen::empleado($r['imagen'] ?? null);
        ?>
        <tr class="<?= (int)$r['activo'] === 0 ? 'text-muted' : '' ?>">
                <td><img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>" alt="Foto de <?= htmlspecialchars($r['nombre'] . ' ' . $r['apellidos'], ENT_QUOTES) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;"></td>
            <td><?= $r['idempleado'] ?></td>
            <td><?= htmlspecialchars($r['nombre']) ?></td>
            <td><?= htmlspecialchars($r['apellidos']) ?></td>
            <td><?= $r['edad'] ?></td>
            <td><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcionarea'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcioncargo'] ?? '') ?></td>
            <td><?= (int)$r['activo'] === 1 ? '<span class="badge app-badge-success">Activo</span>' : '<span class="badge app-badge-muted">Inactivo</span>' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
$(document).ready(function(){ $('#tablaConsEmp').DataTable({ dom: 'lrtip', order: [[1, 'desc']] }); });
</script>
<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>
