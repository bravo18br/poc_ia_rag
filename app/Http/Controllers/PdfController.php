<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Spatie\PdfToText\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Http\Controllers\StatusController;

class PdfController extends Controller
{
    public function lerPDF($path)
    {
        $status = new StatusController();
        $status->atualizaStatus($path, 0, 'Leitura PDF Spatie\PdfToText iniciada');

        // Primeiro tenta extrair texto normalmente
        try {
            $binaryPath = 'C:\Program Files\poppler-24.08.0\Library\bin\pdftotext.exe'; // Ajuste conforme a instalação
            $text = Pdf::getText(storage_path("app/private/" . $path), $binaryPath);
            // $text = Pdf::getText(storage_path("app/private/" . $path));
            if (!empty(trim($text))) {
                $status->atualizaStatus($path, 1, 'Leitura PDF Spatie\PdfToText concluída');
                return $text;
            }
        } catch (\Exception $e) {
            $errorMessage = "Erro ao extrair texto do PDF: " . $e->getMessage();
            Log::error($errorMessage);
            $status->atualizaStatus($path, 0, $errorMessage);
        }

        // Se não conseguiu extrair texto, tenta OCR
        return $this->extrairTextoComOCR($path);
    }

    private function extrairTextoComOCR($path)
    {
        $status = new StatusController();
        $status->atualizaStatus($path, 1, 'Leitura PDF TesseractOCR iniciada');

        $tesseract = new TesseractOCR();

        // Configura o binário do Tesseract (necessário no Windows)
        $status->atualizaStatus($path, 1, 'Configurando binário do Tesseract');
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $tesseract->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');
        }

        // Converte o PDF em imagens e aplica OCR (Ghostscript pode ser necessário)
        $status->atualizaStatus($path, 1, 'Convertendo o PDF em imagens');
        $outputPath = storage_path('app/pdf_images');
        if (!file_exists($outputPath)) {
            mkdir($outputPath, 0777, true);
        }

        // Converte PDF para imagens usando Ghostscript (precisa estar instalado)
        $status->atualizaStatus($path, 1, 'Aplicando Ghostscript');
        $imagePath = $outputPath . '/page-%d.png';
        $ghostscript = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'gswin64c' : 'gs';
        $cmd = "$ghostscript -dNOPAUSE -sDEVICE=png16m -r300 -o $imagePath $path";

        shell_exec($cmd);

        // Processa cada imagem extraída
        $status->atualizaStatus($path, 0, 'Processamento OCR de imagens');
        $text = '';
        $total = count(glob($outputPath . '/*.png'));
        foreach (glob($outputPath . '/*.png') as $i => $image) {
            $text .= (new TesseractOCR($image))->run() . "\n";
            unlink($image); // Remove a imagem após o processamento
            $status->atualizaStatus($path, $i / $total, 'Processamento OCR de imagens');
        }
        $status->atualizaStatus($path, 1, 'Processamento OCR de imagens');
        return trim($text);
    }
}
