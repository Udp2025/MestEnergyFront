<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('info_fiscal_usuarios') || !Schema::hasColumn('info_fiscal_usuarios', 'csf')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE info_fiscal_usuarios MODIFY csf VARCHAR(512) NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('info_fiscal_usuarios') || !Schema::hasColumn('info_fiscal_usuarios', 'csf')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE info_fiscal_usuarios MODIFY csf TINYINT(1) NULL');
        }
    }
};
