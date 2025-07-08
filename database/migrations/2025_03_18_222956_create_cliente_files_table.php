<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_files', function (Blueprint $table) {
            $table->id();
            // Relación con la tabla clientes
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            // Nombre y ruta del archivo
            $table->string('file_name');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_files');
    }
};
