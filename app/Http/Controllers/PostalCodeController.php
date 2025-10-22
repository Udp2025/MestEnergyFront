<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostalCodeController extends Controller
{
    public function lookup($cp)
    {
        $cp = preg_replace('/\D/', '', $cp);
        if (!$cp) return response()->json([], 404);

        // ejemplo: tabla 'postal_codes' con columnas cp, ciudad, estado, pais
        $rows = DB::table('postal_codes')->where('cp', $cp)->get(['ciudad','estado','pais']);

        if ($rows->isNotEmpty()) {
            // Convertir a array único
            $data = $rows->map(function($r){ return [
                'ciudad' => $r->ciudad,
                'estado' => $r->estado,
                'pais'   => $r->pais ?? 'México'
            ]; })->unique()->values()->all();
            return response()->json($data);
        }

        // fallback: si no tienes tabla, puedes llamar a un API externo aquí (SEPOMEX o servicio de CP)
        return response()->json([], 200); // retorna vacío para no romper JS
    }
}
