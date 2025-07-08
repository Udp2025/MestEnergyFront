<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dato;
use App\Models\Cliente;
use Carbon\Carbon;

class DatosSeeder extends Seeder
{
    public function run()
    {
        // Obtener los clientes existentes (se asume que ya tienes al menos 2 clientes)
        $clientes = Cliente::all();

        // Define algunos nombres de sitio, dispositivos y rangos para los datos
        $sitios = ['Site A', 'Site B', 'Site C'];
        $dispositivos = ['Device X', 'Device Y', 'Device Z'];

        // Para cada cliente, generamos 100 registros aleatorios
        foreach ($clientes as $cliente) {
            for ($i = 0; $i < 100; $i++) {
                Dato::create([
                    'cliente_id'  => $cliente->id,
                    'fecha'       => Carbon::now()->subDays(rand(0, 365))->subHours(rand(0,23))->subMinutes(rand(0,59))->format('Y-m-d H:i:s'),
                    'site_name'   => $sitios[array_rand($sitios)],
                    'device_name' => $dispositivos[array_rand($dispositivos)],
                    'site_id'     => rand(1, 10),
                    'device_id'   => rand(1, 10),
                    'voltage'     => rand(210, 240) + rand(0, 99) / 100,
                    'current'     => rand(5, 15) + rand(0, 99) / 100,
                    'energy'      => rand(50, 500) + rand(0, 99) / 100,
                    'power'       => rand(100, 1000) + rand(0, 99) / 100,
                    'cost'        => rand(20, 300) + rand(0, 99) / 100,
                ]);
            }
        }
    }
}
