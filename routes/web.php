<?php

use App\Models\StatusRAG;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RagController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/upload', [RagController::class, 'upload']);

Route::get('/status/{id}', function ($id) {
    return StatusRAG::find($id) ?? response()->json(['error' => 'Processo não encontrado'], 404);
});