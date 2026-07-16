<?php
// ============================================================
// GestActivos - Generar PDF: Reporte de Asignaciones
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

$pdf = new MYPDF('L', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->SetMargins(20, 35, 20);
$pdf->SetHeaderMargin(20);
$pdf->setPrintFooter(false);
$pdf->setPrintHeader(true);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Reporte de Asignaciones');

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(220, 20);
$pdf->Write(0, 'Código: RAS00001');
$pdf->SetXY(220, 25);
$pdf->Write(0, 'Fecha: ' . date('d-m-Y'));
$pdf->SetXY(220, 30);
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
$pdf->Cell(50, 6, 'LISTA DE ASIGNACIONES', 0, 0, 'C');

$pdf->Ln(10);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetFillColor(232, 232, 232);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(12, 6, '#', 1, 0, 'C', 1);
$pdf->Cell(48, 6, 'Empleado', 1, 0, 'C', 1);
$pdf->Cell(48, 6, 'Equipo', 1, 0, 'C', 1);
$pdf->Cell(35, 6, 'Área', 1, 0, 'C', 1);
$pdf->Cell(35, 6, 'Cargo', 1, 0, 'C', 1);
$pdf->Cell(25, 6, 'Asignado', 1, 0, 'C', 1);
$pdf->Cell(22, 6, 'Estado', 1, 1, 'C', 1);

$pdf->SetFont('helvetica', '', 9);

$filtro = trim($_REQUEST['buscar'] ?? '');
$sql = "SELECT asg.idasignacion, asg.activa, asg.fecha_asignacion,
               CONCAT(em.nombre,' ',em.apellidos) AS empleado,
               CONCAT(COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)), ' - ', ma.nombreMarca, ' ', mo.nombreModelo) AS equipo,
               ar.descripcionarea AS area,
               ca.descripcioncargo AS cargo
        FROM asignacion asg
        INNER JOIN empleados em ON asg.idempleado = em.idempleado
        INNER JOIN equipo eq    ON asg.idequipo   = eq.idequipo
        INNER JOIN marca ma     ON eq.idmarca_equipo  = ma.idmarca
        INNER JOIN modelo mo    ON eq.idmodelo_equipo = mo.idmodelo
        LEFT  JOIN areas ar     ON em.idarea  = ar.idarea
        LEFT  JOIN cargos ca    ON em.idcargo = ca.idcargo";
$params = [];
if ($filtro !== '') {
    $sql   .= " WHERE CONCAT(em.nombre,' ',em.apellidos) LIKE ?
              OR CONCAT(ma.nombreMarca,' ',mo.nombreModelo) LIKE ?
              OR ar.descripcionarea LIKE ?";
    $params = ["%$filtro%", "%$filtro%", "%$filtro%"];
}
$sql .= " ORDER BY asg.fecha_asignacion DESC, asg.idasignacion DESC";
$rows = $db->consulta($sql, $params);

foreach ($rows as $r) {
    $pdf->Cell(12, 6, $r['idasignacion'], 1, 0, 'C');
    $pdf->Cell(48, 6, $r['empleado'], 1, 0, 'L');
    $pdf->Cell(48, 6, $r['equipo'], 1, 0, 'L');
    $pdf->Cell(35, 6, $r['area'] ?? '', 1, 0, 'L');
    $pdf->Cell(35, 6, $r['cargo'] ?? '', 1, 0, 'L');
    $pdf->Cell(25, 6, $r['fecha_asignacion'] ? date('d/m/Y', strtotime($r['fecha_asignacion'])) : '-', 1, 0, 'C');
    $pdf->Cell(22, 6, (int)$r['activa'] === 1 ? 'Activa' : 'Devuelta', 1, 1, 'C');
}

$pdf->Output('ReporteAsignaciones_' . date('d_m_y') . '.pdf', 'I');
