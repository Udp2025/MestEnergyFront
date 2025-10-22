<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // columnas que faltaban en tu migration actual
            if (!Schema::hasColumn('clientes', 'tarifa_region')) {
                $table->string('tarifa_region')->nullable();
            }
            if (!Schema::hasColumn('clientes', 'factor_carga')) {
                $table->string('factor_carga')->nullable();
            }
            if (!Schema::hasColumn('clientes', 'latitud')) {
                $table->string('latitud', 100)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'longitud')) {
                $table->string('longitud', 100)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'contacto_nombre')) {
                $table->string('contacto_nombre', 100)->nullable();
            }
            if (!Schema::hasColumn('clientes', 'estado_cliente')) {
                $table->integer('estado_cliente')->nullable();
            }
            if (!Schema::hasColumn('clientes', 'capacitacion')) {
                $table->integer('capacitacion')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'tarifa_region', 'factor_carga', 'latitud', 'longitud',
                'contacto_nombre', 'estado_cliente', 'capacitacion'
            ]);
        });
    }
};
