<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('reportes');
require_once __DIR__ . '/pdf_layout.php';

$db = Database::getInstance();
if (ob_get_level()) {
    ob_end_clean();
}

$filtro = trim($_REQUEST['buscar'] ?? '');
$sql = "SELECT eq.idequipo, eq.activo, eq.codigo_activo, eq.tipo_equipo, eq.numero_serie,
               eq.estado_equipo, eq.fecha_compra, eq.vencimiento_garantia,
               ma.nombreMarca, mo.nombreModelo
        FROM equipo eq
        INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo";
$params = [];
if ($filtro !== '') {
    $sql .= " WHERE ma.nombreMarca LIKE ? OR mo.nombreModelo LIKE ? OR eq.codigo_activo LIKE ?
              OR eq.numero_serie LIKE ? OR eq.tipo_equipo LIKE ?";
    $like = "%$filtro%";
    $params = [$like, $like, $like, $like, $like];
}
$sql .= " ORDER BY eq.idequipo DESC";
$rows = $db->consulta($sql, $params);

$columns = [
    ['label' => 'Codigo',   'width' => 25, 'align' => 'C'],
    ['label' => 'Tipo',     'width' => 32, 'align' => 'L'],
    ['label' => 'Serie',    'width' => 38, 'align' => 'L'],
    ['label' => 'Marca',    'width' => 30, 'align' => 'L'],
    ['label' => 'Modelo',   'width' => 42, 'align' => 'L'],
    ['label' => 'Compra',   'width' => 24, 'align' => 'C'],
    ['label' => 'Garantia', 'width' => 24, 'align' => 'C'],
    ['label' => 'Estado',   'width' => 35, 'align' => 'C'],
];

$pdf = new GestActivosPDF('L', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->configureReport('REPORTE DE EQUIPOS', 'REP-EQP-001', count($rows) . ' activo(s) encontrado(s)', $columns, 14);
$pdf->SetMargins(14, 60, 14);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(8);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Reporte de Equipos');
$pdf->SetSubject('Inventario de equipos');
$pdf->setCellPaddings(1.2, 0.8, 1.2, 0.8);
$pdf->AddPage();

$estados = [1 => 'Disponible', 2 => 'Asignado', 3 => 'En mantenimiento', 4 => 'Perdido o robado', 5 => 'Dado de baja'];
foreach ($rows as $index => $row) {
    $estado = $estados[(int)$row['estado_equipo']] ?? 'Sin definir';
    if ((int)$row['activo'] === 0 && (int)$row['estado_equipo'] !== 5) {
        $estado .= ' / Inactivo';
    }
    $pdf->tableRow([
        $row['codigo_activo'] ?: ('EQ-' . $row['idequipo']),
        $row['tipo_equipo'] ?: 'Otro',
        $row['numero_serie'] ?: '-',
        $row['nombreMarca'],
        $row['nombreModelo'],
        $row['fecha_compra'] ? date('d/m/Y', strtotime($row['fecha_compra'])) : '-',
        $row['vencimiento_garantia'] ? date('d/m/Y', strtotime($row['vencimiento_garantia'])) : '-',
        $estado,
    ], $index);
}

outputGestActivosPdf($pdf, 'ReporteEquipos_' . date('d_m_Y') . '.pdf');