<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MlProxyController extends PlotProxyController
{
    /**
     * Proxy forecast requests to the upstream ML service.
     */
    public function forecast(Request $request): JsonResponse
    {
        return $this->forwardMl($request, '/ml/forecast');
    }

    /**
     * Proxy anomaly detection requests to the upstream ML service.
     */
    public function anomaly(Request $request): JsonResponse
    {
        return $this->forwardMl($request, '/ml/anomaly-detection');
    }

    protected function forwardMl(Request $request, string $path): JsonResponse
    {
        if (!$this->shouldUseRemote()) {
            abort(503, 'El servicio de ML no está disponible en este entorno.');
        }

        $payload = $request->all();
        $payload = $this->normaliseMlTimeColumn($payload);
        $this->ensureAllowedTable($payload);
        $payload = $this->applySiteConstraints($request->user(), $payload);

        $apiKey = (string) ($this->getApiKey() ?? '');
        $logApiKey = (bool) config('services.ml.log_api_key', false);

        \Log::info('ml_proxy.request', [
            'path' => $path,
            'base_url' => $this->getBaseUrl(),
            'api_key_present' => $apiKey !== '',
            // Por defecto no se imprime el secreto; habilitar solo temporalmente para debug.
            'api_key' => $logApiKey ? $apiKey : null,
            'api_key_prefix' => $apiKey !== '' ? substr($apiKey, 0, 6) : null,
            'api_key_len' => $apiKey !== '' ? strlen($apiKey) : null,
            'site_id' => $payload['filter_map']['site_id'] ?? null,
        ]);

        return $this->forward($payload, $path);
    }

    protected function getBaseUrl(): string
    {
        return (string) config('services.plot.base_url', '');
    }

    protected function getApiKey(): ?string
    {
        return config('services.plot.api_key');
    }

    protected function normaliseMlTimeColumn(array $payload): array
    {
        $configured = (string) config('services.ml.time_column', 'measurement_time');
        $configured = $configured !== '' ? $configured : 'measurement_time';

        if (array_key_exists('time_column', $payload)) {
            $payload['time_column'] = $configured;
        }

        $filterMap = $payload['filter_map'] ?? null;
        if (!is_array($filterMap)) {
            return $payload;
        }

        if ($configured !== 'measurement_time' && array_key_exists('measurement_time', $filterMap)) {
            $filterMap[$configured] = $filterMap['measurement_time'];
            unset($filterMap['measurement_time']);
            $payload['filter_map'] = $filterMap;
        }

        return $payload;
    }
}
