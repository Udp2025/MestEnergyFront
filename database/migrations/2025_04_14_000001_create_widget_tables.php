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
        Schema::create('widget_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();
            $table->string('name', 160);
            $table->enum('kind', ['kpi', 'chart']);
            $table->text('description')->nullable();
            $table->string('source_dataset', 160)->nullable();
            $table->json('default_config')->nullable();
            $table->timestamps();
        });

        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->json('layout_settings')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'title'], 'uq_dashboards_user_title');
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('widget_definition_id')->constrained('widget_definitions')->cascadeOnDelete();
            $table->unsignedInteger('position_index')->default(0);
            $table->json('layout')->nullable();
            $table->json('visual_config')->nullable();
            $table->json('data_filters')->nullable();
            $table->timestamps();
            $table->index(['dashboard_id', 'position_index'], 'idx_dashboard_widgets_dashboard_position');
        });

        Schema::create('widget_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_widget_id')->constrained('dashboard_widgets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('action', ['created', 'updated', 'filters_changed', 'reordered', 'removed']);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('dashboard_widget_id', 'idx_widget_audit_widget');
            $table->index('created_at', 'idx_widget_audit_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widget_audit');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboards');
        Schema::dropIfExists('widget_definitions');
    }
};
