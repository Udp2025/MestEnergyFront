<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Kpi\KpiAlertEvaluator;
use Illuminate\Console\Command;

class RunKpiAlerts extends Command
{
    protected $signature = 'kpi:run-alerts {--user= : Run alerts for a specific user ID}';

    protected $description = 'Evaluate KPI alerts for all users (or a single user)';

    public function handle(KpiAlertEvaluator $evaluator): int
    {
        $userId = $this->option('user');

        $query = User::query()->whereHas('kpiAlerts', function ($q) {
            $q->where('is_active', true);
        });

        if ($userId) {
            $query->where('id', $userId);
        }

        $count = 0;
        $query->chunkById(100, function ($users) use (&$count, $evaluator) {
            foreach ($users as $user) {
                $evaluator->evaluate($user);
                $count++;
            }
        });

        $this->info("Evaluated alerts for {$count} users.");

        return self::SUCCESS;
    }
}
