<?php
require_once __DIR__ . '/bootstrap.php';
Auth::requerir();
Auth::guardarPagina();
$db = Database::getInstance();

$estadoDisponible = EquipoEstado::DISPONIBLE;
$estadoAsignado = EquipoEstado::ASIGNADO;
$estadoMantenimiento = EquipoEstado::MANTENIMIENTO;
$estadoPerdido = EquipoEstado::PERDIDO_ROBADO;
$estadoBaja = EquipoEstado::BAJA;

$resumen = $db->fila(
    "SELECT COUNT(*) total, COALESCE(SUM(activo=1),0) activos,
     COALESCE(SUM(activo=1 AND estado_equipo={$estadoDisponible}),0) disponibles,
     COALESCE(SUM(activo=1 AND estado_equipo={$estadoAsignado}),0) asignados,
     COALESCE(SUM(activo=1 AND estado_equipo={$estadoMantenimiento}),0) mantenimiento,
     COALESCE(SUM(estado_equipo={$estadoPerdido}),0) perdidos,
     COALESCE(SUM(estado_equipo={$estadoBaja}),0) bajas FROM equipo"
) ?? [];

$metricas = [
    'equipos' => (int)($resumen['total'] ?? 0),
    'disponibles' => (int)($resumen['disponibles'] ?? 0),
    'asignados' => (int)($resumen['asignados'] ?? 0),
    'mantenimiento' => (int)($resumen['mantenimiento'] ?? 0),
    'perdidos' => (int)($resumen['perdidos'] ?? 0),
    'bajas' => (int)($resumen['bajas'] ?? 0),
    'empleados' => $db->contar("SELECT COUNT(*) FROM empleados WHERE activo=1"),
    'asignaciones' => $db->contar("SELECT COUNT(*) FROM asignacion WHERE activa=1"),
    'firmas' => $db->contar("SELECT COUNT(*) FROM asignacion WHERE activa=1 AND requiere_firma_entrega=1 AND (firma IS NULL OR firma='')"),
    'garantias_vencidas' => $db->contar("SELECT COUNT(*) FROM equipo WHERE activo=1 AND vencimiento_garantia IS NOT NULL AND vencimiento_garantia<CURDATE()"),
    'garantias_proximas' => $db->contar("SELECT COUNT(*) FROM equipo WHERE activo=1 AND vencimiento_garantia BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)"),
];

$movimientos = $db->consulta(
    "SELECT a.idasignacion,a.fecha_asignacion,a.fecha_devolucion,
     e.nombre,e.apellidos,q.idequipo,q.codigo_activo,q.tipo_equipo
     FROM asignacion a
     LEFT JOIN empleados e ON e.idempleado=a.idempleado
     LEFT JOIN equipo q ON q.idequipo=a.idequipo
     ORDER BY COALESCE(a.fecha_devolucion,a.fecha_asignacion) DESC,a.idasignacion DESC LIMIT 6"
);

$permisos = [
    'maestros' => ($_SESSION['maestros'] ?? '0') == '1',
    'transacciones' => ($_SESSION['transacciones'] ?? '0') == '1',
    'mantenimientos' => ($_SESSION['mantenimientos'] ?? '0') == '1',
    'consultas' => ($_SESSION['consultas'] ?? '0') == '1',
    'reportes' => ($_SESSION['reportes'] ?? '0') == '1',
    'seguridad' => ($_SESSION['seguridad'] ?? '0') == '1',
];
$acciones = [];
if ($permisos['transacciones']) $acciones[] = ['asignarequipo.php','fa-laptop-file','Nueva asignación','Entregar un equipo disponible'];
if ($permisos['mantenimientos']) $acciones[] = ['mantenimientos.php','fa-screwdriver-wrench','Mantenimientos','Abrir y dar seguimiento a reparaciones'];
if ($permisos['maestros']) {
    $acciones[] = ['equipos.php','fa-laptop','Inventario','Registrar o actualizar equipos'];
    $acciones[] = ['empleados.php','fa-users','Personal','Administrar empleados'];
}
if ($permisos['consultas']) $acciones[] = ['consultas/asignaciones.php','fa-magnifying-glass','Consultar asignaciones','Revisar el historial completo'];
if ($permisos['reportes']) $acciones[] = ['reportes/asignaciones.php','fa-file-pdf','Reportes','Consultar y descargar información'];
if ($permisos['seguridad']) $acciones[] = ['usuarios.php','fa-user-shield','Usuarios y permisos','Administrar accesos'];

