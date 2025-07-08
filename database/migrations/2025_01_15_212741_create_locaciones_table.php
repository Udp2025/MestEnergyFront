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
        Schema::create('locaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('monitoreando_desde'); // true o false
            $table->unsignedBigInteger('area_de_carga'); // FK a Area de Carga
            $table->unsignedBigInteger('zona_de_carga'); // FK a Zona de Carga
            $table->unsignedBigInteger('division_tarifaria'); // FK a División Tarifaria
            $table->string('tipo_sistema_fotovoltaico')->nullable(); // Puede ser nulo
            $table->string('tamaño_sistema_fotovoltaico')->nullable();
            $table->string('numero_modulos')->nullable();
            $table->string('potencia_modulos')->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->string('transformador')->nullable();
            $table->decimal('voltaje', 8, 2);
            $table->unsignedBigInteger('grupo_tarifario'); // FK a Grupo Tarifario
            $table->decimal('minimo_factor_de_potencia', 5, 2);
            $table->string('calle');
            $table->string('numero');
            $table->string('colonia');
            $table->string('codigo_postal');
            $table->string('ciudad');
            $table->string('estado');
            $table->string('pais');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->timestamps();

            // Definimos las relaciones
            $table->foreign('area_de_carga')->references('id')->on('areade_cargas')->onDelete('cascade');
            $table->foreign('zona_de_carga')->references('id')->on('zonade_cargas')->onDelete('cascade');
            $table->foreign('division_tarifaria')->references('id')->on('division_tarifarias')->onDelete('cascade');
            $table->foreign('grupo_tarifario')->references('id')->on('grupo_tarifarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locaciones');
    }
};
