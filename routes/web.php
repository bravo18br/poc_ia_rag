<?php

use App\Http\Controllers\ChatController;
use App\Models\StatusRAG;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RagController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/upload', [RagController::class, 'upload']);

Route::post('/userInput', [ChatController::class, 'userInput']);

Route::get('/status/{id}', function ($id) {
    return StatusRAG::find($id) ?? response()->json(['error' => 'Processo não encontrado'], 404);
});