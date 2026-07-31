<?php
// ============================================================
// GestActivos - Asignaciones activas, entrega y devolución
// ============================================================
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('transacciones');

$db = Database::getInstance();
$q = TableFilter::text('query');
$preseleccionarEmpleado = max(0, (int)($_POST['preseleccionar_empleado'] ?? 0));
$preseleccionarEquipo = max(0, (int)($_POST['preseleccionar_equipo'] ?? 0));
$condicionEntregaFiltro = TableFilter::enum('condicion_entrega', ['Nuevo', 'Excelente', 'Bueno', 'Regular']);
$firmaEntregaFiltro = TableFilter::enum('firma_entrega', ['firmada', 'pendiente', 'no_requerida']);
$fechaDesdeFiltro = TableFilter::date('fecha_desde');
$fechaHastaFiltro = TableFilter::date('fecha_hasta');
$estadoDisponible = EquipoEstado::DISPONIBLE;
$estadoAsignado = EquipoEstado::ASIGNADO;

$sql = "SELECT asg.idasignacion, asg.idempleado, asg.idequipo, asg.fecha_asignacion,
               asg.condicion_entrega, asg.entrega_cargador, asg.entrega_maletin,
               asg.entrega_otros, asg.observaciones_entrega, asg.firma, asg.requiere_firma_entrega,
               CONCAT(em.nombre, ' ', em.apellidos) AS empleado,
               CONCAT(COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)), ' - ', ma.nombreMarca, ' ', mo.nombreModelo) AS equipo
        FROM asignacion asg
        INNER JOIN empleados em ON asg.idempleado = em.idempleado
        INNER JOIN equipo eq ON asg.idequipo = eq.idequipo
        INNER JOIN marca ma ON eq.idmarca_equipo = ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo
        WHERE asg.activa = 1";
$params = [];
if ($q !== '') {
    $sql .= " AND (CONCAT(em.nombre, ' ', em.apellidos) LIKE ?
              OR CONCAT(ma.nombreMarca, ' ', mo.nombreModelo) LIKE ?
              OR eq.codigo_activo LIKE ? OR asg.idasignacion LIKE ?)";
    $like = "%$q%";
    $params = [$like, $like, $like, $like];
}
if ($condicionEntregaFiltro !== '') {
    $sql .= ' AND asg.condicion_entrega = ?';
    $params[] = $condicionEntregaFiltro;
}
if ($firmaEntregaFiltro === 'firmada') {
    $sql .= " AND asg.firma IS NOT NULL AND asg.firma <> ''";
} elseif ($firmaEntregaFiltro === 'pendiente') {
    $sql .= " AND asg.requiere_firma_entrega = 1 AND (asg.firma IS NULL OR asg.firma = '')";
} elseif ($firmaEntregaFiltro === 'no_requerida') {
    $sql .= ' AND asg.requiere_firma_entrega = 0';
}
if ($fechaDesdeFiltro !== '') {
    $sql .= ' AND DATE(asg.fecha_asignacion) >= ?';
    $params[] = $fechaDesdeFiltro;
}
if ($fechaHastaFiltro !== '') {
    $sql .= ' AND DATE(asg.fecha_asignacion) <= ?';
    $params[] = $fechaHastaFiltro;
}
$sql .= " ORDER BY asg.fecha_asignacion DESC, asg.idasignacion DESC";
$resultado = $db->consulta($sql, $params);

$emps = $db->consulta(
    "SELECT idempleado, nombre, apellidos FROM empleados WHERE activo=1 ORDER BY nombre, apellidos"
);
$eqsDisponibles = $db->consulta(
    "SELECT eq.idequipo, eq.codigo_activo, ma.nombreMarca, mo.nombreModelo
     FROM equipo eq
     INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
     INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo
     WHERE eq.activo=1 AND eq.estado_equipo={$estadoDisponible}
       AND NOT EXISTS (SELECT 1 FROM asignacion asg WHERE asg.idequipo=eq.idequipo AND asg.activa=1)
     ORDER BY eq.codigo_activo, ma.nombreMarca, mo.nombreModelo"
);
$eqsTodos = $db->consulta(
    "SELECT eq.idequipo, eq.codigo_activo, ma.nombreMarca, mo.nombreModelo
     FROM equipo eq
     INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
     INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo
     WHERE eq.activo=1 AND eq.estado_equipo IN ({$estadoDisponible},{$estadoAsignado})
     ORDER BY eq.codigo_activo, ma.nombreMarca, mo.nombreModelo"
);

$empleadoPreseleccionable = false;
foreach ($emps as $empleadoOpcion) {
    if ((int)$empleadoOpcion['idempleado'] === $preseleccionarEmpleado) {
        $empleadoPreseleccionable = true;
        break;
    }
}
$equipoPreseleccionable = false;
foreach ($eqsDisponibles as $equipoOpcion) {
    if ((int)$equipoOpcion['idequipo'] === $preseleccionarEquipo) {
        $equipoPreseleccionable = true;
        break;
    }
}
$abrirModalNuevo = $empleadoPreseleccionable || $equipoPreseleccionable;
?>

