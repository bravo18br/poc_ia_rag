<?php

namespace App\Http\Controllers;

use App\Models\StatusRAG;
use Illuminate\Support\Facades\Log;

class StatusController extends Controller
{
    public function atualizaStatus($path, $percent, $statusText)
    {
        try {
            $status = StatusRAG::where('file_path', $path)->first();
            if (!$status) {
                throw new \Exception("Status não encontrado para o caminho: $path");
            }

            $status->percent = $percent;
            $status->status = $statusText; // Agora correto
            $status->save();

            return true;
        } catch (\Exception $e) {
            Log::error("Exception: " . $e->getMessage());
            return false;
        }
    }
}
