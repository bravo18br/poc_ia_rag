<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChunkController extends Controller
{
    public function chunkText($text, $chunkSize, $overlap, $status)
    {
        $chunks = [];
        $length = Str::length($text);
        for ($i = 0; $i < $length; $i += ($chunkSize - $overlap)) {
            $chunks[] = Str::substr($text, $i, $chunkSize);
            $status->percent = $i / $length;
            $status->save();
            usleep(10000); // 0.01 segundo
        }
        return $chunks;
    }
}
