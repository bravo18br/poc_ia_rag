<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('files_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('title')->nullable();
            $table->string('author')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });

        // Adicionar coluna para relacionar embeddings com files_metadata
        Schema::table('embeddings', function (Blueprint $table) {
            $table->foreignId('file_id')->nullable()->constrained('files_metadata')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('embeddings', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->dropColumn('file_id');
        });

        Schema::dropIfExists('files_metadata');
    }
};
