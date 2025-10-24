<?php

namespace App\Http\Controllers;

use Carbon\Carbon;use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SensoresController extends Controller
{
    public function index()
    {
        // Sensores
        $sensors = DB::table('devices')
            ->select('site_id', 'device_id', 'device_name', 'resolution_min')
            ->orderBy('site_id')
            ->orderBy('device_id')
            ->get();

        // Clientes: nota usamos la tabla 'clientes'
        $clients = DB::table('clientes') // <-- <--- aquí está el cambio
            ->select('id', 'nombre', 'site')
            ->orderBy('nombre')
            ->get();

        // Agrupar por site (casting a string para evitar mismatch '0' vs 0)
        $clientsBySite = $clients->groupBy(function($c){
            return (string) ($c->site ?? '0');
        });

        // Asignaciones actuales (si creaste la tabla device_client_assignments)
        $assignments = DB::table('device_client_assignments')
            ->select('site_id','device_id','client_id','assigned_at')
            ->get()
            ->keyBy(function($a){ return $a->site_id . ':' . $a->device_id; });

        return view('vincular_sensores', [
            'sensors' => $sensors,
            'clients' => $clients,               // lista completa
            'clientsBySite' => $clientsBySite,   // agrupado por site
            'assignments' => $assignments,
        ]);
    }



    // Vincular un solo sensor
    public function store(Request $request)
    {
        $request->validate([
            'site_id' => 'required|integer',
            'device_id' => 'required|integer',
            'client_id' => 'required|integer',
        ]);

        $site = $request->input('site_id');
        $device = $request->input('device_id');
        $client = $request->input('client_id');

        // Evita duplicados: upsert
        DB::table('device_client_assignments')->updateOrInsert(
            ['site_id' => $site, 'device_id' => $device],
            ['client_id' => $client, 'assigned_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['success' => true, 'message' => 'Vinculado correctamente']);
    }

    // Vincular en lote (bulk)
    public function bulkAssign(Request $request)
    {
        $data = $request->input('assignments', []); // array de {site_id, device_id, client_id}

        if (!is_array($data) || empty($data)) {
            return response()->json(['success' => false, 'message' => 'Nada que vincular'], 422);
        }

        $now = now();
        $inserts = [];
        foreach ($data as $row) {
            if (!isset($row['site_id'], $row['device_id'], $row['client_id'])) continue;
            $inserts[] = [
                'site_id' => (int)$row['site_id'],
                'device_id' => (int)$row['device_id'],
                'client_id' => (int)$row['client_id'],
                'assigned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Recomendado: upsert para evitar duplicados
        foreach ($inserts as $i) {
            DB::table('device_client_assignments')->updateOrInsert(
                ['site_id' => $i['site_id'], 'device_id' => $i['device_id']],
                ['client_id' => $i['client_id'], 'assigned_at' => $now, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        return response()->json(['success' => true, 'message' => 'Vinculaciones en lote completadas']);
    }
}
