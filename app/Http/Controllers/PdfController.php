<?php

namespace App\Http\Controllers;

use Spatie\PdfToText\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;

class PdfController extends Controller
{
    public function lerPDF($path)
    {
        // Primeiro tenta extrair texto normalmente
        try {
            $binaryPath = 'C:\Program Files\poppler-24.08.0\Library\bin\pdftotext.exe'; // Ajuste conforme a instalação
            $text = Pdf::getText($path, $binaryPath);
            // $text = Pdf::getText($path);
            if (!empty(trim($text))) {
                return $text;
            }
        } catch (\Exception $e) {
            \Log::error("Erro ao extrair texto do PDF: " . $e->getMessage());
        }

        // Se não conseguiu extrair texto, tenta OCR
        return $this->extrairTextoComOCR($path);
    }

    private function extrairTextoComOCR($path)
    {
        $tesseract = new TesseractOCR();

        // Configura o binário do Tesseract (necessário no Windows)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $tesseract->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');
        }

        // Converte o PDF em imagens e aplica OCR (Ghostscript pode ser necessário)
        $outputPath = storage_path('app/pdf_images');
        if (!file_exists($outputPath)) {
            mkdir($outputPath, 0777, true);
        }

        // Converte PDF para imagens usando Ghostscript (precisa estar instalado)
        $imagePath = $outputPath . '/page-%d.png';
        $ghostscript = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'gswin64c' : 'gs';
        $cmd = "$ghostscript -dNOPAUSE -sDEVICE=png16m -r300 -o $imagePath $path";

        shell_exec($cmd);

        // Processa cada imagem extraída
        $text = '';
        foreach (glob($outputPath . '/*.png') as $image) {
            $text .= (new TesseractOCR($image))->run() . "\n";
            unlink($image); // Remove a imagem após o processamento
        }

        return trim($text);
    }
}
