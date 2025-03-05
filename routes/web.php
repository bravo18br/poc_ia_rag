<?php

use App\Http\Controllers\ChatController;
use App\Models\FileMetadata;
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

Route::get('/metadata/{id}', function ($id) {
    return FileMetadata::find($id) ?? response()->json(['error' => 'Processo não encontrado'], 404);
});

Route::get('/documents', function () {
    return FileMetadata::all() ?? response()->json(['error' => 'Processo não encontrado'], 404);
});