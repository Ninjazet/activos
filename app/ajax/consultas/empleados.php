<?php
// ============================================================
// GestActivos - Consultas: Empleados (SOLO LECTURA)
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerirPermiso('consultas');

$db = Database::getInstance();
$q  = trim($_POST['query'] ?? '');

$sql = "SELECT em.idempleado, em.nombre, em.apellidos, em.edad, em.telefono,
               em.direccion, em.imagen, em.activo,
               ar.descripcionarea, ca.descripcioncargo
        FROM empleados em
        LEFT JOIN areas  ar ON em.idarea  = ar.idarea
        LEFT JOIN cargos ca ON em.idcargo = ca.idcargo";
$params = [];
if ($q !== '') {
    $sql   .= " WHERE em.nombre LIKE ? OR em.apellidos LIKE ? OR em.telefono LIKE ? OR ar.descripcionarea LIKE ?";
    $like   = "%$q%";
    $params = [$like, $like, $like, $like];
}
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
            $archivoImagen = basename($r['imagen'] ?? '');
            $imgUrl = ($archivoImagen && file_exists(IMG_EMPLEADOS . $archivoImagen))
                ? BASE_URL . '/public/img/empleados/' . $archivoImagen
                : BASE_URL . '/public/img/empleados/avatar1.png';
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
            <td><?= (int)$r['activo'] === 1 ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>' ?></td>
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
