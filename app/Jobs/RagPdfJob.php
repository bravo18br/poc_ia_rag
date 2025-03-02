<?php

namespace App\Jobs;

use App\Http\Controllers\ChunkController;
use App\Http\Controllers\EmbeddingController;
use App\Http\Controllers\PdfController;
use App\Models\Embedding;
use App\Models\FileMetadata;
use App\Models\StatusRAG;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Pgvector\Laravel\Vector;
use Smalot\PdfParser\Parser;

class RagPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("Iniciando o processamento do arquivo: app/private/" . $this->filePath);

        $status = StatusRAG::where('file_path', $this->filePath)->first();
        if (!$status) {
            Log::error("Status não encontrado para o arquivo: " . $this->filePath);
            return;
        }

        try {
            // Caminho do PDF de exemplo
            $pdfPath = storage_path("app/private/$this->filePath");

            // Capturar metadados do PDF
            Log::info("Iniciando Capturar metadados do PDF");
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($pdfPath);
                $details = $pdf->getDetails();

                $filename = basename($pdfPath);
                $title = $details['Title'] ?? null;
                $author = $details['Author'] ?? null;
                $created_at = isset($details['CreationDate']) ? date('Y-m-d H:i:s', strtotime($details['CreationDate'])) : null;

                // Verifica se já existe um registro igual no banco**
                $existingFile = FileMetadata::where('filename', $filename)
                    ->where('title', $title)
                    ->where('author', $author)
                    ->where('created_at', $created_at)
                    ->first();

                if ($existingFile) {
                    Log::warning("\nArquivo já foi inserido no pgvector. Processamento abortado.");
                    return;
                }

                // Se não existir, cria um novo registro**
                $pdfMetadata = FileMetadata::create([
                    'filename' => $filename,
                    'title' => $title,
                    'author' => $author,
                    'created_at' => $created_at,
                    'updated_at' => isset($details['ModDate']) ? date('Y-m-d H:i:s', strtotime($details['ModDate'])) : null,
                    'source' => 'Local'
                ]);

            } catch (\Exception $e) {
                Log::error("\nErro ao capturar metadados: " . $e->getMessage());
                return;
            }

            // Iniciar a leitura do PDF
            Log::info("Iniciando a leitura do PDF");
            try {
                $pdfController = app(PdfController::class);
                $text = $pdfController->lerPDF($pdfPath);
                Log::info("PDF lido.");
            } catch (\Exception $e) {
                Log::error("\nException: " . $e->getMessage());
                return;
            }

            // Gerar os chunks
            Log::info("Iniciando Gerar os chunks");
            try {
                $chunkController = app(ChunkController::class);
                $chunks = $chunkController->chunkText($text, 500, 100, $status);
            } catch (\Exception $e) {
                Log::error("\nException: " . $e->getMessage());
                return;
            }

            // Gerar embeddings
            Log::info("Iniciando Gerar embeddings");
            try {
                $embeddingController = app(EmbeddingController::class);
                Log::info('$embeddingController gerado');
                foreach ($chunks as $chunk) {
                    $embeddingData = $embeddingController->generateEmbedding($chunk);
                    if ($embeddingData && isset($embeddingData['embedding'])) {
                        Embedding::create([
                            'content' => $chunk,
                            'embedding' => new Vector($embeddingData['embedding']),
                            'file_id' => $pdfMetadata->id, // Relaciona com o arquivo processado
                        ]);
                    } else {
                        Log::error("Erro ao gerar embedding para o chunk: " . $chunk);
                    }
                    $status->percent -= 1;
                    $status->save();
                }
            } catch (\Exception $e) {
                Log::error("\nException: " . $e->getMessage());
                return;
            }

            Log::info("Processamento concluído para: " . $this->filePath);
        } catch (\Exception $e) {
            Log::error("Erro no processamento: " . $e->getMessage());
        }
    }
}
