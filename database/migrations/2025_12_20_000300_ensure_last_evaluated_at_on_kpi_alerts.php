<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kpi_alerts', function (Blueprint $table) {
            if (!Schema::hasColumn('kpi_alerts', 'last_evaluated_at')) {
                $table->timestamp('last_evaluated_at')->nullable()->after('last_triggered_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_alerts', function (Blueprint $table) {
            if (Schema::hasColumn('kpi_alerts', 'last_evaluated_at')) {
                $table->dropColumn('last_evaluated_at');
            }
        });
    }
};
