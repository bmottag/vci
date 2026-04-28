<?php

namespace App\Libraries;

use TCPDF;

class PdfBuilder
{
    public function create($title = '')
    {
        $pdf = new TCPDF();

        $pdf->SetCreator('VCI');
        $pdf->SetAuthor('VCI');
        $pdf->SetTitle($title);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetMargins(10, 25, 10);
        $pdf->SetAutoPageBreak(true, 10);

        $pdf->SetFont('dejavusans', '', 8);

        // Página inicial
        $pdf->AddPage();

        // Logo automático
        $logo = FCPATH . 'images/logo.png';

        if (is_file($logo)) {
            $pdf->Image($logo, 10, 8, 30);
        }

        return $pdf;
    }
}