<?php

namespace App\Http\Controllers;

use App\Models\StatusRAG;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\RagPdfJob;

class RagController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'pdfFile' => 'required|file|mimes:pdf|max:10240',
            ]);
            $path = $request->file('pdfFile')->store('uploads', 'local');

            // Registrar o processamento no banco de dados
            $status = new StatusRAG();
            $status->file_path = $path;
            $status->percent = 0;
            $status->status = 'Em processamento';
            $status->save();

            // Dispara o processamento em background
            RagPdfJob::dispatch($path);

            return response()->json([
                'message' => 'Arquivo enviado com sucesso! O processamento será feito em segundo plano.',
                'path' => $path
            ]);

        } catch (\Exception $e) {
            Log::error("Erro no upload: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar o upload'], 500);
        }
    }
}
