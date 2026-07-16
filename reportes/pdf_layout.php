<?php
require_once BASE_PATH . '/lib/tcpdf/tcpdf.php';

class GestActivosPDF extends TCPDF {
    private string $reportTitle = '';
    private string $reportCode = '';
    private string $reportSubtitle = '';
    private array $tableColumns = [];
    private float $tableLeft = 12.0;

    public function configureReport(string $title, string $code, string $subtitle = '', array $columns = [], float $tableLeft = 12.0): void {
        $this->reportTitle = $title;
        $this->reportCode = $code;
        $this->reportSubtitle = $subtitle;
        $this->tableColumns = $columns;
        $this->tableLeft = $tableLeft;
    }

    public function Header(): void {
        $logo = BASE_PATH . '/public/icons/logo_pdf.jpg';
        if (is_file($logo)) {
            $this->Image($logo, 12, 7, 18, 18, 'JPG', '', '', false, 300);
        }

        $this->SetTextColor(31, 55, 86);
        $this->SetFont('helvetica', 'B', 15);
        $this->SetXY(35, 8);
        $this->Cell(90, 6, APP_NAME, 0, 1, 'L');
        $this->SetFont('helvetica', '', 8.5);
        $this->SetTextColor(92, 105, 120);
        $this->SetX(35);
        $this->Cell(100, 5, 'Sistema de Gestion de Activos', 0, 1, 'L');

        $rightX = $this->getPageWidth() - 72;
        $this->SetXY($rightX, 8);
        $this->SetFont('helvetica', 'B', 8.5);
        $this->SetTextColor(31, 55, 86);
        $this->Cell(60, 5, 'Codigo: ' . $this->reportCode, 0, 1, 'R');
        $this->SetX($rightX);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(92, 105, 120);
        $this->Cell(60, 5, 'Generado: ' . date('d/m/Y h:i A'), 0, 1, 'R');

        $this->SetDrawColor(75, 103, 140);
        $this->SetLineWidth(0.5);
        $this->Line(12, 29, $this->getPageWidth() - 12, 29);

        if ($this->reportTitle !== '') {
            $this->SetXY(12, 34);
            $this->SetTextColor(31, 55, 86);
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell($this->getPageWidth() - 24, 7, $this->reportTitle, 0, 1, 'C');
        }
        if ($this->reportSubtitle !== '') {
            $this->SetX(12);
            $this->SetTextColor(92, 105, 120);
            $this->SetFont('helvetica', '', 8.5);
            $this->Cell($this->getPageWidth() - 24, 5, $this->reportSubtitle, 0, 1, 'C');
        }

        if ($this->tableColumns) {
            $this->drawTableHeader(49);
        }
    }

    private function drawTableHeader(float $y): void {
        $x = $this->tableLeft;
        $this->SetFillColor(31, 55, 86);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(31, 55, 86);
        $this->SetFont('helvetica', 'B', 8);
        foreach ($this->tableColumns as $column) {
            $width = (float)$column['width'];
            $this->MultiCell($width, 8, $column['label'], 1, 'C', true, 0, $x, $y, true, 0, false, true, 8, 'M');
            $x += $width;
        }
        $this->SetXY($this->tableLeft, $y + 8);
    }

    public function tableRow(array $values, int $index): void {
        $lineHeight = 4.2;
        $height = 6.5;
        foreach ($this->tableColumns as $i => $column) {
            $text = (string)($values[$i] ?? '');
            $lines = max(1, $this->getNumLines($text, (float)$column['width'] - 2));
            $height = max($height, $lines * $lineHeight + 1.6);
        }
        if ($this->GetY() + $height > $this->getPageHeight() - 18) {
            $this->AddPage();
        }

        $x = $this->tableLeft;
        $y = $this->GetY();
        $fill = ($index % 2) === 0;
        $this->SetFillColor(244, 247, 250);
        $this->SetTextColor(32, 39, 48);
        $this->SetDrawColor(204, 212, 221);
        $this->SetFont('helvetica', '', 7.8);

        foreach ($this->tableColumns as $i => $column) {
            $width = (float)$column['width'];
            $align = $column['align'] ?? 'L';
            $text = (string)($values[$i] ?? '');
            $this->MultiCell($width, $height, $text, 1, $align, $fill, 0, $x, $y, true, 0, false, true, $height, 'M');
            $x += $width;
        }
        $this->SetXY($this->tableLeft, $y + $height);
    }

    public function Footer(): void {
        $this->SetY(-14);
        $this->SetDrawColor(190, 198, 208);
        $this->SetLineWidth(0.2);
        $this->Line(12, $this->GetY(), $this->getPageWidth() - 12, $this->GetY());
        $this->Ln(2);
        $this->SetFont('helvetica', '', 7.5);
        $this->SetTextColor(105, 115, 128);
        $contentWidth = $this->getPageWidth() - 24;
        $this->SetX(12);
        $this->Cell($contentWidth * 0.7, 5, APP_NAME . ' - Documento generado por el sistema', 0, 0, 'L');
        $this->Cell($contentWidth * 0.3, 5, 'Pagina ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

function outputGestActivosPdf(TCPDF $pdf, string $filename): void {
    if (defined('PDF_TEST_FILE')) {
        $pdf->Output(PDF_TEST_FILE, 'F');
        return;
    }
    $pdf->Output($filename, 'I');
}