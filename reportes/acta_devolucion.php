<?php
// GestActivos - Recepción y acta firmada de devolución de equipo.
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
    die('Falta el número de asignación.');
}

$condicionesPermitidas = ['Bueno', 'Con daño', 'No funcional'];
$firmaGuardada = null;

if ($esPost) {
    $condicion = is_string($_POST['condicion_devolucion'] ?? null)
        ? trim($_POST['condicion_devolucion'])
        : '';
    $firmaData = $_POST['firma_devolucion'] ?? '';
    $otros = is_string($_POST['devolucion_otros'] ?? null)
        ? trim($_POST['devolucion_otros'])
        : '';
    $observaciones = is_string($_POST['observaciones_devolucion'] ?? null)
        ? trim($_POST['observaciones_devolucion'])
        : '';
    $cargador = isset($_POST['devolucion_cargador']) ? 1 : 0;
    $maletin = isset($_POST['devolucion_maletin']) ? 1 : 0;
    $idusuario = (int)Auth::get('idusuario');

    if (!in_array($condicion, $condicionesPermitidas, true)) {
        die('La condición de devolución no es válida.');
    }
    if (!is_string($firmaData) || $firmaData === '') {
        die('Debe registrar la firma de quien recibe el equipo.');
    }
    if (mb_strlen($otros) > 255 || mb_strlen($observaciones) > 500) {
        die('Los detalles de devolución exceden el tamaño permitido.');
    }
    if ($idusuario <= 0) {
        die('No se pudo identificar al usuario que recibe el equipo.');
    }

    $estadoEquipo = EquipoEstado::desdeCondicionDevolucion($condicion);
    $idMantenimiento = null;
    try {
        $firmaGuardada = guardarFirmaDigital($firmaData, 'firma_devolucion_' . $idasignacion);
        $db->transaccion(function (Database $db) use (
            $idasignacion, $condicion, $cargador, $maletin, $otros,
            $observaciones, $estadoEquipo, $firmaGuardada, $idusuario, &$idMantenimiento
        ): void {
            $actual = $db->fila(
                "SELECT asg.activa, asg.firma, asg.requiere_firma_entrega, asg.firma_devolucion, asg.idequipo, eq.activo
                 FROM asignacion asg
                 INNER JOIN equipo eq ON asg.idequipo=eq.idequipo
                 WHERE asg.idasignacion=? FOR UPDATE",
                [$idasignacion]
            );
            if (!$actual) {
                throw new RuntimeException('La asignación no existe.');
            }
            if ((int)$actual['activa'] !== 1 || !empty($actual['firma_devolucion'])) {
                throw new RuntimeException('Esta asignación ya fue devuelta.');
            }
            if ((int)$actual['requiere_firma_entrega'] === 1 && empty($actual['firma'])) {
                throw new RuntimeException('Debe firmar el acta de entrega antes de devolver este equipo.');
            }
            if ((int)$actual['activo'] !== 1) {
                throw new RuntimeException('El equipo está inactivo y no puede procesarse esta devolución.');
            }
            if (!$db->fila('SELECT idusuario FROM usuarios WHERE idusuario=?', [$idusuario])) {
                throw new RuntimeException('El usuario que recibe el equipo ya no existe.');
            }

            $db->ejecutar(
                "UPDATE asignacion
                 SET activa=0, fecha_devolucion=NOW(), condicion_devolucion=?,
                     devolucion_cargador=?, devolucion_maletin=?, devolucion_otros=?,
                     observaciones_devolucion=?, estado_equipo_devolucion=?,
                     firma_devolucion=?, firma_devolucion_fecha=NOW(), idusuario_devolucion=?
                 WHERE idasignacion=? AND activa=1",
                [
                    $condicion, $cargador, $maletin, $otros !== '' ? $otros : null,
                    $observaciones !== '' ? $observaciones : null, $estadoEquipo,
                    $firmaGuardada['relativa'], $idusuario, $idasignacion,
                ]
            );
            if ($estadoEquipo === EquipoEstado::MANTENIMIENTO) {
                $idMantenimiento = (new MantenimientoService($db))->abrirDesdeDevolucion(
                    $idasignacion,
                    (int)$actual['idequipo'],
                    $condicion,
                    $observaciones !== '' ? $observaciones : null,
                    $idusuario
                );
            } else {
                $db->ejecutar(
                    'UPDATE equipo SET estado_equipo=? WHERE idequipo=? AND activo=1',
                    [$estadoEquipo, (int)$actual['idequipo']]
                );
            }
        });
    } catch (RuntimeException $e) {
        eliminarFirmaDigitalTemporal($firmaGuardada['absoluta'] ?? null);
        die($e->getMessage());
    } catch (Throwable $e) {
        eliminarFirmaDigitalTemporal($firmaGuardada['absoluta'] ?? null);
        error_log('GestActivos - Error al registrar devolución: ' . $e->getMessage());
        die('No se pudo registrar la devolución del equipo.');
    }

    Auth::registrarBitacora(
        $idusuario, Auth::get('usuario'), 'devolver', 'asignaciones',
        "asignación #$idasignacion; condición: $condicion; estado equipo: $estadoEquipo"
    );
    if ($idMantenimiento !== null) {
        Auth::registrarBitacora(
            $idusuario, Auth::get('usuario'), 'crear', 'mantenimientos',
            "mantenimiento #$idMantenimiento generado por devolución #$idasignacion"
        );
    }
}

