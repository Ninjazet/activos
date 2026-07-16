<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('reportes');
require_once __DIR__ . '/pdf_layout.php';

$db = Database::getInstance();
if (ob_get_level()) {
    ob_end_clean();
}

$filtro = trim($_REQUEST['buscar'] ?? '');
$sql = "SELECT em.idempleado, em.nombre, em.apellidos, em.telefono, em.correo, em.activo,
               ar.descripcionarea AS area, ca.descripcioncargo AS cargo
        FROM empleados em
        LEFT JOIN areas ar ON em.idarea=ar.idarea
        LEFT JOIN cargos ca ON em.idcargo=ca.idcargo";
$params = [];
if ($filtro !== '') {
    $sql .= " WHERE em.nombre LIKE ? OR em.apellidos LIKE ? OR em.idempleado LIKE ?
              OR em.telefono LIKE ? OR em.correo LIKE ?";
    $like = "%$filtro%";
    $params = [$like, $like, $like, $like, $like];
}
$sql .= " ORDER BY em.idempleado DESC";
$rows = $db->consulta($sql, $params);

$columns = [
    ['label' => 'ID',       'width' => 12, 'align' => 'C'],
    ['label' => 'Empleado', 'width' => 55, 'align' => 'L'],
    ['label' => 'Correo',   'width' => 52, 'align' => 'L'],
    ['label' => 'Telefono', 'width' => 27, 'align' => 'C'],
    ['label' => 'Area',     'width' => 35, 'align' => 'L'],
    ['label' => 'Cargo',    'width' => 38, 'align' => 'L'],
    ['label' => 'Estado',   'width' => 22, 'align' => 'C'],
];

$pdf = new GestActivosPDF('L', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->configureReport('REPORTE DE EMPLEADOS', 'REP-EMP-001', count($rows) . ' registro(s) encontrado(s)', $columns, 12);
$pdf->SetMargins(12, 60, 12);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(8);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Reporte de Empleados');
$pdf->SetSubject('Listado de empleados');
$pdf->setCellPaddings(1.2, 0.8, 1.2, 0.8);
$pdf->AddPage();

foreach ($rows as $index => $row) {
    $pdf->tableRow([
        $row['idempleado'],
        trim($row['nombre'] . ' ' . $row['apellidos']),
        $row['correo'] ?: '-',
        $row['telefono'] ?: '-',
        $row['area'] ?: '-',
        $row['cargo'] ?: '-',
        (int)$row['activo'] === 1 ? 'Activo' : 'Inactivo',
    ], $index);
}

outputGestActivosPdf($pdf, 'ReporteEmpleados_' . date('d_m_Y') . '.pdf');