<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\KpiAlert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'No autenticado.');
        }

        $isSuperAdmin = session('is_super_admin', (int) ($user->cliente_id ?? -1) === 0);

        if ($isSuperAdmin) {
            $totalClients = Cliente::where('id', '!=', 0)->count();
            $totalUsers = User::whereNotNull('cliente_id')
                ->where('cliente_id', '!=', 0)
                ->count();

            $totalSites = DB::table('sites')->count();
            $totalDevices = DB::table('devices')->count();

            $recentClients = Cliente::where('id', '!=', 0)
                ->orderByDesc('created_at')
                ->take(6)
                ->get(['id', 'nombre', 'created_at', 'estado_cliente']);

            $context = [
                'is_super_admin' => true,
                'metrics' => [
                    'clients' => $totalClients,
                    'users' => $totalUsers,
                    'sites' => $totalSites,
                    'devices' => $totalDevices,
                ],
                'recent_clients' => $recentClients,
            ];
        } else {
            $clientId = $user->cliente_id;
            $siteId = session('site') ?? optional($user->cliente)->site;

            $client = null;
            if ($clientId) {
                $client = Cliente::select([
                    'id',
                    'nombre',
                    'rfc',
                    'email',
                    'telefono',
                    'calle',
                    'numero',
                    'colonia',
                    'codigo_postal',
                    'ciudad',
                    'estado',
                    'pais',
                    'cambio_dolar',
                    'tarifa_region',
                    'factor_carga',
                    'contacto_nombre',
                    'estado_cliente',
                    'capacitacion',
                    'site',
                ])->find($clientId);
            }

            $clientUsers = collect();
            if ($clientId) {
                $clientUsers = User::where('cliente_id', $clientId)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email', 'role', 'created_at']);
            }

            $totalDevices = $siteId
                ? DB::table('devices')->where('site_id', $siteId)->count()
                : 0;

            $siteName = null;
            if ($siteId) {
                $siteRecord = DB::table('sites')
                    ->where('site_id', $siteId)
                    ->select('site_name')
                ->first();
                $siteName = $siteRecord->site_name ?? null;
            }

            $alerts = collect();
            if ($client && $siteId) {
                $alerts = $user->kpiAlerts()
                    ->where('site_id', (string) $siteId)
                    ->with(['events' => function ($query) {
                        $query->latest()->limit(1);
                    }])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function (KpiAlert $alert) use ($siteName) {
                        $lastEvent = $alert->events->first();
                        return [
                            'id' => $alert->id,
                            'name' => $alert->definition()['name'] ?? $alert->kpi_slug,
                            'slug' => $alert->kpi_slug,
                            'is_active' => $alert->is_active,
                            'threshold' => $alert->threshold_value,
                            'operator' => $alert->comparison_operator,
                            'site_name' => $siteName,
                            'last_triggered_at' => optional($lastEvent?->triggered_at)->diffForHumans(),
                            'last_value' => $lastEvent?->kpi_value ?? $alert->last_value,
                        ];
                    });
            }

            $clientSummary = $client
                ? [
                    'id' => $client->id,
                    'nombre' => $client->nombre,
                    'rfc' => $client->rfc,
                    'email' => $client->email,
                    'telefono' => $client->telefono,
                    'contacto_nombre' => $client->contacto_nombre,
                    'ciudad' => $client->ciudad,
                    'estado' => $client->estado,
                    'pais' => $client->pais,
                    'direccion' => trim("{$client->calle} {$client->numero}, {$client->colonia}, CP {$client->codigo_postal}"),
                    'tarifa_region' => $client->tarifa_region,
                    'factor_carga' => $client->factor_carga,
                    'cambio_dolar' => $client->cambio_dolar,
                    'estado_cliente' => $client->estado_cliente,
                    'capacitacion' => $client->capacitacion,
                ]
                : null;

            $context = [
                'is_super_admin' => false,
                'metrics' => [
                    'users' => $clientUsers->count(),
                    'devices' => $totalDevices,
                    'site_name' => $siteName,
                ],
                'client_missing' => !$client,
                'client_summary' => $clientSummary,
                'client_users' => $clientUsers,
                'alerts' => $alerts,
                'active_user_id' => $user->id,
            ];
        }

        return view('home', $context);
    }
}
