<?php

namespace App\Http\Controllers;

use App\Models\StatusRAG;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\RagPdfJob;
use App\Models\FileMetadata;
use Smalot\PdfParser\Parser;

class RagController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'pdfFile' => 'required|file|mimes:pdf|max:20480',
            ]);

            $metadadosFile = $this->capturaMetadadosPDF($request);

            // Se o arquivo já possuir um path, significa que ele já foi processado
            if ($metadadosFile->path) {
                return response()->json([
                    'message' => 'Arquivo já processado',
                    'path' => $metadadosFile->path,
                    'id' => $metadadosFile->id
                ]);
            }

            // Salvar o arquivo no disco
            $path = $request->file('pdfFile')->store('uploads', 'local');

            //Atualizar metadados (sem alterar o campo updated_at)
            $metadadosFile->updateQuietly(['path' => $path]);

            // Registrar o status no banco de dados
            $status = new StatusRAG();
            $status->file_path = $metadadosFile->path;
            $status->percent = 0;
            $status->status = 'Em processamento';
            $status->save();

            // Dispara o processamento em background
            RagPdfJob::dispatch($metadadosFile);

            return response()->json([
                'message' => 'Arquivo enviado com sucesso! O processamento será feito em segundo plano.',
                'path' => $metadadosFile->path,
                'id' => $status->id
            ]);

        } catch (\Exception $e) {
            Log::error("Erro no upload: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar o upload'], 500);
        }
    }

    public function capturaMetadadosPDF($request)
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($request->file('pdfFile'));
            $details = $pdf->getDetails();
            $filename = $request->file('pdfFile')->getClientOriginalName();
            $title = $details['Title'] ?? null;
            $author = $details['Author'] ?? null;
            $producer = $details['Producer'] ?? null;
            $pages = $details['Pages'] ?? null;
            $created_at = isset($details['CreationDate']) ? date('Y-m-d H:i:s', strtotime($details['CreationDate'])) : null;
            $updated_at = isset($details['ModDate']) ? date('Y-m-d H:i:s', strtotime($details['ModDate'])) : null;

            // Verifica se já existe um registro igual no banco
            $existingFile = FileMetadata::where('filename', $filename)
                ->where('title', $title)
                ->where('author', $author)
                ->where('created_at', $created_at)
                ->first();

            if ($existingFile) {
                return $existingFile;
            }

            // Se não existir, cria um novo registro
            $existingFile = FileMetadata::create([
                'filename' => $filename,
                'title' => $title,
                'author' => $author,
                'created_at' => $created_at,
                'updated_at' => $updated_at,
                'source' => 'Local',
                'producer' => $producer,
                'pages' => $pages
            ]);
            return $existingFile;
        } catch (\Exception $e) {
            return 'Erro ao capturar metadados: ' . $e->getMessage();
        }
    }
}