$sqlActa = "SELECT asg.idasignacion, asg.activa, asg.fecha_asignacion, asg.fecha_devolucion,
                   asg.condicion_entrega, asg.entrega_cargador, asg.entrega_maletin,
                   asg.entrega_otros, asg.observaciones_entrega,
                   asg.condicion_devolucion, asg.devolucion_cargador, asg.devolucion_maletin,
                   asg.devolucion_otros, asg.observaciones_devolucion,
                   asg.estado_equipo_devolucion, asg.firma_devolucion,
                   asg.firma_devolucion_fecha,
                   CONCAT(em.nombre,' ',em.apellidos) AS empleado,
                   CONCAT(COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)), ' - ', ma.nombreMarca, ' ', mo.nombreModelo) AS equipo,
                   COALESCE(eq.codigo_activo, CONCAT('EQ-', eq.idequipo)) AS codigo_activo,
                   eq.numero_serie, eq.tipo_equipo,
                   ar.descripcionarea AS area, ca.descripcioncargo AS cargo,
                   COALESCE(NULLIF(TRIM(CONCAT(COALESCE(rec.nombre,''), ' ', COALESCE(rec.apellidos,''))), ''),
                            us.username, 'Usuario IT') AS recibido_por
            FROM asignacion asg
            INNER JOIN empleados em ON asg.idempleado=em.idempleado
            INNER JOIN equipo eq ON asg.idequipo=eq.idequipo
            INNER JOIN marca ma ON eq.idmarca_equipo=ma.idmarca
            INNER JOIN modelo mo ON eq.idmodelo_equipo=mo.idmodelo
            LEFT JOIN areas ar ON em.idarea=ar.idarea
            LEFT JOIN cargos ca ON em.idcargo=ca.idcargo
            LEFT JOIN usuarios us ON asg.idusuario_devolucion=us.idusuario
            LEFT JOIN empleados rec ON us.idempleado=rec.idempleado
            WHERE asg.idasignacion=?";
$row = $db->fila($sqlActa, [$idasignacion]);
if (!$row) {
    die('Asignación no encontrada.');
}
if ((int)$row['activa'] !== 0 || empty($row['firma_devolucion'])) {
    die('Esta asignación todavía no tiene una devolución firmada.');
}

$rutaFirma = BASE_PATH . '/' . ltrim($row['firma_devolucion'], '/');
if (!is_file($rutaFirma)) {
    die('No se encontró el archivo de la firma de devolución.');
}

$estadoResultante = EquipoEstado::nombre((int)$row['estado_equipo_devolucion']);
$accesoriosEntrega = textoAccesoriosActa($row['entrega_cargador'], $row['entrega_maletin'], $row['entrega_otros']);
$accesoriosDevolucion = textoAccesoriosActa($row['devolucion_cargador'], $row['devolucion_maletin'], $row['devolucion_otros']);
$fechaAsignacion = $row['fecha_asignacion'] ? date('d/m/Y h:i A', strtotime($row['fecha_asignacion'])) : '-';
$fechaDevolucion = $row['fecha_devolucion'] ? date('d/m/Y h:i A', strtotime($row['fecha_devolucion'])) : '-';
$fechaFirma = $row['firma_devolucion_fecha'] ? date('d/m/Y h:i A', strtotime($row['firma_devolucion_fecha'])) : '';

