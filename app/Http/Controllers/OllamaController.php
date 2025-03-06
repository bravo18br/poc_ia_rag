<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class OllamaController extends Controller
{
    public function promptOllama($params)
    {
        set_time_limit(600);
        $baseUrl = env('OLLAMA_API_URL', 'http://localhost:11434');

        try {
            // Log::info("$baseUrl/api/generate");
            $response = Http::timeout(600)->post("$baseUrl/api/generate", $params);

            // Log::info("Response: " . $response);
            if ($response->successful()) {
                return response()->json(["response" => $response->json('response')]);
            } else {
                return response()->json(["error" => $response->json('error')]);
            }
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

    public function chatOllama($params)
    {
        set_time_limit(600);
        $baseUrl = env('OLLAMA_API_URL', 'http://localhost:11434');

        try {
            $response = Http::timeout(600)->post("$baseUrl/api/chat", $params);

            if ($response->successful()) {
                return $response->json();
            } else {
                return response()->json(["error" => $response->json('error')]);
            }
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }
    
    public function chatOllamaStream($params)
    {
        set_time_limit(600);
        $baseUrl = env('OLLAMA_API_URL', 'http://localhost:11434');
    
        try {
            // Log::info("Enviando requisição para Ollama (chat)...");
            $response = Http::timeout(600)->withOptions(['stream' => true])
                ->post("$baseUrl/api/chat", $params);
    
            if (!$response->successful()) {
                Log::error("Erro ao chamar Ollama: " . $response->body());
                return response()->json(["error" => $response->json('error')], 500);
            }
    
            // Log::info("Resposta do Ollama recebida, iniciando stream...");
    
            return response()->stream(function () use ($response) {
                $stream = $response->getBody()->detach(); // Obtém o stream bruto
                // Log::info("Início while: ");
                while (!feof($stream)) {
                    $chunk = fread($stream, 4096);
                    if ($chunk !== false) {
                        echo $chunk;
                        // Log::info($chunk);
                        ob_flush();
                        flush();
                    }
                }
                // Log::info("Fim while: ");
                fclose($stream);
                // Log::info("Fclose.");
            }, 200, [
                'Content-Type' => 'application/json',
                'X-Accel-Buffering' => 'no',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Connection' => 'keep-alive',
            ]);
        } catch (\Exception $e) {
            Log::error("Erro no chatOllamaStream: " . $e->getMessage());
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }
}