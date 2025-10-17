<?php

namespace App\Support;

class WidgetDefaults
{
    /**
     * Default widget definitions used to seed the catalog.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function catalog(): array
    {
        return [
            [
                'slug' => 'histogram_chart',
                'name' => 'Histograma de corriente',
                'kind' => 'chart',
                'description' => 'Distribución horaria de los valores de corriente agregada por dispositivo.',
                'source_dataset' => 'measurements',
                'default_config' => [
                    'layout' => [
                        'barmode' => 'overlay',
                        'legend' => ['orientation' => 'h'],
                    ],
                ],
            ],
            [
                'slug' => 'scatter_chart',
                'name' => 'Dispersión voltaje vs corriente',
                'kind' => 'chart',
                'description' => 'Relación entre corriente y voltaje promedio por dispositivo.',
                'source_dataset' => 'measurements',
                'default_config' => [
                    'layout' => [
                        'legend' => ['orientation' => 'h'],
                    ],
                ],
            ],
            [
                'slug' => 'timeseries_chart',
                'name' => 'Serie temporal de potencia',
                'kind' => 'chart',
                'description' => 'Evolución horaria de la potencia promedio por dispositivo.',
                'source_dataset' => 'measurements',
                'default_config' => [
                    'layout' => [
                        'legend' => ['orientation' => 'h'],
                    ],
                ],
            ],
            [
                'slug' => 'bar_chart',
                'name' => 'Barras de energía',
                'kind' => 'chart',
                'description' => 'Energía acumulada por dispositivo en el periodo seleccionado.',
                'source_dataset' => 'measurements',
                'default_config' => [
                    'layout' => [
                        'barmode' => 'group',
                        'legend' => ['orientation' => 'h'],
                    ],
                ],
            ],
            [
                'slug' => 'heatmap_chart',
                'name' => 'Patrón de calor de potencia',
                'kind' => 'chart',
                'description' => 'Mapa de calor con patrones horarios de potencia promedio.',
                'source_dataset' => 'measurements',
                'default_config' => [
                    'layout' => [
                        'yaxis' => ['type' => 'category'],
                    ],
                ],
            ],
            [
                'slug' => 'devices_per_site',
                'name' => 'Dispositivos por sitio',
                'kind' => 'kpi',
                'description' => 'Conteo total de dispositivos registrados por sitio.',
                'source_dataset' => 'devices',
                'default_config' => null,
            ],
            [
                'slug' => 'site_availability',
                'name' => 'Disponibilidad del sitio (hoy)',
                'kind' => 'kpi',
                'description' => 'Porcentaje de disponibilidad diaria del sitio para la fecha seleccionada.',
                'source_dataset' => 'site_daily_kpi',
                'default_config' => null,
            ],
        ];
    }
}

