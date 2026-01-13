<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SensoresController extends Controller
{
    public function index()
    {
        // Traer sites
        $sites = DB::table('sites')
            ->select('site_id', 'site_name')
            ->orderBy('site_name')
            ->get();

        // Traer todos los clientes (incluyendo su columna 'site')
        $clients = DB::table('clientes')
            ->select('id', 'nombre', 'site')
            ->orderBy('nombre')
            ->get();

        // Mapeo site_id => client id (si existe)
        $assignedBySite = [];
        foreach ($clients as $c) {
            if (!is_null($c->site) && $c->site !== '') {
                $assignedBySite[$c->site] = $c->id;
            }
        }

        return view('vincular_sensores', compact('sites', 'clients', 'assignedBySite'));
    }

    // Vincular/desasignar un solo site a un cliente
    // Dentro de App\Http\Controllers\SensoresController
public function store(Request $request)
{
    $data = $request->only(['site_id', 'client_id']);

    $validator = Validator::make($data, [
        'site_id' => 'required|integer|exists:sites,site_id',
        'client_id' => 'nullable|integer|exists:clientes,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
    }

    $siteId = (int)$data['site_id'];
    $clientId = $data['client_id'] !== null && $data['client_id'] !== '' ? (int)$data['client_id'] : null;

    DB::beginTransaction();
    try {
        // quien tenía ese site antes (si existe)
        $previousClient = DB::table('clientes')->where('site', $siteId)->value('id');
        $previousClient = $previousClient !== null ? (int)$previousClient : null;

        if (is_null($clientId)) {
            // Desasignar: quitar site a quien lo tuviera
            DB::table('clientes')->where('site', $siteId)->update(['site' => null, 'updated_at' => now()]);
        } else {
            // Si otro cliente tiene este site, limpiarlo (1-1)
            DB::table('clientes')->where('site', $siteId)->where('id', '!=', $clientId)->update(['site' => null, 'updated_at' => now()]);

            // Asignar el site al cliente seleccionado
            DB::table('clientes')->where('id', $clientId)->update(['site' => $siteId, 'updated_at' => now()]);
        }

        DB::commit();

        // recalcular contadores reales
        $totalSites = DB::table('sites')->count();
        $assignedCount = DB::table('clientes')->whereNotNull('site')->where('site', '<>', '')->count();
        $pendingCount = max(0, $totalSites - $assignedCount);

        // action detection
        if ($previousClient === $clientId) {
            $action = 'noop';
        } elseif (is_null($previousClient) && !is_null($clientId)) {
            $action = 'assigned';
        } elseif (!is_null($previousClient) && is_null($clientId)) {
            $action = 'unassigned';
        } else {
            $action = 'reassigned';
        }

        // devolver info fiable para que el frontend actualice los contadores
        return response()->json([
            'success' => true,
            'message' => 'Vinculación actualizada',
            'action' => $action,
            'assignedCount' => (int)$assignedCount,
            'pendingCount' => (int)$pendingCount,
            'assigned_client_id' => $clientId // ahora asignado o null
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error al vincular site: '.$e->getMessage());
        return response()->json(['success' => false, 'message' => 'Error interno'], 500);
    }
}


    // Vincular en lote (bulk)
    public function bulkAssign(Request $request)
    {
        $data = $request->input('assignments', []);

        if (!is_array($data) || empty($data)) {
            return response()->json(['success' => false, 'message' => 'Nada que vincular'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($data as $row) {
                if (!isset($row['site_id'])) continue;
                $siteId = (int)$row['site_id'];
                $clientId = isset($row['client_id']) && $row['client_id'] !== null ? (int)$row['client_id'] : null;

                if (is_null($clientId)) {
                    DB::table('clientes')->where('site', $siteId)->update(['site' => null, 'updated_at' => now()]);
                } else {
                    // limpiar otros clientes que tengan ese site
                    DB::table('clientes')->where('site', $siteId)->where('id', '!=', $clientId)->update(['site' => null, 'updated_at' => now()]);
                    // asignar
                    DB::table('clientes')->where('id', $clientId)->update(['site' => $siteId, 'updated_at' => now()]);
                }
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Vinculaciones en lote completadas']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error bulk vincular: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno en bulk'], 500);
        }
    }

    // Actualizar el nombre del site (desde modal)
    public function updateSiteName(Request $request)
    {
        $data = $request->only(['site_id', 'site_name']);
        $validator = Validator::make($data, [
            'site_id' => 'required|integer|exists:sites,site_id',
            'site_name' => 'required|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::table('sites')->where('site_id', (int)$data['site_id'])->update([
                'site_name' => $data['site_name']
            ]);

            return response()->json(['success' => true, 'message' => 'Nombre actualizado']);
        } catch (\Exception $e) {
            \Log::error('Error actualizando site_name: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }
}
