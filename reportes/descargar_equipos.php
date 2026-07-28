<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('reportes');
require_once __DIR__ . '/pdf_layout.php';

$db = Database::getInstance();
if (ob_get_level()) {
    ob_end_clean();
}

$filtro = TableFilter::text('buscar', 150, $_GET);
$estadoEquipoFiltro = TableFilter::enum('estado_equipo', ['1', '2', '3', '4', '5'], $_GET);
$tipoEquipoFiltro = TableFilter::text('tipo_equipo', 50, $_GET);
$marcaFiltro = TableFilter::positiveInt('idmarca', $_GET);
$modeloFiltro = TableFilter::positiveInt('idmodelo', $_GET);
$activoFiltro = TableFilter::enum('activo', ['0', '1'], $_GET);
$garantiaFiltro = TableFilter::enum('garantia', ['vigente', 'vence_30', 'vencida', 'sin_fecha'], $_GET);
$sql = "SELECT eq.idequipo, eq.activo, eq.codigo_activo, eq.tipo_equipo, eq.numero_serie,
               eq.estado_equipo, eq.fecha_compra, eq.vencimiento_garantia,
               ma.nombreMarca, mo.nombreModelo
        FROM equipo eq
        INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo";
$conditions = [];
$params = [];
if ($filtro !== '') {
    $conditions[] = '(ma.nombreMarca LIKE ? OR mo.nombreModelo LIKE ? OR eq.codigo_activo LIKE ? OR eq.numero_serie LIKE ? OR eq.tipo_equipo LIKE ?)';
    $like = "%$filtro%";
    $params = [$like, $like, $like, $like, $like];
}
if ($estadoEquipoFiltro !== '') { $conditions[] = 'eq.estado_equipo = ?'; $params[] = (int)$estadoEquipoFiltro; }
if ($tipoEquipoFiltro !== '') { $conditions[] = 'eq.tipo_equipo = ?'; $params[] = $tipoEquipoFiltro; }
if ($marcaFiltro > 0) { $conditions[] = 'eq.idmarca_equipo = ?'; $params[] = $marcaFiltro; }
if ($modeloFiltro > 0) { $conditions[] = 'eq.idmodelo_equipo = ?'; $params[] = $modeloFiltro; }
if ($activoFiltro !== '') { $conditions[] = 'eq.activo = ?'; $params[] = (int)$activoFiltro; }
if ($garantiaFiltro === 'vigente') { $conditions[] = 'eq.vencimiento_garantia >= CURDATE()'; }
elseif ($garantiaFiltro === 'vence_30') { $conditions[] = 'eq.vencimiento_garantia BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)'; }
elseif ($garantiaFiltro === 'vencida') { $conditions[] = 'eq.vencimiento_garantia < CURDATE()'; }
elseif ($garantiaFiltro === 'sin_fecha') { $conditions[] = 'eq.vencimiento_garantia IS NULL'; }
if ($conditions) { $sql .= ' WHERE ' . implode(' AND ', $conditions); }
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
