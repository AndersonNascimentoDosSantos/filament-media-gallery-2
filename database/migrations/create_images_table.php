<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('original_name');
            $table->string('alt')->nullable(); // Adiciona a coluna 'alt'

            $table->string('mime_type', 50);
            $table->unsignedBigInteger('size')->comment('size in bytes');
            $table->timestamps();
            $table->softDeletes();

            // Índices para melhor performance
            $table->index('created_at');
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
