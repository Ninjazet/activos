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
require_once BASE_PATH . '/reportes/pdf_layout.php';

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
    "SELECT asg.idasignacion, asg.firma, asg.firma_fecha, asg.fecha_asignacion,
            CONCAT(em.nombre,' ',em.apellidos) AS empleado,
            CONCAT(COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)), ' - ', ma.nombreMarca, ' ', mo.nombreModelo) AS equipo,
            COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)) AS codigo_activo,
            eq.numero_serie, eq.tipo_equipo,
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
$fechaFirma = '';

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
    $fechaFirma = date('d-m-Y h:i A');
} elseif (!empty($row['firma'])) {
    $rutaFirmaAbsoluta = BASE_PATH . '/' . $row['firma'];
    $fechaFirma = $row['firma_fecha']
        ? date('d-m-Y h:i A', strtotime($row['firma_fecha']))
        : '';
} else {
    die('Esta asignacion todavia no tiene firma. Firmela primero desde Asignar Equipos.');
}

$pdf = new GestActivosPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->configureReport(
    'ACTA DE ENTREGA DE EQUIPO',
    'ACT-' . str_pad((string)$row['idasignacion'], 5, '0', STR_PAD_LEFT),
    'Constancia de entrega y responsabilidad del activo'
);
$pdf->SetMargins(20, 54, 20);
$pdf->SetHeaderMargin(7);
$pdf->setPrintFooter(true);
$pdf->setPrintHeader(true);
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Acta de Entrega - Asignacion ' . $row['idasignacion']);
$pdf->SetSubject('Constancia de entrega del activo ' . $row['codigo_activo']);
$pdf->AddPage();

$fechaAsignacion = !empty($row['fecha_asignacion'])
    ? date('d/m/Y h:i A', strtotime($row['fecha_asignacion']))
    : '-';

// Resumen de la entrega.
$pdf->SetFillColor(240, 244, 248);
$pdf->SetDrawColor(202, 211, 221);
$pdf->RoundedRect(20, 54, 176, 16, 2, '1111', 'DF');
$pdf->SetXY(25, 57);
$pdf->SetTextColor(31, 55, 86);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(37, 5, 'No. de asignacion', 0, 0, 'L');
$pdf->Cell(50, 5, 'Activo', 0, 0, 'L');
$pdf->Cell(74, 5, 'Fecha de entrega', 0, 1, 'L');
$pdf->SetX(25);
$pdf->SetTextColor(42, 50, 60);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(37, 5, (string)$row['idasignacion'], 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(50, 5, $row['codigo_activo'], 0, 0, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(74, 5, $fechaAsignacion, 0, 1, 'L');

$pdf->SetY(77);
$pdf->SetTextColor(31, 55, 86);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'DATOS DEL RESPONSABLE', 0, 1, 'L');
$pdf->SetDrawColor(214, 220, 228);
$pdf->SetFont('helvetica', '', 9.5);
$datosResponsable = [
    ['Empleado', $row['empleado']],
    ['Area', $row['area'] ?: '-'],
    ['Cargo', $row['cargo'] ?: '-'],
];
foreach ($datosResponsable as $indice => $dato) {
    $relleno = ($indice % 2) === 0;
    $pdf->SetFillColor(247, 249, 251);
    $pdf->SetTextColor(78, 89, 103);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(38, 7, $dato[0], 1, 0, 'L', $relleno);
    $pdf->SetTextColor(30, 36, 44);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(138, 7, $dato[1], 1, 1, 'L', $relleno);
}

$pdf->Ln(6);
$pdf->SetTextColor(31, 55, 86);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'DATOS DEL EQUIPO', 0, 1, 'L');
$datosEquipo = [
    ['Equipo', $row['equipo']],
    ['Tipo', $row['tipo_equipo'] ?: 'Otro'],
    ['Numero de serie', $row['numero_serie'] ?: '-'],
];
foreach ($datosEquipo as $indice => $dato) {
    $relleno = ($indice % 2) === 0;
    $pdf->SetFillColor(247, 249, 251);
    $pdf->SetTextColor(78, 89, 103);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(38, 7, $dato[0], 1, 0, 'L', $relleno);
    $pdf->SetTextColor(30, 36, 44);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(138, 7, $dato[1], 1, 1, 'L', $relleno);
}

$pdf->Ln(7);
$pdf->SetTextColor(42, 50, 60);
$pdf->SetFillColor(248, 250, 252);
$pdf->SetDrawColor(202, 211, 221);
$pdf->SetFont('helvetica', '', 9.5);
$pdf->MultiCell(
    176,
    6,
    'Por medio de la presente, el empleado antes mencionado declara haber recibido a su cargo ' .
    'el equipo descrito, en buen estado y funcionamiento. Se compromete a utilizarlo adecuadamente ' .
    'y a reportar oportunamente cualquier dano, falla, perdida o incidente relacionado con el activo.',
    1,
    'J',
    true,
    1,
    '',
    '',
    true,
    0,
    false,
    true,
    31,
    'M'
);

$pdf->Ln(8);
$firmaAncho = 72;
$firmaX = ($pdf->getPageWidth() - $firmaAncho) / 2;
$firmaY = $pdf->GetY();
if (is_file($rutaFirmaAbsoluta)) {
    $pdf->Image($rutaFirmaAbsoluta, $firmaX, $firmaY, $firmaAncho, 26, '', '', '', true, 300, '', false, false, 0, 'CM');
}

$lineaY = $firmaY + 28;
$pdf->SetDrawColor(90, 101, 115);
$pdf->Line($firmaX, $lineaY, $firmaX + $firmaAncho, $lineaY);
$pdf->SetXY($firmaX, $lineaY + 1);
$pdf->SetTextColor(31, 55, 86);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell($firmaAncho, 5, 'Firma del empleado', 0, 2, 'C');
$pdf->SetTextColor(52, 61, 72);
$pdf->SetFont('helvetica', '', 8.5);
$pdf->Cell($firmaAncho, 5, $row['empleado'], 0, 2, 'C');
if ($fechaFirma !== '') {
    $pdf->SetTextColor(105, 115, 128);
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->Cell($firmaAncho, 4, 'Firmado: ' . $fechaFirma, 0, 1, 'C');
}

outputGestActivosPdf($pdf, 'Acta_Asignacion_' . $row['idasignacion'] . '.pdf');