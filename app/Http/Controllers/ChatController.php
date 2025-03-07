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

            // Criando contexto formatado para o Ollama
            $contexto = $this->generateContext($docSelecionado, $userInput);
            $prompt = $contexto . "<|start_prompt|>{$userInput}<|end_prompt|>";

            // Preparar params Ollama
            $params = [
                "model" => env('OLLAMA_MODEL', 'llama3.1'),
                "prompt" => $prompt,
                "stream" => false,
                "options" => [
                    "temperature" => env('OLLAMA_MODEL_TEMPERATURE', 0.1),
                    "top_p" => env('OLLAMA_MODEL_TOP_P', 0.1),
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

    public function userInputStream(Request $request)
    {
        try {
            $request->validate([
                'userInput' => 'required',
            ]);

            // Receber input do usuário
            $userInput = $request->input('userInput');
            $docSelecionado = $request->input('docSelecionado');

            // Criando contexto formatado para o Ollama
            $contexto = $this->generateContext($docSelecionado, $userInput);
            $prompt = $contexto . "<|start_prompt|>{$userInput}<|end_prompt|>";

            // Preparar params Ollama
            $params = [
                "model" => env('OLLAMA_MODEL', 'llama3.1'),
                "messages" => [
                    [
                        "role" => "system",
                        "content" => "Você é um assistente administrativo. Usando o contexto informado, responda o prompt do usuário em formato markdown."
                    ],
                    [
                        "role" => "user",
                        "content" => $prompt
                    ]
                ],
                "stream" => true,
                "options" => [
                    "temperature" => (float) env('OLLAMA_MODEL_TEMPERATURE', 0.1),
                    "top_p" => (float) env('OLLAMA_MODEL_TOP_P', 0.1),
                ]
            ];

            $ollama = new OllamaController();
            return $ollama->chatOllamaStream($params);
        } catch (\Exception $e) {
            Log::error("Erro no userInputStream: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Função auxiliar para gerar contexto
    private function generateContext($docSelecionado, $userInput)
    {
        $embeddingController = app(EmbeddingController::class);
        $embedding = new Vector($embeddingController->generateEmbedding($userInput)['embedding']);

        $contextEmbeddings = Embedding::where('file_id', $docSelecionado)
            ->orderByRaw('embedding <=> ?', [$embedding])
            ->limit(env('OLLAMA_CONTEXT_EMBEDDINGS_LIMIT', 5))
            ->get();

        $contexto = '';
        foreach ($contextEmbeddings as $index => $context) {
            $metadados = FileMetadata::where('id', $context->file_id)->first();
            $id = $index + 1;
            if ($metadados) {
                $contexto .= "<|start_context_{$id}|>";
                $contexto .= "<|start_context_metadata_nome_do_arquivo|>{$metadados->filename}<|end_context_metadata_nome_do_arquivo|>";
                $contexto .= "<|start_context_metadata_titulo|>{$metadados->title}<|end_context_metadata_titulo|>";
                $contexto .= "<|start_context_conteudo|>{$context->content}<|end_context_conteudo|>";
                $contexto .= "<|end_context_{$id}|>";
            }
        }
        return $contexto;
    }
}
