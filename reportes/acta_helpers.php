<?php

function textoAccesoriosActa($cargador, $maletin, ?string $otros): string {
    $accesorios = [];
    if ((int)$cargador === 1) {
        $accesorios[] = 'Cargador';
    }
    if ((int)$maletin === 1) {
        $accesorios[] = 'Maletin';
    }
    if ($otros !== null && trim($otros) !== '') {
        $accesorios[] = trim($otros);
    }
    return $accesorios ? implode(', ', $accesorios) : 'Sin accesorios';
}

function tituloSeccionActa(GestActivosPDF $pdf, string $titulo): void {
    $pdf->SetTextColor(31, 55, 86);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, $titulo, 0, 1, 'L');
}

function filaDetalleActa(
    GestActivosPDF $pdf,
    string $etiqueta,
    string $valor,
    bool $relleno,
    float $anchoEtiqueta = 38,
    float $anchoValor = 138
): void {
    $valor = $valor !== '' ? $valor : '-';
    $lineas = max(1, $pdf->getNumLines($valor, $anchoValor - 3));
    $alto = max(7, $lineas * 4.2 + 2.2);
    if ($pdf->GetY() + $alto > $pdf->getPageHeight() - 20) {
        $pdf->AddPage();
    }

    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->SetFillColor(247, 249, 251);
    $pdf->SetDrawColor(214, 220, 228);
    $pdf->SetTextColor(78, 89, 103);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->MultiCell($anchoEtiqueta, $alto, $etiqueta, 1, 'L', $relleno, 0, $x, $y, true, 0, false, true, $alto, 'M');
    $pdf->SetTextColor(30, 36, 44);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell($anchoValor, $alto, $valor, 1, 'L', $relleno, 1, $x + $anchoEtiqueta, $y, true, 0, false, true, $alto, 'M');
}
