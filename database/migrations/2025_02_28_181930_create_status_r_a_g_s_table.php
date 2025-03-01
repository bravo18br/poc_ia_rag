<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_r_a_g_s', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->float('percent')->nullable();
            $table->string('status')->default('pendente'); // pendente, processando, concluído
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_r_a_g_s');
    }
};
