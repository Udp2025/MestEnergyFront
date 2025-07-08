<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('datos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->dateTime('fecha'); // Fecha y hora del registro
            $table->string('site_name'); // Nombre del sitio
            $table->string('device_name'); // Nombre del dispositivo
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('device_id');
            $table->float('voltage'); // Voltaje
            $table->float('current'); // Corriente
            $table->float('energy'); // Energía
            $table->float('power'); // Potencia
            $table->float('cost')->nullable(); // Costos asociados
            $table->timestamps();

            // Llave foránea asociada al cliente
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('datos');
    }
};

