<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('original_name');
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('size')->comment('Size in bytes');
            $table->decimal('duration', 10, 2)->nullable()->comment('Duração em segundos');
            $table->timestamps();
            $table->softDeletes();

            // Índices para melhor performance
            $table->index('created_at');
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
