<?php

namespace App\Http\Controllers;

use App\Http\Controllers\OllamaController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pgvector\Vector;

use App\Models\FileMetadata;
use App\Models\Embedding;

class ChatController extends Controller
{
    public function userInput(Request $request)
    {
        try {
            $request->validate([
                'userInput' => 'required',
            ]);

            // Receber input do usuário
            $userInput = $request->input('userInput');
            $docSelecionado = $request->input('docSelecionado');

            // Transformar input em embeddings
            $embeddingController = app(EmbeddingController::class);
            $embedding = new Vector($embeddingController->generateEmbedding($userInput)['embedding']);

            // Buscar embeddings semelhantes no db
            $contextEmbeddings = Embedding::where('file_id', $docSelecionado)
                ->orderByRaw('embedding <=> ?', [$embedding])
                ->limit(6)
                ->get();

            // Criar um contexto formatado para o Ollama
            $contexto = '';
            foreach ($contextEmbeddings as $index => $context) {
                $metadados = FileMetadata::where('id', $context->file_id)->first();
                $id = $index + 1;
                if ($metadados) {
                    $contexto .= "<|start_context_{$id}|>\n";
                    $contexto .= "<|start_context_metadata_nome_do_arquivo|>{$metadados->filename}<|end_context_metadata_nome_do_arquivo|>\n";
                    $contexto .= "<|start_context_metadata_caminho_do_arquivo|>{$metadados->path}<|end_context_metadata_caminho_do_arquivo|>\n";
                    $contexto .= "<|start_context_metadata_titulo|>{$metadados->title}<|end_context_metadata_titulo|>\n";
                    $contexto .= "<|start_context_metadata_autor|>{$metadados->author}<|end_context_metadata_autor|>\n";
                    $contexto .= "<|start_context_metadata_produtor|>{$metadados->producer}<|end_context_metadata_produtor|>\n";
                    $contexto .= "<|start_context_metadata_paginas|>{$metadados->pages}<|end_context_metadata_paginas|>\n";
                    $contexto .= "<|start_context_metadata_criado_em|>{$metadados->created_at}<|end_context_metadata_criado_em|>\n";
                    $contexto .= "<|start_context_metadata_atualizado_em|>{$metadados->updated_at}<|end_context_metadata_atualizado_em|>\n";
                    $contexto .= "<|start_context_conteudo|>{$context->content}<|end_context_conteudo|>\n";
                    $contexto .= "<|end_context_{$id}|>";
                }
            }

            // Criando o prompt final para o Ollama
            $prompt = $contexto . "<|start_prompt|>{$userInput}<|end_prompt|>";
            // Log::info($prompt);
            // Preparar params Ollama
            $params = [
                "model" => 'llama3.1',
                "prompt" => $prompt,
                "stream" => false,
                "options" => [
                    "temperature" => 0.1,
                    "top_p" => 0.3,
                ]
            ];

            // Mandar input do usuário para Ollama
            $ollama = new OllamaController();
            $response = $ollama->promptOllama($params);
            $responseData = json_decode($response->getContent(), true);

            if (isset($responseData['response'])) {
                return response()->json(['response' => $responseData['response']], 200);
            } else {
                return response()->json(['error' => json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
