<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kpi_alert_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_alert_id')->constrained('kpi_alerts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->double('kpi_value');
            $table->json('context')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_alert_events');
    }
};
