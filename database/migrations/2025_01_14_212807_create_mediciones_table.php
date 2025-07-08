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
        Schema::create('mediciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('corriente', 8, 2);
            $table->decimal('voltaje', 8, 2);
            $table->decimal('poder', 8, 2);
            $table->decimal('energia', 8, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mediciones');
    }
};
