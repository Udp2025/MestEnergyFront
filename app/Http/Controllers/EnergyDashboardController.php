<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EnergyCostService;
use Carbon\Carbon;

class EnergyDashboardController extends Controller
{
    protected $service;

    public function __construct(EnergyCostService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $start = $request->query('start_date', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end   = $request->query('end_date', \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d'));
        $site_id = (int) $request->query('site_id');
        $device_id = $request->query('device_id');
        $device_id = $device_id !== null && $device_id !== ''
            ? (int) $device_id
            : null;


        $valor = $this->service->getCosts($start, $end, $device_id, $site_id);
        $latestCost = (object) $valor;

        // Pasamos también los filtros para que el blade los deje en los inputs
        $filters = [
            'start' => $start,
            'end' => $end,
            'device_id' => $device_id,
            'site_id' => $site_id
        ];

        // **Aquí se usa la vista que tienes: clientes.clidash**
        return view('clientes.clidash', compact('latestCost', 'filters'));
    }

    public function costs(Request $request)
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');
        $site_id = (int) $request->query('site_id');
        $device_id = $request->query('device_id');
        $device_id = $device_id !== null && $device_id !== ''
            ? (int) $device_id
            : null;

        if (!$start || !$end || !$site_id) {
            return response()->json(['message' => 'Parámetros inválidos.'], 400);
        }

        $valor = $this->service->getCosts($start, $end, $device_id, $site_id);
        return response()->json($valor);
    }

}
