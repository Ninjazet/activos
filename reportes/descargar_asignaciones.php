<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('reportes');
require_once __DIR__ . '/pdf_layout.php';

$db = Database::getInstance();
if (ob_get_level()) {
    ob_end_clean();
}

$filtro = trim($_REQUEST['buscar'] ?? '');
$sql = "SELECT asg.idasignacion, asg.activa, asg.fecha_asignacion, asg.fecha_devolucion,
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
$params = [];
if ($filtro !== '') {
    $sql .= " WHERE CONCAT(em.nombre,' ',em.apellidos) LIKE ?
              OR CONCAT(ma.nombreMarca,' ',mo.nombreModelo) LIKE ?
              OR eq.codigo_activo LIKE ? OR ar.descripcionarea LIKE ?";
    $like = "%$filtro%";
    $params = [$like, $like, $like, $like];
}
$sql .= " ORDER BY asg.fecha_asignacion DESC, asg.idasignacion DESC";
$rows = $db->consulta($sql, $params);

$columns = [
    ['label' => '#',         'width' => 12, 'align' => 'C'],
    ['label' => 'Empleado',  'width' => 48, 'align' => 'L'],
    ['label' => 'Equipo',    'width' => 58, 'align' => 'L'],
    ['label' => 'Area',      'width' => 30, 'align' => 'L'],
    ['label' => 'Cargo',     'width' => 35, 'align' => 'L'],
    ['label' => 'Asignado',  'width' => 25, 'align' => 'C'],
    ['label' => 'Devuelto',  'width' => 25, 'align' => 'C'],
    ['label' => 'Estado',    'width' => 22, 'align' => 'C'],
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
        $row['area'] ?: '-',
        $row['cargo'] ?: '-',
        $row['fecha_asignacion'] ? date('d/m/Y', strtotime($row['fecha_asignacion'])) : '-',
        $row['fecha_devolucion'] ? date('d/m/Y', strtotime($row['fecha_devolucion'])) : '-',
        (int)$row['activa'] === 1 ? 'Activa' : 'Devuelta',
    ], $index);
}

outputGestActivosPdf($pdf, 'ReporteAsignaciones_' . date('d_m_Y') . '.pdf');