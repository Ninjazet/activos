<?php
// GestActivos - Acta firmada de entrega de equipo.
require_once __DIR__ . '/../bootstrap.php';
$esPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if ($esPost) {
    Auth::requerirPermiso('transacciones');
} else {
    Auth::requerirPermisoActas();
}
require_once __DIR__ . '/pdf_layout.php';
require_once __DIR__ . '/firma_digital.php';
require_once __DIR__ . '/acta_helpers.php';

$db = Database::getInstance();
if ($esPost) {
    Auth::verificarCsrf();
}
if (ob_get_level()) {
    ob_end_clean();
}

$idasignacion = (int)($_POST['idasignacion'] ?? $_GET['idasignacion'] ?? 0);
if ($idasignacion <= 0) {
    die('Falta el numero de asignacion.');
}

$sqlActa = "SELECT asg.idasignacion, asg.activa, asg.firma, asg.firma_fecha, asg.fecha_asignacion,
                   asg.condicion_entrega, asg.entrega_cargador, asg.entrega_maletin,
                   asg.entrega_otros, asg.observaciones_entrega,
                   CONCAT(em.nombre,' ',em.apellidos) AS empleado,
                   CONCAT(COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)), ' - ', ma.nombreMarca, ' ', mo.nombreModelo) AS equipo,
                   COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)) AS codigo_activo,
                   eq.numero_serie, eq.tipo_equipo,
                   ar.descripcionarea AS area, ca.descripcioncargo AS cargo
            FROM asignacion asg
            INNER JOIN empleados em ON asg.idempleado=em.idempleado
            INNER JOIN equipo eq ON asg.idequipo=eq.idequipo
            INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
            INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo
            LEFT JOIN areas ar ON em.idarea=ar.idarea
            LEFT JOIN cargos ca ON em.idcargo=ca.idcargo
            WHERE asg.idasignacion=?";
$row = $db->fila($sqlActa, [$idasignacion]);
if (!$row) {
    die('Asignacion no encontrada.');
}

$rutaFirmaAbsoluta = null;
$fechaFirma = '';

if (!empty($_POST['firma'])) {
    if (!is_string($_POST['firma'])) {
        die('El formato de la firma no es valido.');
    }
    if (!empty($row['firma'])) {
        die('Esta asignacion ya tiene una firma de entrega registrada.');
    }

    $firmaGuardada = null;
    try {
        $firmaGuardada = guardarFirmaDigital($_POST['firma'], 'firma_entrega_' . $idasignacion);
        $db->transaccion(function (Database $db) use ($idasignacion, $firmaGuardada): void {
            $actual = $db->fila(
                "SELECT activa, firma FROM asignacion WHERE idasignacion=? FOR UPDATE",
                [$idasignacion]
            );
            if (!$actual) {
                throw new RuntimeException('La asignacion ya no existe.');
            }
            if ((int)$actual['activa'] !== 1) {
                throw new RuntimeException('No puede firmarse la entrega despues de cerrar la asignacion.');
            }
            if (!empty($actual['firma'])) {
                throw new RuntimeException('Esta asignacion ya tiene una firma de entrega registrada.');
            }
            $db->ejecutar(
                "UPDATE asignacion SET firma=?, firma_fecha=NOW() WHERE idasignacion=?",
                [$firmaGuardada['relativa'], $idasignacion]
            );
        });
    } catch (RuntimeException $e) {
        eliminarFirmaDigitalTemporal($firmaGuardada['absoluta'] ?? null);
        die($e->getMessage());
    } catch (Throwable $e) {
        eliminarFirmaDigitalTemporal($firmaGuardada['absoluta'] ?? null);
        error_log('GestActivos - Error al firmar entrega: ' . $e->getMessage());
        die('No se pudo registrar la firma de entrega.');
    }

    Auth::registrarBitacora(
        (int)Auth::get('idusuario'), Auth::get('usuario'), 'crear', 'acta_entrega',
        "asignacion #$idasignacion"
    );
    $rutaFirmaAbsoluta = $firmaGuardada['absoluta'];
    $fechaFirma = date('d/m/Y h:i A');
    $row['firma'] = $firmaGuardada['relativa'];
    $row['firma_fecha'] = date('Y-m-d H:i:s');
} elseif (!empty($row['firma'])) {
    $rutaFirmaAbsoluta = Imagen::firmaRuta($row['firma']);
    $fechaFirma = $row['firma_fecha']
        ? date('d/m/Y h:i A', strtotime($row['firma_fecha']))
        : '';
} else {
    die('Esta asignacion todavia no tiene firma de entrega.');
}

