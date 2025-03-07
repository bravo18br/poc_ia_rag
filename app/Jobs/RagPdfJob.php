<?php

namespace App\Jobs;

use App\Http\Controllers\ChunkController;
use App\Http\Controllers\EmbeddingController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\StatusController;
use App\Models\Embedding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Pgvector\Laravel\Vector;

class RagPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $metadados;

    /**
     * Create a new job instance.
     */
    public function __construct($metadados)
    {
        $this->metadados = $metadados;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("Iniciando o processamento do arquivo: app/private/" . $this->metadados->path);

        $status = new StatusController();
        $status->atualizaStatus($this->metadados->path, 0, 'Preparando metadados');

        try {
            // Preparar os metadados do arquivo
            $text = '';
            $text .= "arquivo_id: {$this->metadados->id}\n";
            $text .= "arquivo_nome: {$this->metadados->filename}\n";
            $text .= "arquivo_caminho: {$this->metadados->path}\n";
            $text .= "arquivo_titulo: {$this->metadados->title}\n";
            $text .= "arquivo_autor: {$this->metadados->author}\n";
            $text .= "arquivo_produtor: {$this->metadados->producer}\n";
            $text .= "arquivo_paginas: {$this->metadados->pages}\n";
            $text .= "arquivo_criado_em: {$this->metadados->created_at}\n";
            $text .= "arquivo_atualizado_em: {$this->metadados->updated_at}\n";

            $status->atualizaStatus($this->metadados->path, 1, 'Preparando metadados');

            // Iniciar a leitura do PDF
            try {
                $pdfController = app(PdfController::class);
                $text .= $pdfController->lerPDF($this->metadados->path);
            } catch (\Exception $e) {
                $errorMessage = "Exception: " . $e->getMessage();
                Log::error($errorMessage);
                $status->atualizaStatus($this->metadados->path, 1, $errorMessage);
                return;
            }

            // Gerar os chunks
            // Log::info("Iniciando Gerar os chunks");
            try {
                $chunkController = app(ChunkController::class);
                $chunkSize = env('OLLAMA_CHUNK_SIZE', 500);
                $chunkOverlap = env('OLLAMA_CHUNK_OVERLAP', 50);
                $chunks = $chunkController->chunkText($text, $chunkSize, $chunkOverlap, $this->metadados->path);
            } catch (\Exception $e) {
                $errorMessage = "Exception: " . $e->getMessage();
                Log::error($errorMessage);
                $status->atualizaStatus($this->metadados->path, 0, $errorMessage);
                return;
            }

            // Gerar embeddings
            Log::info("Iniciando Gerar embeddings");
            $status->atualizaStatus($this->metadados->path, 0, 'Gerando embeddings');
            $embeddingController = app(EmbeddingController::class);
            foreach ($chunks as $i => $chunk) {
                $embeddingData = $embeddingController->generateEmbedding($chunk);
                if ($embeddingData && isset($embeddingData['embedding'])) {
                    Embedding::create([
                        'content' => $chunk,
                        'embedding' => new Vector($embeddingData['embedding']),
                        'file_id' => $this->metadados->id, // Relaciona com o arquivo processado
                    ]);
                } else {
                    Log::error("Erro ao gerar embedding para o chunk: " . $chunk);
                }
                // Log::info( $i . ' / ' . count($chunks));
                $status->atualizaStatus($this->metadados->path, $i / count($chunks), 'Gerando embeddings');
            }
            $status->atualizaStatus($this->metadados->path, 1, 'Gerando embeddings');
            $status->atualizaStatus($this->metadados->path, 1, 'Concluído');
            Log::info("Processamento concluído para: " . $this->metadados->path);
        } catch (\Exception $e) {
            $errorMessage = "Exception: " . $e->getMessage();
            Log::error($errorMessage);
            $status->atualizaStatus($this->metadados->path, 1, $errorMessage);
            return;
        }
    }
}
