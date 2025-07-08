<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class ApiDataController extends Controller
{
   public function obtenerInfo()
{
    $url = "https://3bde-18-118-74-86.ngrok-free.app/items/data";

    $headers = [
        "Content-Type: application/json",
        "x-api-key: dCgfO03mNAx_8WUcmrTlvodmh3OlXviAa79hWajbuaE",
        "Accept: application/json",
    ];

    $payload = [
        "table" => "test_telemetry_data",
        "filter_map" => [
            "site_name" => ["Site B", "Site C"],
            "measurement_time" => "[2020-01-01 00:00:00, 2022-01-01 00:00:00)"
        ],
        "aggregation" => [[
            "group_by" => ["device_id"],
            "time_column" => "measurement_time",
            "time_window" => "S",
            "aggregations" => [
                "power" => ["avg", "std"],
                "current" => ["sum"]
            ]
        ]]
    ];

    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // SOLUCIÓN PARA DESARROLLO (elegir una):
    // Opción 1: Deshabilitar verificación SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Opción 2: Usar bundle de certificados (recomendado para producción)
    // curl_setopt($ch, CURLOPT_CAINFO, '/ruta/completa/cacert.pem');

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);

    if ($error) {
        return response()->json([
            'error' => 'CURL_ERROR',
            'message' => $error
        ], 500);
    }

    if ($statusCode !== 200) {
        return response()->json([
            'error' => 'HTTP_ERROR',
            'status' => $statusCode,
            'response' => $response
        ], $statusCode);
    }

    return response()->json(json_decode($response, true));
}
}