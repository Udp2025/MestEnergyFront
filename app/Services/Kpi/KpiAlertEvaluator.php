<?php

namespace App\Services\Kpi;

use App\Models\KpiAlert;
use App\Models\KpiAlertEvent;
use App\Models\User;
use App\Services\Plot\PlotClient;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class KpiAlertEvaluator
{
    public function __construct(
        protected PlotClient $plotClient
    ) {
    }

    public function evaluate(User $user, ?Collection $alerts = null): void
    {
        $alerts = $alerts ?? $user->kpiAlerts()->where('is_active', true)->get();

        foreach ($alerts as $alert) {
            if (!$this->shouldEvaluate($alert)) {
                continue;
            }

            $definition = $alert->definition();
            if (!$definition) {
                Log::warning('kpi_alert.missing_definition', ['alert_id' => $alert->id]);
                continue;
            }

            $result = $this->fetchCurrentValue($user, $alert, $definition);
            if ($result['status'] === 'missing') {
                $this->handleMissingValue($user, $alert, $result['reason']);
                continue;
            }

            $normalizedValue = $this->normaliseValue($result['value'], $definition);
            if ($normalizedValue === null) {
                $this->handleMissingValue($user, $alert, 'normalisation_failed');
                continue;
            }

            $this->handleResult($user, $alert, $normalizedValue);
        }
    }

    protected function fetchCurrentValue(User $user, KpiAlert $alert, array $definition): array
    {
        $payload = [
            'table' => $definition['table'],
        ];

        $siteId = $this->resolveAlertSiteId($alert, $user);
        $filterMap = Arr::get($definition, 'filter_map', []);
        if ($siteId) {
            $filterMap['site_id'] = [(string) $siteId];
        }

        $dateColumn = $definition['date_column'] ?? 'kpi_date';
        $timeframe = $definition['timeframe'] ?? 'today';

        switch ($timeframe) {
            case 'latest_hour':
                $range = $this->buildLatestHourRange();
                $filterMap[$dateColumn] = sprintf('[%s, %s]', $range['from'], $range['to']);
                break;
            case 'today':
            default:
                $filterMap[$dateColumn] = [now()->toDateString()];
                break;
        }

        $payload['filter_map'] = $filterMap;
        $selectColumns = [$definition['value_column'], 'site_id', $dateColumn];
        if (!empty($definition['total_column'])) {
            $selectColumns[] = $definition['total_column'];
        }
        $payload['select_columns'] = array_values(array_unique($selectColumns));

        try {
            $response = $this->plotClient->dataForUser($user, $payload);
        } catch (\Throwable $e) {
            Log::warning('kpi_alert.fetch_error', [
                'alert_id' => $alert->id,
                'message' => $e->getMessage(),
            ]);
            return ['status' => 'missing', 'reason' => 'fetch_error'];
        }

        $rows = $response['data'] ?? (is_array($response) ? $response : []);
        if (!$rows) {
            return ['status' => 'missing', 'reason' => 'no_rows'];
        }

        if ($timeframe === 'latest_hour') {
            usort($rows, static fn ($a, $b) => strcmp((string) ($b[$dateColumn] ?? ''), (string) ($a[$dateColumn] ?? '')));
        }

        $record = null;
        if ($siteId) {
            foreach ($rows as $row) {
                if ((string) ($row['site_id'] ?? '') === (string) $siteId) {
                    $record = $row;
                    break;
                }
            }
        }
        if (!$record) {
            $record = $rows[0];
        }

        if (!array_key_exists($definition['value_column'], $record)) {
            return ['status' => 'missing', 'reason' => 'missing_column'];
        }

        $rawValue = $record[$definition['value_column']];
        if ($rawValue === null || $rawValue === '') {
            return ['status' => 'missing', 'reason' => 'null_value'];
        }

        return ['status' => 'ok', 'value' => (float) $rawValue];
    }

    protected function normaliseValue(float $value, array $definition): ?float
    {
        $format = $definition['format'] ?? 'number';
        return match ($format) {
            'percentage' => $value <= 1 ? $value * 100 : $value,
            'percentage_loose' => $value > 1 ? $value : $value * 100,
            default => $value,
        };
    }

    protected function handleResult(User $owner, KpiAlert $alert, float $value): void
    {
        $alert->last_value = $value;
        $alert->last_evaluated_at = now();
        $alert->save();

        $shouldTrigger = $alert->comparison_operator === 'above'
            ? $value > $alert->threshold_value
            : $value < $alert->threshold_value;

        if (!$shouldTrigger || $this->onCooldown($alert)) {
            return;
        }

        $event = new KpiAlertEvent([
            'user_id' => $alert->user_id,
            'kpi_value' => $value,
            'triggered_at' => now(),
            'context' => [
                'site_id' => $this->resolveAlertSiteId($alert, $owner),
                'threshold' => $alert->threshold_value,
                'comparison' => $alert->comparison_operator,
                'missing_data' => false,
            ],
        ]);

        $alert->events()->save($event);
        $alert->last_triggered_at = $event->triggered_at;
        $alert->save();
    }

    protected function handleMissingValue(User $owner, KpiAlert $alert, string $reason): void
    {
        $alert->last_evaluated_at = now();

        if ($this->onCooldown($alert)) {
            $alert->save();
            return;
        }

        $event = new KpiAlertEvent([
            'user_id' => $alert->user_id,
            'kpi_value' => 0,
            'triggered_at' => now(),
            'context' => [
                'site_id' => $this->resolveAlertSiteId($alert, $owner),
                'missing_data' => true,
                'missing_reason' => $reason,
                'threshold' => $alert->threshold_value,
                'comparison' => $alert->comparison_operator,
            ],
        ]);

        $alert->events()->save($event);
        $alert->last_triggered_at = $event->triggered_at;
        $alert->last_value = null;
        $alert->save();
    }

    protected function onCooldown(KpiAlert $alert): bool
    {
        if (!$alert->last_triggered_at) {
            return false;
        }

        $cooldown = max(1, (int) $alert->cooldown_minutes);
        return $alert->last_triggered_at->greaterThan(Carbon::now()->subMinutes($cooldown));
    }

    protected function buildLatestHourRange(): array
    {
        $end = now();
        $start = (clone $end)->subHour();

        return [
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
        ];
    }

    protected function resolveAlertSiteId(KpiAlert $alert, User $owner): ?string
    {
        if ($alert->site_id) {
            return (string) $alert->site_id;
        }
        if ($owner->isSuperAdmin()) {
            return null;
        }
        return $owner->siteId();
    }

    protected function shouldEvaluate(KpiAlert $alert): bool
    {
        $lastEvaluated = $alert->last_evaluated_at;
        if (!$lastEvaluated) {
            return true;
        }

        $interval = max(1, (int) $alert->cooldown_minutes);
        return $lastEvaluated->lte(Carbon::now()->subMinutes($interval));
    }
}
