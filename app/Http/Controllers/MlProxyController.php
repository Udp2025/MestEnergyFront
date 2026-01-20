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
        $this->ensureAllowedTable($payload);
        $payload = $this->applySiteConstraints($request->user(), $payload);

        return $this->forward($payload, $path);
    }

    protected function getBaseUrl(): string
    {
        // Permite separar el host de ML; si no se define, hereda del de plot
        return (string) config('services.ml.base_url', config('services.plot.base_url', ''));
    }

    protected function getApiKey(): ?string
    {
        // Permite separar el API key de ML; si no se define, hereda del de plot
        return config('services.ml.api_key', config('services.plot.api_key'));
    }
}
