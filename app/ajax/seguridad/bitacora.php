<?php
// ============================================================
// GestActivos - Bitácora de Auditoría (SOLO LECTURA)
// Registra cada acción de escritura y cada intento de login.
// ============================================================
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('seguridad');

$db = Database::getInstance();
$q  = trim($_POST['query'] ?? '');

$sql = "SELECT idbitacora, idusuario, usuario_texto, accion,
               modulo, detalle, ip, fecha
        FROM bitacora";
$params = [];
if ($q !== '') {
    $sql   .= " WHERE usuario_texto LIKE ? OR accion LIKE ? OR modulo LIKE ? OR detalle LIKE ?";
    $like   = "%$q%";
    $params = [$like, $like, $like, $like];
}
$sql .= " ORDER BY fecha DESC LIMIT 500";

$resultado = $db->consulta($sql, $params);

// Paleta de colores por tipo de acción
$colores = [
    'crear'          => '#28a745',
    'editar'         => '#007bff',
    'eliminar'       => '#dc3545',
    'reactivar'      => '#17a2b8',
    'devolucion'     => '#fd7e14',
    'login_exitoso'  => '#6f42c1',
    'login_fallido'  => '#dc3545',
];
?>
<?php if (count($resultado) > 0): ?>
<p class="text-muted" style="font-size:12px; margin-bottom:6px;">
    Mostrando los últimos <?= count($resultado) ?> registros. Usa el buscador para filtrar.
</p>
<table class="table table-bordered table-striped table-condensed" id="tablaBitacora">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Módulo</th>
            <th>Detalle</th>
            <th>IP</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <?php $color = $colores[$r['accion']] ?? '#6c757d'; ?>
        <tr>
            <td><?= $r['idbitacora'] ?></td>
            <td style="white-space:nowrap;"><?= htmlspecialchars($r['fecha']) ?></td>
            <td><?= htmlspecialchars($r['usuario_texto'] ?? '') ?></td>
            <td>
                <span style="background:<?= $color ?>;color:#fff;padding:2px 6px;border-radius:3px;font-size:11px;">
                    <?= htmlspecialchars($r['accion']) ?>
                </span>
            </td>
            <td><?= htmlspecialchars($r['modulo'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['detalle'] ?? '') ?></td>
            <td style="font-size:11px;"><?= htmlspecialchars($r['ip'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
$(document).ready(function () {
    $('#tablaBitacora').DataTable({
        dom: 'lrtip',
        order: [[0, 'desc']],
        pageLength: 25,
        columnDefs: [{ orderable: false, targets: [5, 6] }]
    });
});
</script>
<?php else: ?>
<p class="lead"><em>No hay registros en la bitácora todavía.</em></p>
<?php endif; ?>
