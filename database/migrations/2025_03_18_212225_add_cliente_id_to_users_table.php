<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClienteIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agrega la columna cliente_id (nullable en caso de que no sea obligatorio)
            $table->unsignedBigInteger('cliente_id')->nullable()->after('id');
            // Define la llave foránea, asumiendo que la tabla clientes ya existe
            $table->foreign('cliente_id')->references('id')->on('clientes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Primero se elimina la restricción y luego la columna
            $table->dropForeign(['cliente_id']);
            $table->dropColumn('cliente_id');
        });
    }
}
