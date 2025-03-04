<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
}