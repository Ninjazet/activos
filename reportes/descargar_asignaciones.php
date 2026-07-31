<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('reportes');
require_once __DIR__ . '/pdf_layout.php';

$db = Database::getInstance();
if (ob_get_level()) {
    ob_end_clean();
}

$filtro = TableFilter::text('buscar', 150, $_GET);
$estadoAsignacionFiltro = TableFilter::enum('estado_asignacion', ['activa', 'cerrada'], $_GET);
$resultadoEquipoFiltro = TableFilter::enum('resultado_equipo', ['1', '3', '4', '5'], $_GET);
$fechaDesdeFiltro = TableFilter::date('fecha_desde', $_GET);
$fechaHastaFiltro = TableFilter::date('fecha_hasta', $_GET);
$sql = "SELECT asg.idasignacion, asg.activa, asg.fecha_asignacion, asg.fecha_devolucion,
               asg.condicion_entrega, asg.condicion_devolucion, asg.estado_equipo_devolucion,
               CONCAT(em.nombre,' ',em.apellidos) AS empleado,
               CONCAT(COALESCE(eq.codigo_activo, CONCAT('EQ-',eq.idequipo)), ' - ', ma.nombreMarca, ' ', mo.nombreModelo) AS equipo,
               ar.descripcionarea AS area, ca.descripcioncargo AS cargo
        FROM asignacion asg
        INNER JOIN empleados em ON asg.idempleado=em.idempleado
        INNER JOIN equipo eq ON asg.idequipo=eq.idequipo
        INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo
        LEFT JOIN areas ar ON em.idarea=ar.idarea
        LEFT JOIN cargos ca ON em.idcargo=ca.idcargo";
$conditions = [];
$params = [];
if ($filtro !== '') {
    $conditions[] = "(CONCAT(em.nombre,' ',em.apellidos) LIKE ?
              OR CONCAT(ma.nombreMarca,' ',mo.nombreModelo) LIKE ?
              OR eq.codigo_activo LIKE ? OR ar.descripcionarea LIKE ?)";
    $like = "%$filtro%";
    $params = [$like, $like, $like, $like];
}
if ($estadoAsignacionFiltro !== '') { $conditions[] = 'asg.activa = ?'; $params[] = $estadoAsignacionFiltro === 'activa' ? 1 : 0; }
if ($resultadoEquipoFiltro !== '') { $conditions[] = 'asg.estado_equipo_devolucion = ?'; $params[] = (int)$resultadoEquipoFiltro; }
if ($fechaDesdeFiltro !== '') { $conditions[] = 'DATE(asg.fecha_asignacion) >= ?'; $params[] = $fechaDesdeFiltro; }
if ($fechaHastaFiltro !== '') { $conditions[] = 'DATE(asg.fecha_asignacion) <= ?'; $params[] = $fechaHastaFiltro; }
if ($conditions) { $sql .= ' WHERE ' . implode(' AND ', $conditions); }
$sql .= " ORDER BY asg.fecha_asignacion DESC, asg.idasignacion DESC";
$rows = $db->consulta($sql, $params);

$columns = [
    ['label' => '#',          'width' => 10, 'align' => 'C'],
    ['label' => 'Empleado',   'width' => 43, 'align' => 'L'],
    ['label' => 'Equipo',     'width' => 54, 'align' => 'L'],
    ['label' => 'Asignado',   'width' => 22, 'align' => 'C'],
    ['label' => 'Entrega',    'width' => 24, 'align' => 'C'],
    ['label' => 'Devuelto',   'width' => 22, 'align' => 'C'],
    ['label' => 'Devolución', 'width' => 28, 'align' => 'C'],
    ['label' => 'Resultado',  'width' => 30, 'align' => 'C'],
    ['label' => 'Estado',     'width' => 22, 'align' => 'C'],
];

$pdf = new GestActivosPDF('L', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->configureReport('REPORTE DE ASIGNACIONES', 'REP-ASG-001', count($rows) . ' movimiento(s) encontrado(s)', $columns, 12);
$pdf->SetMargins(12, 60, 12);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(8);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Reporte de Asignaciones');
$pdf->SetSubject('Historial de asignaciones');
$pdf->setCellPaddings(1.1, 0.8, 1.1, 0.8);
$pdf->AddPage();

foreach ($rows as $index => $row) {
    $pdf->tableRow([
        $row['idasignacion'],
        $row['empleado'],
        $row['equipo'],
        $row['fecha_asignacion'] ? date('d/m/Y', strtotime($row['fecha_asignacion'])) : '-',
        $row['condicion_entrega'] ?: 'Bueno',
        $row['fecha_devolucion'] ? date('d/m/Y', strtotime($row['fecha_devolucion'])) : '-',
        $row['condicion_devolucion'] ?: '-',
        EquipoEstado::nombre((int)$row['estado_equipo_devolucion'], '-'),
        (int)$row['activa'] === 1 ? 'Activa' : 'Devuelta',
    ], $index);
}

outputGestActivosPdf($pdf, 'ReporteAsignaciones_' . date('d_m_Y') . '.pdf');
