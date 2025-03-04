<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function userInput(Request $request)
    {
        try {
            $request->validate([
                'userInput' => 'required',
            ]);

            // Log::info("userInput: " . $request->userInput);

            return response()->json(["message" => 'userInput recebido']);

        } catch (\Exception $e) {
            Log::error("Erro no userInput: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar o userInput'], 500);
        }
    }

}