$urlEquipos = $permisos['maestros'] ? 'equipos.php' : ($permisos['consultas'] ? 'consultas/equipos.php' : null);
$urlAsignaciones = $permisos['transacciones'] ? 'asignarequipo.php' : ($permisos['consultas'] ? 'consultas/asignaciones.php' : null);
$urlMantenimientos = $permisos['mantenimientos'] ? 'mantenimientos.php' : ($permisos['consultas'] ? 'consultas/mantenimientos.php' : $urlEquipos);
$alertas = [];
if ($metricas['firmas']) $alertas[] = ['warning','fa-signature',$metricas['firmas'],'entrega(s) pendiente(s) de firma',$urlAsignaciones];
if ($metricas['mantenimiento']) $alertas[] = ['info','fa-screwdriver-wrench',$metricas['mantenimiento'],'equipo(s) en mantenimiento',$urlMantenimientos];
if ($metricas['garantias_vencidas']) $alertas[] = ['danger','fa-shield-halved',$metricas['garantias_vencidas'],'garantía(s) vencida(s)',$urlEquipos];
if ($metricas['garantias_proximas']) $alertas[] = ['warning','fa-calendar-day',$metricas['garantias_proximas'],'garantía(s) vencen en 30 días',$urlEquipos];
if ($metricas['perdidos']) $alertas[] = ['danger','fa-triangle-exclamation',$metricas['perdidos'],'equipo(s) perdido(s) o robado(s)',$urlEquipos];

