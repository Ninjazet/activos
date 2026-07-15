<?php
// ============================================================
// GestActivos - Reportes: Equipos (SOLO LECTURA)
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerir();

$db = Database::getInstance();
$q  = trim($_POST['query'] ?? '');

$sql = "SELECT eq.idequipo, eq.activo, ma.nombreMarca, mo.nombreModelo
        FROM equipo eq
        INNER JOIN marca  ma ON eq.idmarca_equipo  = ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo";
$params = [];
if ($q !== '') {
    $sql   .= " WHERE ma.nombreMarca LIKE ? OR mo.nombreModelo LIKE ? OR eq.idequipo LIKE ?";
    $like   = "%$q%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY ma.nombreMarca, mo.nombreModelo";

$resultado = $db->consulta($sql, $params);
?>
<?php if (count($resultado) > 0): ?>
<table class="table table-bordered table-striped" id="datosE">
    <thead style="background-color:#D3E9F1">
        <tr><th>ID</th><th>Marca</th><th>Modelo</th><th>Estado</th></tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <tr class="<?= (int)$r['activo'] === 0 ? 'text-muted' : '' ?>">
            <td><?= $r['idequipo'] ?></td>
            <td><?= htmlspecialchars($r['nombreMarca']) ?></td>
            <td><?= htmlspecialchars($r['nombreModelo']) ?></td>
            <td><?= (int)$r['activo'] === 1 ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
$(document).ready(function(){ $("#datosE").DataTable({ dom: 'lrtip' }); });
</script>
<?php else: ?>
<p class="lead"><em>No hay registros</em></p>
<?php endif; ?>
