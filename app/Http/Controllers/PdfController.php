<?php

namespace App\Http\Controllers;

use App\Models\FileMetadata;
use Smalot\PdfParser\Parser;

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
}
