<?php
// ============================================================
// GestActivos - Consultas: Cargo (SOLO LECTURA)
// Este módulo nunca debe permitir crear, editar ni eliminar.
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerir();

$db = Database::getInstance();
$q  = trim($_POST['query'] ?? '');

$sql = "SELECT idcargo, descripcioncargo, activo FROM cargos";
$params = [];
if ($q !== '') {
    $sql   .= " WHERE (descripcioncargo LIKE ? OR idcargo LIKE ?)";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY descripcioncargo";

$resultado = $db->consulta($sql, $params);
?>
<?php if (count($resultado) > 0): ?>
<table class="table table-bordered table-striped" id="tablaConsCargo">
    <thead style="background-color:#D3E9F1">
        <tr><th>ID</th><th>Cargo</th><th>Estado</th></tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <tr class="<?= (int)$r['activo'] === 0 ? 'text-muted' : '' ?>">
            <td><?= $r['idcargo'] ?></td>
            <td><?= htmlspecialchars($r['descripcioncargo']) ?></td>
            <td><?= (int)$r['activo'] === 1 ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
$(document).ready(function(){ $('#tablaConsCargo').DataTable({ dom: 'lrtip' }); });
</script>
<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>
