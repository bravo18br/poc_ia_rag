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
        $steps = ceil($length / ($chunkSize - $overlap));
        $status->percent = 2 * $steps;
        $status->save();

        for ($i = 0; $i < $length; $i += ($chunkSize - $overlap)) {
            $chunks[] = Str::substr($text, $i, $chunkSize);
            $status->percent -= 1;
            $status->save();
        }

        return $chunks;
    }
}
