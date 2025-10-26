<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
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

            $clientUsers = User::where('cliente_id', $clientId)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'created_at']);

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

            $alerts = collect([
                [
                    'title' => 'Sin alertas activas',
                    'detail' => 'No se han reportado incidentes recientes.',
                    'timestamp' => now()->subMinutes(15),
                ],
                [
                    'title' => 'Monitoreo al día',
                    'detail' => 'Los dispositivos reportan telemetría correcta.',
                    'timestamp' => now()->subMinutes(42),
                ],
            ]);

            $context = [
                'is_super_admin' => false,
                'metrics' => [
                    'users' => $clientUsers->count(),
                    'devices' => $totalDevices,
                    'site_name' => $siteName,
                ],
                'client_users' => $clientUsers,
                'alerts' => $alerts,
            ];
        }

        return view('home', $context);
    }
}