$estados = [
    [EquipoEstado::nombre(EquipoEstado::DISPONIBLE),$metricas['disponibles'],'available'],
    [EquipoEstado::nombre(EquipoEstado::ASIGNADO),$metricas['asignados'],'assigned'],
    [EquipoEstado::nombre(EquipoEstado::MANTENIMIENTO),$metricas['mantenimiento'],'maintenance'],
    [EquipoEstado::nombre(EquipoEstado::PERDIDO_ROBADO),$metricas['perdidos'],'lost'],
    [EquipoEstado::nombre(EquipoEstado::BAJA),$metricas['bajas'],'retired'],
];
$mostrarEstadoEquipos = false; // Cambiar a true para reactivar el bloque completo.
$hora = (int)date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches');
$dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$meses = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fechaActual = $dias[(int)date('w')].', '.date('j').' de '.$meses[(int)date('n')].' de '.date('Y');
$total = max(1,$metricas['equipos']);
$pageTitle = 'Inicio';
require BASE_PATH . '/app/views/layouts/encabezado.php';
?>
<main class="dashboard-shell">
  <section class="dashboard-welcome">
    <div class="dashboard-welcome-copy">
      <span class="dashboard-eyebrow"><?= htmlspecialchars($fechaActual) ?></span>
      <h1><?= $saludo ?>, <?= htmlspecialchars(Auth::get('nombre')) ?></h1>
      <p>Este es el estado actual de los activos y las asignaciones.</p>
    </div>
    <div class="dashboard-brand">
      <img src="<?= BASE_URL ?>/public/icons/logo.png" alt="Logo de <?= htmlspecialchars(APP_NAME) ?>">
      <div><strong><?= htmlspecialchars(APP_NAME) ?></strong><span>Control de activos</span></div>
    </div>
  </section>

  <section class="dashboard-metrics" aria-label="Resumen general">
    <article class="metric-card metric-purple"><div><span>Equipos registrados</span><strong><?= $metricas['equipos'] ?></strong><small><?= (int)($resumen['activos'] ?? 0) ?> activos en inventario</small></div></article>
    <article class="metric-card metric-green"><div><span>Disponibles</span><strong><?= $metricas['disponibles'] ?></strong><small>Listos para asignar</small></div></article>
    <article class="metric-card metric-blue"><div><span>Asignaciones activas</span><strong><?= $metricas['asignaciones'] ?></strong><small><?= $metricas['asignados'] ?> equipos marcados como asignados</small></div></article>
    <article class="metric-card metric-orange"><div><span>Empleados activos</span><strong><?= $metricas['empleados'] ?></strong><small>Personal habilitado</small></div></article>
  </section>

  <div class="dashboard-main-grid<?= $mostrarEstadoEquipos ? '' : ' dashboard-main-grid-single' ?>">
    <?php if ($mostrarEstadoEquipos): ?>
    <section class="dashboard-panel">
      <div class="dashboard-panel-header">
        <div><span class="panel-kicker">Inventario</span><h2>Estado de los equipos</h2></div>
        <?php if ($urlEquipos): ?><a href="<?= BASE_URL ?>/<?= $urlEquipos ?>">Ver inventario <i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
      </div>
      <div class="inventory-status-list">
        <?php foreach ($estados as [$etiqueta,$cantidad,$clase]): ?>
        <div class="inventory-status-row">
          <div class="inventory-status-label"><span><i class="status-dot status-<?= $clase ?>"></i><?= htmlspecialchars($etiqueta) ?></span><strong><?= $cantidad ?></strong></div>
          <div class="status-track"><span class="status-fill status-<?= $clase ?>" style="width:<?= round($cantidad/$total*100) ?>%"></span></div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <section class="dashboard-panel attention-panel">
      <div class="dashboard-panel-header"><div><span class="panel-kicker">Seguimiento</span><h2>Requiere atención</h2></div><span class="attention-count"><?= count($alertas) ?></span></div>
      <?php if ($alertas): ?>
      <div class="attention-list">
        <?php foreach ($alertas as [$tipo,$icono,$cantidad,$texto,$url]): ?>
          <?php if ($url): ?><a href="<?= BASE_URL ?>/<?= $url ?>" class="attention-item attention-<?= $tipo ?>"><?php else: ?><div class="attention-item attention-<?= $tipo ?>"><?php endif; ?>
            <span class="attention-icon"><i class="fa-solid <?= $icono ?>"></i></span>
            <span><strong><?= $cantidad ?></strong> <?= htmlspecialchars($texto) ?></span>
            <?php if ($url): ?><i class="fa-solid fa-chevron-right"></i><?php endif; ?>
          <?php if ($url): ?></a><?php else: ?></div><?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="dashboard-empty dashboard-empty-success"><i class="fa-solid fa-circle-check"></i><strong>Todo está al día</strong><span>No hay alertas operativas pendientes.</span></div>
      <?php endif; ?>
    </section>
  </div>

  <div class="dashboard-lower-grid">
    <section class="dashboard-panel">
      <div class="dashboard-panel-header">
        <div><span class="panel-kicker">Actividad</span><h2>Movimientos recientes</h2></div>
        <?php if ($urlAsignaciones): ?><a href="<?= BASE_URL ?>/<?= $urlAsignaciones ?>">Ver historial <i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
      </div>
      <?php if ($movimientos): ?>
      <div class="dashboard-table-wrap"><table class="dashboard-table">
        <thead><tr><th>Empleado</th><th>Equipo</th><th>Movimiento</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach ($movimientos as $m):
          $devuelto = !empty($m['fecha_devolucion']);
          $fecha = $devuelto ? $m['fecha_devolucion'] : $m['fecha_asignacion'];
          $empleado = trim(($m['nombre'] ?? '').' '.($m['apellidos'] ?? '')) ?: 'Empleado no disponible';
          $codigo = $m['codigo_activo'] ?: 'Equipo #'.($m['idequipo'] ?? $m['idasignacion']);
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($empleado) ?></strong></td>
          <td><span class="asset-code"><?= htmlspecialchars($codigo) ?></span><small><?= htmlspecialchars($m['tipo_equipo'] ?: 'Equipo') ?></small></td>
          <td><span class="movement-badge <?= $devuelto ? 'movement-returned' : 'movement-active' ?>"><i class="fa-solid <?= $devuelto ? 'fa-rotate-left' : 'fa-arrow-right' ?>"></i><?= $devuelto ? 'Devolución' : 'Asignación' ?></span></td>
          <td><?= $fecha ? date('d/m/Y · h:i a',strtotime($fecha)) : 'Sin fecha' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php else: ?>
      <div class="dashboard-empty"><i class="fa-solid fa-inbox"></i><strong>Sin movimientos</strong><span>Las asignaciones aparecerán aquí.</span></div>
      <?php endif; ?>
    </section>

    <aside class="dashboard-panel">
      <div class="dashboard-panel-header"><div><span class="panel-kicker">Atajos</span><h2>Acciones rápidas</h2></div></div>
      <div class="quick-actions">
      <?php foreach ($acciones as [$url,$icono,$titulo,$detalle]): ?>
        <a href="<?= BASE_URL ?>/<?= $url ?>" class="quick-action">
          <span class="quick-action-icon"><i class="fa-solid <?= $icono ?>"></i></span>
          <span><strong><?= htmlspecialchars($titulo) ?></strong><small><?= htmlspecialchars($detalle) ?></small></span>
          <i class="fa-solid fa-chevron-right"></i>
        </a>
      <?php endforeach; ?>
      <?php if (!$acciones): ?><div class="dashboard-empty"><i class="fa-solid fa-lock"></i><strong>Sin accesos asignados</strong><span>Solicita permisos al administrador.</span></div><?php endif; ?>
      </div>
    </aside>
  </div>
</main>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
