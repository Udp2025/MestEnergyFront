<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class PlotProxyController extends Controller
{
    /**
     * Proxy plot requests (charts with aggregation).
     */
    public function plot(Request $request): JsonResponse
    {
        $payload = $request->all();
        $this->ensureAllowedTable($payload);
        $payload = $this->applySiteConstraints($request->user(), $payload);

        return $this->forward($payload, '/items/data/plot');
    }

    /**
     * Proxy raw data requests (sites/devices listings, etc.).
     */
    public function data(Request $request): JsonResponse
    {
        $payload = $request->all();
        $this->ensureAllowedTable($payload);
        $payload = $this->applySiteConstraints($request->user(), $payload);

        return $this->forward($payload, '/items/data');
    }

    /**
     * Ensure the requested table is within the supported whitelist.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function ensureAllowedTable(array $payload): void
    {
        $table = Arr::get($payload, 'table');
        if (!is_string($table) || $table === '') {
            abort(422, 'El parámetro "table" es obligatorio.');
        }

        $allowed = ['measurements', 'devices', 'sites'];
        if (!in_array($table, $allowed, true)) {
            abort(422, "La tabla solicitada ({$table}) no está permitida.");
        }
    }

    /**
     * Apply site-based access control for non-admin users.
     */
    protected function applySiteConstraints(?User $user, array $payload): array
    {
        if (!$user || $user->isSuperAdmin()) {
            return $payload;
        }

        $siteId = session('site') ?? $user->siteId();
        if (!$siteId) {
            abort(403, 'El usuario no tiene un sitio asignado.');
        }

        $normalizedSite = ltrim((string) $siteId, '=');
        $table = Arr::get($payload, 'table');

        // Normalise filter_map to an array
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

    /**
     * Forward the payload to the upstream Plot API.
     */
    protected function forward(array $payload, string $path): JsonResponse
    {
        $baseUrl = rtrim(config('services.plot.base_url', ''), '/');
        $apiKey = config('services.plot.api_key');

        if (!$baseUrl || !$apiKey) {
            abort(500, 'Plot API no está configurado correctamente.');
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => $apiKey,
            ])->post("{$baseUrl}{$path}", $payload);
        } catch (\Throwable $e) {
            \Log::error('plot_proxy.transport_error', [
                'path' => $path,
                'payload' => $payload,
                'exception' => $e->getMessage(),
            ]);
            abort(502, 'No se pudo contactar el servicio de datos.');
        }

        if ($response->failed()) {
            $body = $response->json() ?? ['message' => $response->body()];
            \Log::warning('plot_proxy.upstream_error', [
                'path' => $path,
                'status' => $response->status(),
                'payload' => $payload,
                'body' => $body,
            ]);
            return response()->json($body, $response->status());
        }

        return response()->json($response->json());
    }
}