<?php if ($resultado): ?>
<p class="responsive-table-note assignment-mobile-note" id="assignmentMobileHelp">
    <i class="fa fa-hand-pointer" aria-hidden="true"></i>
    Toca una fila para mostrar sus opciones.
</p>
<table class="table table-bordered table-striped" id="datosE">
    <thead>
        <tr>
            <th>ID</th><th>Empleado</th><th>Equipo</th><th>Condición entrega</th>
            <th>Accesorios</th><th>Asignado desde</th><th>Acta de entrega</th><th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($resultado as $r): ?>
        <?php
        $accesorios = [];
        if ((int)$r['entrega_cargador'] === 1) { $accesorios[] = 'Cargador'; }
        if ((int)$r['entrega_maletin'] === 1) { $accesorios[] = 'Maletín'; }
        if (!empty($r['entrega_otros'])) { $accesorios[] = $r['entrega_otros']; }
        $textoAccesorios = $accesorios ? implode(', ', $accesorios) : 'Sin accesorios';
        ?>
        <tr data-idempleado="<?= (int)$r['idempleado'] ?>"
            data-idequipo="<?= (int)$r['idequipo'] ?>"
            data-condicion-entrega="<?= htmlspecialchars($r['condicion_entrega'], ENT_QUOTES) ?>"
            data-entrega-cargador="<?= (int)$r['entrega_cargador'] ?>"
            data-entrega-maletin="<?= (int)$r['entrega_maletin'] ?>"
            data-entrega-otros="<?= htmlspecialchars($r['entrega_otros'] ?? '', ENT_QUOTES) ?>"
            data-observaciones-entrega="<?= htmlspecialchars($r['observaciones_entrega'] ?? '', ENT_QUOTES) ?>">
            <td><?= (int)$r['idasignacion'] ?></td>
            <td><?= htmlspecialchars($r['empleado']) ?></td>
            <td><?= htmlspecialchars($r['equipo']) ?></td>
            <td><span class="badge app-badge-info"><?= htmlspecialchars($r['condicion_entrega']) ?></span></td>
            <td><?= htmlspecialchars($textoAccesorios) ?></td>
            <td><?= $r['fecha_asignacion'] ? date('d/m/Y', strtotime($r['fecha_asignacion'])) : '—' ?></td>
            <td>
                <?= !empty($r['firma'])
                    ? '<span class="badge app-badge-success"><i class="fa fa-check" aria-hidden="true"></i> Firmada</span>'
                    : '<span class="badge app-badge-warning"><i class="fa fa-clock" aria-hidden="true"></i> Pendiente</span>' ?>
            </td>
            <td class="table-actions assignment-actions">
                <?php if (empty($r['firma'])): ?>
                <a href="#" title="Editar asignación y checklist" aria-label="Editar asignación y checklist" onclick="return modalEdit(event);"
                   data-bs-toggle="modal" data-bs-target="#editModal"><span class="fa fa-edit" aria-hidden="true"></span></a>
                <?php endif; ?>
                <?php if ((int)$r['requiere_firma_entrega'] === 1 && empty($r['firma'])): ?>
                <button type="button" class="table-action-disabled" disabled
                        title="Debe firmar el acta de entrega antes de devolver este equipo"
                        aria-label="Devolución bloqueada: primero debe firmar el acta de entrega">
                    <span class="fa fa-rotate-left" aria-hidden="true"></span>
                </button>
                <?php else: ?>
                <a href="#" title="Devolver equipo" aria-label="Registrar devolución del equipo" onclick="return modalDevolver(event);"
                   data-bs-toggle="modal" data-bs-target="#devolucionModal">
                    <span class="fa fa-rotate-left" aria-hidden="true"></span>
                </a>
                <?php endif; ?>
                <?php if (!empty($r['firma'])): ?>
                <a href="<?= BASE_URL ?>/reportes/acta_asignacion.php?idasignacion=<?= (int)$r['idasignacion'] ?>"
                   target="_blank" rel="noopener" title="Ver acta de entrega firmada" aria-label="Abrir acta de entrega firmada">
                    <span class="fa fa-file-circle-check" aria-hidden="true"></span>
                </a>
                <?php else: ?>
                <a href="#" title="Firmar acta de entrega" aria-label="Firmar acta de entrega" onclick="return modalFirmarEntrega(event);"
                   data-bs-toggle="modal" data-bs-target="#firmarModal">
                    <span class="fa fa-file-signature" aria-hidden="true"></span>
                </a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require BASE_PATH . '/app/views/transacciones/asignaciones/scripts_tabla.php'; ?>
<?php else: ?>
<p class="lead"><em>No hay asignaciones activas.</em></p>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/transacciones/asignaciones/modal_nueva.php'; ?>
<?php require BASE_PATH . '/app/views/transacciones/asignaciones/modal_editar.php'; ?>
<?php require BASE_PATH . '/app/views/transacciones/asignaciones/modal_firma.php'; ?>
<?php require BASE_PATH . '/app/views/transacciones/asignaciones/modal_devolucion.php'; ?>
<?php require BASE_PATH . '/app/views/transacciones/asignaciones/scripts_formularios.php'; ?>
