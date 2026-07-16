<?php
// ============================================================
// GestActivos - Generar PDF: Reporte de Equipos
// ============================================================
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('reportes');

require_once BASE_PATH . '/lib/tcpdf/tcpdf.php';

$db = Database::getInstance();

if (ob_get_level()) {
    ob_end_clean();
}

class MYPDF extends TCPDF {
    public function Header() {
        $bMargin = $this->getBreakMargin();
        $auto_page_break = $this->AutoPageBreak;
        $this->SetAutoPageBreak(false, 0);
        $img_file = BASE_PATH . '/public/icons/windows.jpg';
        $this->Image($img_file, 85, 8, 50, 50, '', '', '', false, 30, '', false, false, 0);
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        $this->setPageMark();
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, 'mm', 'Letter', true, 'UTF-8', false);
$pdf->SetMargins(18, 35, 18);
$pdf->SetHeaderMargin(20);
$pdf->setPrintFooter(false);
$pdf->setPrintHeader(true);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Reporte de Equipos');

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(150, 20);
$pdf->Write(0, 'Código: REQ00001');
$pdf->SetXY(150, 25);
$pdf->Write(0, 'Fecha: ' . date('d-m-Y'));
$pdf->SetXY(150, 30);
$pdf->Write(0, 'Hora: ' . date('h:i A'));

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(15, 20);
$pdf->SetTextColor(204, 0, 0);
$pdf->Write(0, APP_NAME);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(15, 25);
$pdf->Write(0, 'Empresa: WEM');

$pdf->Ln(35);
$pdf->Cell(40, 26, '', 0, 0, 'C');
$pdf->SetTextColor(34, 68, 136);
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(50, 6, 'LISTA DE EQUIPOS', 0, 0, 'C');

$pdf->Ln(10);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetFillColor(232, 232, 232);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(27, 6, 'Codigo', 1, 0, 'C', 1);
$pdf->Cell(38, 6, 'Tipo', 1, 0, 'C', 1);
$pdf->Cell(30, 6, 'Marca', 1, 0, 'C', 1);
$pdf->Cell(38, 6, 'Modelo', 1, 0, 'C', 1);
$pdf->Cell(42, 6, 'Estado', 1, 1, 'C', 1);

$pdf->SetFont('helvetica', '', 10);

$filtro = trim($_REQUEST['buscar'] ?? '');
$sql = "SELECT eq.idequipo, eq.activo, eq.codigo_activo, eq.tipo_equipo, eq.estado_equipo, ma.nombreMarca, mo.nombreModelo
        FROM equipo eq
        INNER JOIN marca  ma ON eq.idmarca_equipo  = ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo";
$params = [];
if ($filtro !== '') {
    $sql   .= " WHERE ma.nombreMarca LIKE ? OR eq.idequipo LIKE ? OR mo.nombreModelo LIKE ? OR eq.codigo_activo LIKE ? OR eq.tipo_equipo LIKE ?";
    $params = ["%$filtro%", "%$filtro%", "%$filtro%", "%$filtro%", "%$filtro%"];
}
$sql .= " ORDER BY eq.idequipo DESC";
$rows = $db->consulta($sql, $params);

foreach ($rows as $r) {
    $estados = [1 => 'Disponible', 2 => 'Asignado', 3 => 'En mantenimiento', 4 => 'Perdido/robado', 5 => 'Dado de baja'];
    $estado = $estados[(int)$r['estado_equipo']] ?? 'Sin definir';
    if ((int)$r['activo'] === 0 && (int)$r['estado_equipo'] !== 5) {
        $estado .= ' (inactivo)';
    }
    $pdf->Cell(27, 6, $r['codigo_activo'] ?: ('EQ-' . $r['idequipo']), 1, 0, 'C');
    $pdf->Cell(38, 6, $r['tipo_equipo'] ?: 'Otro', 1, 0, 'C');
    $pdf->Cell(30, 6, $r['nombreMarca'], 1, 0, 'C');
    $pdf->Cell(38, 6, $r['nombreModelo'], 1, 0, 'C');
    $pdf->Cell(42, 6, $estado, 1, 1, 'C');
}

$pdf->Output('ReporteEquipos_' . date('d_m_y') . '.pdf', 'I');
