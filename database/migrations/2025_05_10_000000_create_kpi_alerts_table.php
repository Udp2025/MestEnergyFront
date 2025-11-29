<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kpi_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kpi_slug');
            $table->enum('comparison_operator', ['above', 'below'])->default('above');
            $table->double('threshold_value');
            $table->string('site_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('cooldown_minutes')->default(config('kpi_alerts.default_cooldown_minutes', 30));
            $table->timestamp('last_triggered_at')->nullable();
            $table->double('last_value')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'kpi_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_alerts');
    }
};