if (!$rutaFirmaAbsoluta || !is_file($rutaFirmaAbsoluta)) {
    die('No se encontro el archivo de la firma de entrega.');
}

$pdf = new GestActivosPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->configureReport(
    'ACTA DE ENTREGA DE EQUIPO',
    'ACT-E-' . str_pad((string)$row['idasignacion'], 5, '0', STR_PAD_LEFT),
    'Constancia de entrega, accesorios y responsabilidad del activo'
);
$pdf->SetMargins(20, 54, 20);
$pdf->SetHeaderMargin(7);
$pdf->SetFooterMargin(8);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Acta de Entrega - Asignacion ' . $row['idasignacion']);
$pdf->SetSubject('Entrega del activo ' . $row['codigo_activo']);
$pdf->AddPage();

$fechaAsignacion = $row['fecha_asignacion']
    ? date('d/m/Y h:i A', strtotime($row['fecha_asignacion']))
    : '-';
$accesoriosEntrega = textoAccesoriosActa(
    $row['entrega_cargador'], $row['entrega_maletin'], $row['entrega_otros']
);

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
tituloSeccionActa($pdf, 'DATOS DEL RESPONSABLE');
filaDetalleActa($pdf, 'Empleado', $row['empleado'], true);
filaDetalleActa($pdf, 'Area', $row['area'] ?: '-', false);
filaDetalleActa($pdf, 'Cargo', $row['cargo'] ?: '-', true);

$pdf->Ln(5);
tituloSeccionActa($pdf, 'DATOS DEL EQUIPO');
filaDetalleActa($pdf, 'Equipo', $row['equipo'], true);
filaDetalleActa($pdf, 'Tipo', $row['tipo_equipo'] ?: 'Otro', false);
filaDetalleActa($pdf, 'Numero de serie', $row['numero_serie'] ?: '-', true);

$pdf->Ln(5);
tituloSeccionActa($pdf, 'CONDICION Y ACCESORIOS DE ENTREGA');
filaDetalleActa($pdf, 'Condicion', $row['condicion_entrega'] ?: 'Bueno', true);
filaDetalleActa($pdf, 'Accesorios', $accesoriosEntrega, false);
filaDetalleActa($pdf, 'Observaciones', $row['observaciones_entrega'] ?: 'Sin observaciones', true);

$pdf->Ln(6);
$pdf->SetTextColor(42, 50, 60);
$pdf->SetFillColor(248, 250, 252);
$pdf->SetDrawColor(202, 211, 221);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    176, 5,
    'El empleado declara haber recibido el activo, su condicion y los accesorios descritos en este documento. ' .
    'Se compromete a utilizarlo adecuadamente y a reportar oportunamente cualquier dano, falla, perdida o incidente.',
    1, 'J', true, 1, '', '', true, 0, false, true, 25, 'M'
);

$pdf->Ln(6);
$firmaAncho = 68;
$firmaX = ($pdf->getPageWidth() - $firmaAncho) / 2;
$firmaY = $pdf->GetY();
$pdf->Image($rutaFirmaAbsoluta, $firmaX, $firmaY, $firmaAncho, 23, '', '', '', true, 300, '', false, false, 0, 'CM');
$lineaY = $firmaY + 25;
$pdf->SetDrawColor(90, 101, 115);
$pdf->Line($firmaX, $lineaY, $firmaX + $firmaAncho, $lineaY);
$pdf->SetXY($firmaX, $lineaY + 1);
$pdf->SetTextColor(31, 55, 86);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell($firmaAncho, 4.5, 'Firma del empleado', 0, 2, 'C');
$pdf->SetTextColor(52, 61, 72);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($firmaAncho, 4.5, $row['empleado'], 0, 2, 'C');
if ($fechaFirma !== '') {
    $pdf->SetTextColor(105, 115, 128);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell($firmaAncho, 4, 'Firmado: ' . $fechaFirma, 0, 1, 'C');
}

outputGestActivosPdf($pdf, 'Acta_Entrega_' . $row['idasignacion'] . '.pdf');
