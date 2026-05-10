<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGenerator
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Génère un PDF à partir d'un template Twig
     */
    public function generatePdf(string $template, array $data, string $filename = 'document.pdf'): string
    {
        // Configuration de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Rendre le template Twig
        $html = $this->twig->render($template, $data);
        
        // Charger le HTML
        $dompdf->loadHtml($html);
        
        // Définir la taille du papier et l'orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Rendre le PDF
        $dompdf->render();
        
        // Retourner le PDF en tant que string
        return $dompdf->output();
    }

    /**
     * Génère et télécharge un PDF
     */
    public function downloadPdf(string $template, array $data, string $filename = 'document.pdf'): void
    {
        $pdfContent = $this->generatePdf($template, $data, $filename);
        
        // Envoyer les headers pour le téléchargement
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        
        echo $pdfContent;
    }
}
