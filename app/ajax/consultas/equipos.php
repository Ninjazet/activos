<?php
// ============================================================
// GestActivos - Consultas: Equipos (SOLO LECTURA)
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerirPermiso('consultas');

$db = Database::getInstance();
$q  = trim($_POST['query'] ?? '');

$sql = "SELECT eq.idequipo, eq.imagen, eq.activo, eq.codigo_activo, eq.numero_serie, eq.tipo_equipo, eq.estado_equipo, ma.nombreMarca, mo.nombreModelo
        FROM equipo eq
        INNER JOIN marca  ma ON eq.idmarca_equipo  = ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo";
$params = [];
if ($q !== '') {
    $sql   .= " WHERE ma.nombreMarca LIKE ? OR mo.nombreModelo LIKE ? OR eq.idequipo LIKE ? OR eq.codigo_activo LIKE ? OR eq.numero_serie LIKE ? OR eq.tipo_equipo LIKE ?";
    $like   = "%$q%";
    $params = [$like, $like, $like, $like, $like, $like];
}
$sql .= " ORDER BY eq.idequipo DESC";

$resultado = $db->consulta($sql, $params);
?>
<?php if (count($resultado) > 0): ?>
<table class="table table-bordered table-striped" id="tablaConsEquipo">
    <thead style="background-color:#D3E9F1">
        <tr><th>Foto</th><th>Código</th><th>Tipo</th><th>Serie</th><th>Marca</th><th>Modelo</th><th>Estado</th></tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <?php $img = $r['imagen'] ? (BASE_URL . '/' . $r['imagen']) : (BASE_URL . '/public/icons/equipo.png'); ?>
        <tr class="<?= (int)$r['activo'] === 0 ? 'text-muted' : '' ?>">
            <td><img src="<?= htmlspecialchars($img, ENT_QUOTES) ?>" style="width:36px;height:36px;object-fit:cover;"></td>
            <td><?= htmlspecialchars($r['codigo_activo'] ?? ('EQ-' . $r['idequipo'])) ?></td>
            <td><?= htmlspecialchars($r['tipo_equipo'] ?? 'Otro') ?></td>
            <td><?= htmlspecialchars($r['numero_serie'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['nombreMarca']) ?></td>
            <td><?= htmlspecialchars($r['nombreModelo']) ?></td>
            <td><?php $estados=[1=>'Disponible',2=>'Asignado',3=>'En mantenimiento',4=>'Perdido o robado',5=>'Dado de baja']; echo htmlspecialchars($estados[(int)$r['estado_equipo']] ?? 'Sin definir'); ?><?= (int)$r['activo']===0 ? ' <span class="label label-default">Inactivo</span>' : '' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
$(document).ready(function(){ $('#tablaConsEquipo').DataTable({ dom: 'lrtip', order: [[1, 'desc']] }); });
</script>
<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>
