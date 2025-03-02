<?php

namespace App\Http\Controllers;

use App\Models\FileMetadata;
use Smalot\PdfParser\Parser;
use Spatie\PdfToText\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;

class PdfController extends Controller
{
    public function capturaMetadadosPDF($pdfPath)
    {
        // Verifica se o arquivo existe
        if (!file_exists($pdfPath)) {
            return 'Path inexistente';
        }

        // Capturar metadados do PDF
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $details = $pdf->getDetails();

            $filename = basename($pdfPath);
            $title = $details['Title'] ?? null;
            $author = $details['Author'] ?? null;
            $created_at = isset($details['CreationDate']) ? date('Y-m-d H:i:s', strtotime($details['CreationDate'])) : null;

            // 🔹 **Verifica se já existe um registro igual no banco**
            $existingFile = FileMetadata::where('filename', $filename)
                ->where('title', $title)
                ->where('author', $author)
                ->where('created_at', $created_at)
                ->first();

            if ($existingFile) {
                return;
            }

            // 🔹 **Se não existir, cria um novo registro**
            $pdfMetadata = FileMetadata::create([
                'filename' => $filename,
                'title' => $title,
                'author' => $author,
                'created_at' => $created_at,
                'updated_at' => isset($details['ModDate']) ? date('Y-m-d H:i:s', strtotime($details['ModDate'])) : null,
                'source' => 'Local'
            ]);
        } catch (\Exception $e) {
            return 'Erro ao capturar metadados: ' . $e->getMessage();
        }


    }

    public function lerPDF($path)
    {
        // Primeiro tenta extrair texto normalmente
        try {
            $binaryPath = 'C:\Program Files\poppler-24.08.0\Library\bin\pdftotext.exe'; // Ajuste conforme a instalação
            $text = Pdf::getText($path, $binaryPath);
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
