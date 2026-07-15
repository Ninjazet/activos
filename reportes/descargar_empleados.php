<?php
// ============================================================
// GestActivos - Generar PDF: Reporte de Empleados
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
$pdf->SetMargins(35, 35, 20);
$pdf->SetHeaderMargin(20);
$pdf->setPrintFooter(false);
$pdf->setPrintHeader(true);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Reporte de Empleados');

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(150, 20);
$pdf->Write(0, 'Código: REE00001');
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
$pdf->Cell(50, 6, 'LISTA DE EMPLEADOS', 0, 0, 'C');

$pdf->Ln(10);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetFillColor(232, 232, 232);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(10, 6, 'Id', 1, 0, 'C', 1);
$pdf->Cell(33, 6, 'Nombre', 1, 0, 'C', 1);
$pdf->Cell(33, 6, 'Apellidos', 1, 0, 'C', 1);
$pdf->Cell(12, 6, 'Edad', 1, 0, 'C', 1);
$pdf->Cell(27, 6, 'Teléfono', 1, 0, 'C', 1);
$pdf->Cell(40, 6, 'Dirección', 1, 0, 'C', 1);
$pdf->Cell(20, 6, 'Estado', 1, 1, 'C', 1);

$pdf->SetFont('helvetica', '', 9);

$filtro = trim($_REQUEST['buscar'] ?? '');
$sql = "SELECT idempleado, nombre, apellidos, edad, telefono, direccion, activo FROM empleados";
$params = [];
if ($filtro !== '') {
    $sql   .= " WHERE nombre LIKE ? OR apellidos LIKE ? OR idempleado LIKE ? OR telefono LIKE ?";
    $params = ["%$filtro%", "%$filtro%", "%$filtro%", "%$filtro%"];
}
$sql .= " ORDER BY nombre, apellidos";
$rows = $db->consulta($sql, $params);

foreach ($rows as $r) {
    $pdf->Cell(10, 6, $r['idempleado'], 1, 0, 'C');
    $pdf->Cell(33, 6, $r['nombre'], 1, 0, 'L');
    $pdf->Cell(33, 6, $r['apellidos'], 1, 0, 'L');
    $pdf->Cell(12, 6, $r['edad'], 1, 0, 'C');
    $pdf->Cell(27, 6, $r['telefono'], 1, 0, 'C');
    $pdf->Cell(40, 6, $r['direccion'], 1, 0, 'L');
    $pdf->Cell(20, 6, (int)$r['activo'] === 1 ? 'Activo' : 'Inactivo', 1, 1, 'C');
}

$pdf->Output('ReporteEmpleados_' . date('d_m_y') . '.pdf', 'I');
