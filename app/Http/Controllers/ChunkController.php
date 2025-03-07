<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

class ChunkController extends Controller
{
    public function chunkText($text, $chunkSize, $overlap, $path)
    {
        $status = new StatusController();
        $status->atualizaStatus($path, 0, 'Gerando chunks');
        $chunks = [];
        $length = Str::length($text);
        for ($i = 0; $i < $length; $i += ($chunkSize - $overlap)) {
            $chunks[] = Str::substr($text, $i, $chunkSize);
            $status->atualizaStatus($path, $i / $length, 'Gerando chunks');
            // usleep(10000); // 0.01 segundo
        }
        $status->atualizaStatus($path, 1, 'Gerando chunks');
        return $chunks;
    }
}