$pdf = new GestActivosPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
$pdf->configureReport(
    'ACTA DE DEVOLUCIÓN DE EQUIPO',
    'ACT-D-' . str_pad((string)$row['idasignacion'], 5, '0', STR_PAD_LEFT),
    'Recepción, condición y destino del activo devuelto'
);
$pdf->SetMargins(20, 54, 20);
$pdf->SetHeaderMargin(7);
$pdf->SetFooterMargin(8);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(APP_NAME);
$pdf->SetTitle('Acta de Devolución - Asignación ' . $row['idasignacion']);
$pdf->SetSubject('Devolución del activo ' . $row['codigo_activo']);
$pdf->AddPage();

$pdf->SetFillColor(240, 244, 248);
$pdf->SetDrawColor(202, 211, 221);
$pdf->RoundedRect(20, 54, 176, 16, 2, '1111', 'DF');
$pdf->SetXY(25, 57);
$pdf->SetTextColor(31, 55, 86);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(37, 5, 'No. de asignación', 0, 0, 'L');
$pdf->Cell(50, 5, 'Activo', 0, 0, 'L');
$pdf->Cell(74, 5, 'Fecha de devolución', 0, 1, 'L');
$pdf->SetX(25);
$pdf->SetTextColor(42, 50, 60);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(37, 5, (string)$row['idasignacion'], 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(50, 5, $row['codigo_activo'], 0, 0, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(74, 5, $fechaDevolucion, 0, 1, 'L');

$pdf->SetY(77);
tituloSeccionActa($pdf, 'RESPONSABLE Y EQUIPO');
filaDetalleActa($pdf, 'Empleado', $row['empleado'], true);
filaDetalleActa($pdf, 'Área / cargo', ($row['area'] ?: '-') . ' / ' . ($row['cargo'] ?: '-'), false);
filaDetalleActa($pdf, 'Equipo', $row['equipo'], true);
filaDetalleActa($pdf, 'Serie / tipo', ($row['numero_serie'] ?: '-') . ' / ' . ($row['tipo_equipo'] ?: 'Otro'), false);

$pdf->Ln(5);
tituloSeccionActa($pdf, 'COMPARACIÓN DE ENTREGA Y DEVOLUCIÓN');
filaDetalleActa($pdf, 'Entrega', ($row['condicion_entrega'] ?: 'Bueno') . ' | ' . $accesoriosEntrega, true);
filaDetalleActa($pdf, 'Devolución', $row['condicion_devolucion'] . ' | ' . $accesoriosDevolucion, false);
filaDetalleActa($pdf, 'Destino del equipo', $estadoResultante, true);
filaDetalleActa($pdf, 'Observaciones', $row['observaciones_devolucion'] ?: 'Sin observaciones', false);

$pdf->Ln(6);
$pdf->SetTextColor(42, 50, 60);
$pdf->SetFillColor(248, 250, 252);
$pdf->SetDrawColor(202, 211, 221);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(
    176, 5,
    'El responsable de IT declara haber recibido el activo y los accesorios indicados, verificando la condición registrada. ' .
    'El sistema deja cerrada la asignación y conserva este movimiento como parte del historial permanente.',
    1, 'J', true, 1, '', '', true, 0, false, true, 25, 'M'
);

$pdf->Ln(6);
$firmaAncho = 68;
$firmaX = ($pdf->getPageWidth() - $firmaAncho) / 2;
$firmaY = $pdf->GetY();
$pdf->Image($rutaFirma, $firmaX, $firmaY, $firmaAncho, 23, '', '', '', true, 300, '', false, false, 0, 'CM');
$lineaY = $firmaY + 25;
$pdf->SetDrawColor(90, 101, 115);
$pdf->Line($firmaX, $lineaY, $firmaX + $firmaAncho, $lineaY);
$pdf->SetXY($firmaX, $lineaY + 1);
$pdf->SetTextColor(31, 55, 86);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell($firmaAncho, 4.5, 'Firma de recepción de IT', 0, 2, 'C');
$pdf->SetTextColor(52, 61, 72);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($firmaAncho, 4.5, $row['recibido_por'], 0, 2, 'C');
if ($fechaFirma !== '') {
    $pdf->SetTextColor(105, 115, 128);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell($firmaAncho, 4, 'Firmado: ' . $fechaFirma, 0, 1, 'C');
}

$pdf->SetY(-27);
$pdf->SetTextColor(105, 115, 128);
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 4, 'Asignado originalmente: ' . $fechaAsignacion, 0, 1, 'R');

outputGestActivosPdf($pdf, 'Acta_Devolucion_' . $row['idasignacion'] . '.pdf');
