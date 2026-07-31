<?php
// ============================================================
// GestActivos - Reportes: Equipos (SOLO LECTURA)
// ============================================================
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerirPermiso('reportes');

$db = Database::getInstance();
$q = TableFilter::text('query');
$estadoEquipoFiltro = TableFilter::enum('estado_equipo', EquipoEstado::idsComoTexto());
$tipoEquipoFiltro = TableFilter::text('tipo_equipo', 50);
$marcaFiltro = TableFilter::positiveInt('idmarca');
$modeloFiltro = TableFilter::positiveInt('idmodelo');
$proveedorFiltro = TableFilter::positiveInt('idproveedor');
$activoFiltro = TableFilter::enum('activo', ['0', '1']);
$garantiaFiltro = TableFilter::enum('garantia', ['vigente', 'vence_30', 'vencida', 'sin_fecha']);

$sql = "SELECT eq.idequipo, eq.activo, eq.codigo_activo, eq.numero_serie, eq.tipo_equipo, eq.estado_equipo, ma.nombreMarca, mo.nombreModelo, p.nombre AS proveedor
        FROM equipo eq
        INNER JOIN marca  ma ON eq.idmarca_equipo  = ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo
        LEFT JOIN proveedores p ON p.idproveedor=eq.idproveedor";
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = '(ma.nombreMarca LIKE ? OR mo.nombreModelo LIKE ? OR eq.idequipo LIKE ? OR eq.codigo_activo LIKE ? OR eq.numero_serie LIKE ? OR eq.tipo_equipo LIKE ? OR p.nombre LIKE ?)';
    $like   = "%$q%";
    $params = [$like, $like, $like, $like, $like, $like, $like];
}
if ($estadoEquipoFiltro !== '') { $conditions[] = 'eq.estado_equipo = ?'; $params[] = (int)$estadoEquipoFiltro; }
if ($tipoEquipoFiltro !== '') { $conditions[] = 'eq.tipo_equipo = ?'; $params[] = $tipoEquipoFiltro; }
if ($marcaFiltro > 0) { $conditions[] = 'eq.idmarca_equipo = ?'; $params[] = $marcaFiltro; }
if ($modeloFiltro > 0) { $conditions[] = 'eq.idmodelo_equipo = ?'; $params[] = $modeloFiltro; }
if ($proveedorFiltro > 0) { $conditions[] = 'eq.idproveedor = ?'; $params[] = $proveedorFiltro; }
if ($activoFiltro !== '') { $conditions[] = 'eq.activo = ?'; $params[] = (int)$activoFiltro; }
if ($garantiaFiltro === 'vigente') { $conditions[] = 'eq.vencimiento_garantia >= CURDATE()'; }
elseif ($garantiaFiltro === 'vence_30') { $conditions[] = 'eq.vencimiento_garantia BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)'; }
elseif ($garantiaFiltro === 'vencida') { $conditions[] = 'eq.vencimiento_garantia < CURDATE()'; }
elseif ($garantiaFiltro === 'sin_fecha') { $conditions[] = 'eq.vencimiento_garantia IS NULL'; }
if ($conditions) { $sql .= ' WHERE ' . implode(' AND ', $conditions); }
$sql .= " ORDER BY eq.idequipo DESC";

$resultado = $db->consulta($sql, $params);
?>
<?php if (count($resultado) > 0): ?>
<table class="table table-bordered table-striped" id="datosE">
    <thead style="background-color:#D3E9F1">
        <tr><th>Código</th><th>Tipo</th><th>Serie</th><th>Marca</th><th>Modelo</th><th>Proveedor</th><th>Estado</th></tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <tr class="<?= (int)$r['activo'] === 0 ? 'text-muted' : '' ?>">
            <td><?= htmlspecialchars($r['codigo_activo'] ?? ('EQ-' . $r['idequipo'])) ?></td>
            <td><?= htmlspecialchars($r['tipo_equipo'] ?? 'Otro') ?></td>
            <td><?= htmlspecialchars($r['numero_serie'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['nombreMarca']) ?></td>
            <td><?= htmlspecialchars($r['nombreModelo']) ?></td>
            <td><?= htmlspecialchars($r['proveedor'] ?: '—') ?></td>
            <td><?= htmlspecialchars(EquipoEstado::nombre((int)$r['estado_equipo'])) ?><?= (int)$r['activo']===0 ? ' <span class="badge app-badge-muted">Inactivo</span>' : '' ?></td>
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
