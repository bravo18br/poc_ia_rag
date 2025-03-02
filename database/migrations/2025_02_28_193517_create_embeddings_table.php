<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmbeddingsTable extends Migration
{
    public function up()
    {
        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->vector('embedding', 768); // Esse valor varia de acordo com o modelo utilizado (nomic-embed-text usa 8192)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('embeddings');
    }
}
