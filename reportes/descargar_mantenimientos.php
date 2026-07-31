<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('reportes');
require_once __DIR__ . '/pdf_layout.php';
if (ob_get_level()) { ob_end_clean(); }

$filtros = MantenimientoService::leerFiltros([
    'query' => $_GET['buscar'] ?? '',
    'tipo' => $_GET['tipo'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'idproveedor' => $_GET['idproveedor'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
]);
$rows = (new MantenimientoService(Database::getInstance()))->listar($filtros);

$columns = [
    ['label'=>'#','width'=>8,'align'=>'C'],
    ['label'=>'Equipo','width'=>42,'align'=>'L'],
    ['label'=>'Tipo','width'=>18,'align'=>'C'],
    ['label'=>'Estado','width'=>20,'align'=>'C'],
    ['label'=>'Ingreso','width'=>24,'align'=>'C'],
    ['label'=>'Cierre','width'=>24,'align'=>'C'],
    ['label'=>'Proveedor','width'=>32,'align'=>'L'],
    ['label'=>'Problema','width'=>40,'align'=>'L'],
    ['label'=>'Resultado','width'=>22,'align'=>'C'],
    ['label'=>'Costo','width'=>24,'align'=>'R'],
];
$total = array_reduce($rows, static fn(float $suma, array $fila): float => $suma + (float)($fila['costo'] ?? 0), 0.0);
$pdf = new GestActivosPDF('L', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->configureReport('REPORTE DE MANTENIMIENTOS', 'REP-MANT-001', count($rows) . ' registro(s) · Costo total L ' . number_format($total, 2), $columns, 12);
$pdf->SetMargins(12, 60, 12);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(8);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Reporte de Mantenimientos');
$pdf->SetSubject('Historial técnico de activos');
$pdf->setCellPaddings(1.1, 0.8, 1.1, 0.8);
$pdf->AddPage();
foreach ($rows as $index => $row) {
    $pdf->tableRow([
        $row['idmantenimiento'],
        ($row['codigo_activo'] ?: 'EQ-' . $row['idequipo']) . ' · ' . $row['nombreMarca'] . ' ' . $row['nombreModelo'],
        $row['tipo'], $row['estado'],
        date('d/m/Y', strtotime($row['fecha_ingreso'])),
        $row['fecha_cierre'] ? date('d/m/Y', strtotime($row['fecha_cierre'])) : '-',
        $row['proveedor'] ?: 'Soporte interno',
        $row['descripcion_problema'],
        $row['resultado'] ?: '-',
        $row['costo'] !== null ? 'L ' . number_format((float)$row['costo'], 2) : '-',
    ], $index);
}
outputGestActivosPdf($pdf, 'ReporteMantenimientos_' . date('d_m_Y') . '.pdf');
