<?php

namespace App\Services\Plot;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PlotClient
{
    public function dataForUser(?User $user, array $payload): array
    {
        $this->ensureAllowedTable($payload);
        $payload = $this->applySiteConstraints($user, $payload);

        if ($this->shouldUseRemote()) {
            return $this->forward($payload, '/items/data');
        }

        return app(LocalPlotService::class)->data($payload);
    }

    protected function ensureAllowedTable(array $payload): void
    {
        $table = Arr::get($payload, 'table');
        if (!is_string($table) || $table === '') {
            throw new HttpException(422, 'El parámetro "table" es obligatorio.');
        }

        $allowed = [
            'measurements',
            'devices',
            'sites',
            'site_daily_kpi',
            'site_hourly_kpi',
            'device_daily_kpi',
            'ingestion_run_kpi',
        ];

        if (!in_array($table, $allowed, true)) {
            throw new HttpException(422, "La tabla solicitada ({$table}) no está permitida.");
        }
    }

    protected function applySiteConstraints(?User $user, array $payload): array
    {
        if (!$user || $user->isSuperAdmin()) {
            return $payload;
        }

        $siteId = session('site') ?? $user->siteId();
        if (!$siteId) {
            throw new HttpException(403, 'El usuario no tiene un sitio asignado.');
        }

        $normalizedSite = ltrim((string) $siteId, '=');
        $table = Arr::get($payload, 'table');
        $filterMap = Arr::get($payload, 'filter_map', []);
        if (!is_array($filterMap)) {
            $filterMap = [];
        }

        if ($table === 'sites') {
            $payload['filter_map'] = ['site_id' => '=' . $normalizedSite];
            return $payload;
        }

        $filterMap['site_id'] = '=' . $normalizedSite;
        $payload['filter_map'] = $filterMap;

        return $payload;
    }

    protected function shouldUseRemote(): bool
    {
        $baseUrl = rtrim((string) config('services.plot.base_url', ''), '/');
        $apiKey = config('services.plot.api_key');

        return $baseUrl !== '' && $apiKey;
    }

    protected function forward(array $payload, string $path): array
    {
        $baseUrl = rtrim((string) config('services.plot.base_url', ''), '/');
        $apiKey = config('services.plot.api_key');

        if (!$baseUrl || !$apiKey) {
            throw new HttpException(500, 'Plot API no está configurado correctamente.');
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => $apiKey,
            ])->post("{$baseUrl}{$path}", $payload);
        } catch (\Throwable $e) {
            Log::error('plot_client.transport_error', [
                'path' => $path,
                'payload' => $payload,
                'exception' => $e->getMessage(),
            ]);
            throw new HttpException(502, 'No se pudo contactar el servicio de datos.');
        }

        if ($response->failed()) {
            $body = $response->json() ?? ['message' => $response->body()];
            Log::warning('plot_client.upstream_error', [
                'path' => $path,
                'status' => $response->status(),
                'payload' => $payload,
                'body' => $body,
            ]);
            throw new HttpException($response->status(), $body['message'] ?? 'Error en el servicio de datos.');
        }

        return $response->json();
    }
}
