<?php
// ============================================================
// GestActivos - Generar PDF: Acta de Entrega de Equipo
//
// Dos modos de uso:
//  1. POST con 'firma' (base64) -> guarda la firma y genera el PDF.
//  2. GET/POST sin firma pero asignacion ya firmada -> reimprime el acta.
// ============================================================
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirPermiso('transacciones');

require_once BASE_PATH . '/lib/tcpdf/tcpdf.php';

$db = Database::getInstance();

// El POST guarda datos, por lo que debe validar el token del formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verificarCsrf();
}

if (ob_get_level()) {
    ob_end_clean();
}

$idasignacion = (int)($_POST['idasignacion'] ?? $_GET['idasignacion'] ?? 0);
if ($idasignacion <= 0) {
    die('Falta el numero de asignacion.');
}

$row = $db->fila(
    "SELECT asg.idasignacion, asg.firma, asg.firma_fecha,
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
     LEFT  JOIN cargos ca    ON em.idcargo = ca.idcargo
     WHERE asg.idasignacion = ?",
    [$idasignacion]
);

if (!$row) {
    die('Asignacion no encontrada.');
}

// ---- Firma: nueva (llega por POST) o ya guardada en BD ----
$rutaFirmaAbsoluta = null;
$fechaFirma        = '';

if (!empty($_POST['firma'])) {

    // Impide que una peticion manipulada reemplace una firma registrada.
    if (!empty($row['firma'])) {
        die('Esta asignacion ya tiene una firma registrada.');
    }

    if (!is_string($_POST['firma']) || strlen($_POST['firma']) > 2 * 1024 * 1024) {
        die('La firma recibida excede el tamano permitido.');
    }

    if (!preg_match('#^data:image/(?:jpeg|png);base64,([A-Za-z0-9+/=]+)$#', $_POST['firma'], $coincidencia)) {
        die('El formato de la firma no es valido.');
    }
    $firmaBinaria = base64_decode($coincidencia[1], true);
    $infoImagen = $firmaBinaria !== false ? @getimagesizefromstring($firmaBinaria) : false;

    if ($firmaBinaria === false || strlen($firmaBinaria) < 100 ||
        $infoImagen === false || !in_array($infoImagen['mime'], ['image/jpeg', 'image/png'], true)) {
        die('La firma recibida no es valida.');
    }

    if (!is_dir(IMG_FIRMAS)) {
        mkdir(IMG_FIRMAS, 0755, true);
    }

    $nombreFirma = 'firma_' . $idasignacion . '_' . time() . '.jpg';
    if (file_put_contents(IMG_FIRMAS . $nombreFirma, $firmaBinaria, LOCK_EX) === false) {
        die('No se pudo guardar la firma.');
    }

    $db->ejecutar(
        "UPDATE asignacion SET firma = ?, firma_fecha = NOW() WHERE idasignacion = ?",
        ['public/img/firmas/' . $nombreFirma, $idasignacion]
    );

    Auth::registrarBitacora(
        (int)Auth::get('idusuario'),
        Auth::get('usuario'),
        'crear',
        'acta_firma',
        "asignacion #$idasignacion"
    );

    $rutaFirmaAbsoluta = IMG_FIRMAS . $nombreFirma;
    $fechaFirma        = date('d-m-Y h:i A');

} elseif (!empty($row['firma'])) {

    $rutaFirmaAbsoluta = BASE_PATH . '/' . $row['firma'];
    $fechaFirma        = $row['firma_fecha']
        ? date('d-m-Y h:i A', strtotime($row['firma_fecha']))
        : '';

} else {
    die('Esta asignacion todavia no tiene firma. Firmela primero desde Asignar Equipos.');
}

// ---- Clase PDF con encabezado ----
class MYPDF extends TCPDF {
    public function Header() {
        $bMargin         = $this->getBreakMargin();
        $auto_page_break = $this->AutoPageBreak;
        $this->SetAutoPageBreak(false, 0);
        $img_file = BASE_PATH . '/public/icons/windows.jpg';
        $this->Image($img_file, 85, 8, 50, 50, '', '', '', false, 30, '', false, false, 0);
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        $this->setPageMark();
    }
}

$pdf = new MYPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->SetMargins(20, 35, 20);
$pdf->SetHeaderMargin(20);
$pdf->setPrintFooter(false);
$pdf->setPrintHeader(true);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Acta de Entrega - Asignacion ' . $row['idasignacion']);

$pdf->AddPage();

// Bloque superior derecho
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(150, 20);
$pdf->Write(0, 'Codigo: ACTA' . str_pad((string)$row['idasignacion'], 5, '0', STR_PAD_LEFT));
$pdf->SetXY(150, 25);
$pdf->Write(0, 'Fecha: ' . date('d-m-Y'));
$pdf->SetXY(150, 30);
$pdf->Write(0, 'Hora: '  . date('h:i A'));

// Bloque superior izquierdo
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(15, 20);
$pdf->SetTextColor(111, 66, 193);
$pdf->Write(0, APP_NAME);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(15, 25);
$pdf->Write(0, 'Sistema de Gestion de Activos');

// Titulo
$pdf->Ln(35);
$pdf->SetTextColor(34, 68, 136);
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(0, 8, 'ACTA DE ENTREGA DE EQUIPO', 0, 1, 'C');
$pdf->Ln(6);
$pdf->SetTextColor(0, 0, 0);

// Datos
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(45, 7, 'No. de Asignacion:', 0, 0, 'L');
$pdf->Cell(0, 7, (string)$row['idasignacion'], 0, 1, 'L');
$pdf->Cell(45, 7, 'Empleado:', 0, 0, 'L');
$pdf->Cell(0, 7, $row['empleado'], 0, 1, 'L');
$pdf->Cell(45, 7, 'Area:', 0, 0, 'L');
$pdf->Cell(0, 7, $row['area'] ?? '-', 0, 1, 'L');
$pdf->Cell(45, 7, 'Cargo:', 0, 0, 'L');
$pdf->Cell(0, 7, $row['cargo'] ?? '-', 0, 1, 'L');
$pdf->Cell(45, 7, 'Equipo asignado:', 0, 0, 'L');
$pdf->Cell(0, 7, $row['equipo'], 0, 1, 'L');

// Cuerpo
$pdf->Ln(8);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6,
    'Por medio de la presente, el empleado antes mencionado declara haber recibido ' .
    'a su cargo el equipo descrito, en buen estado y funcionamiento, comprometiendose ' .
    'a darle un uso adecuado y a reportar cualquier dano, falla o perdida a la brevedad posible.',
    0, 'J');

$pdf->Ln(15);

// Imagen de la firma
$y = $pdf->GetY();
$pdf->Image($rutaFirmaAbsoluta, 25, $y, 70, 25);

// Linea y etiquetas
$pdf->Line(25, $y + 27, 95, $y + 27);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(25, $y + 28);
$pdf->Cell(70, 5, 'Firma del Empleado', 0, 2, 'C');
$pdf->Cell(70, 5, $row['empleado'],     0, 2, 'C');
if ($fechaFirma !== '') {
    $pdf->Cell(70, 5, 'Firmado: ' . $fechaFirma, 0, 1, 'C');
}

$pdf->Output('Acta_Asignacion_' . $row['idasignacion'] . '.pdf', 'I');
