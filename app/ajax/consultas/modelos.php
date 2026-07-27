<?php
// ============================================================
// GestActivos - Consultas: Modelo (SOLO LECTURA)
// Este módulo nunca debe permitir crear, editar ni eliminar.
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerirPermiso('consultas');

$db = Database::getInstance();
$q  = trim($_POST['query'] ?? '');

$sql = "SELECT idmodelo, nombreModelo, activo FROM modelo";
$params = [];
if ($q !== '') {
    $sql   .= " WHERE (nombreModelo LIKE ? OR idmodelo LIKE ?)";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY nombreModelo";

$resultado = $db->consulta($sql, $params);
?>
<?php if (count($resultado) > 0): ?>
<table class="table table-bordered table-striped" id="tablaConsModelo">
    <thead style="background-color:#D3E9F1">
        <tr><th>ID</th><th>Modelo</th><th>Estado</th></tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <tr class="<?= (int)$r['activo'] === 0 ? 'text-muted' : '' ?>">
            <td><?= $r['idmodelo'] ?></td>
            <td><?= htmlspecialchars($r['nombreModelo']) ?></td>
            <td><?= (int)$r['activo'] === 1 ? '<span class="badge app-badge-success">Activo</span>' : '<span class="badge app-badge-muted">Inactivo</span>' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
$(document).ready(function(){ $('#tablaConsModelo').DataTable({ dom: 'lrtip', order: [[1, 'asc']] }); });
</script>
<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>
