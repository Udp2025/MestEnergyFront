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
                'slug' => 'forecast_power_chart',
                'name' => 'Pronóstico de potencia (7 días)',
                'kind' => 'chart',
                'description' => 'Predicción diaria de la potencia para la próxima semana con intervalo de confianza.',
                'source_dataset' => 'measurements',
                'default_config' => [
                    'layout' => [
                        'legend' => ['orientation' => 'h'],
                    ],
                ],
            ],
            [
                'slug' => 'anomaly_detection_chart',
                'name' => 'Detección de anomalías (24 h)',
                'kind' => 'chart',
                'description' => 'Identificación de anomalías en las últimas 24 horas usando histórico extendido.',
                'source_dataset' => 'measurements',
                'default_config' => [
                    'layout' => [
                        'legend' => ['orientation' => 'h'],
                    ],
                ],
            ],
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
                'slug' => 'histogram_today_chart',
                'name' => 'Histograma de corriente (hoy)',
                'kind' => 'chart',
                'description' => 'Distribución de corriente por dispositivo durante el día actual.',
                'source_dataset' => 'measurements',
                'default_config' => null,
            ],
            [
                'slug' => 'histogram_month_chart',
                'name' => 'Histograma de corriente (mes)',
                'kind' => 'chart',
                'description' => 'Distribución de corriente acumulada en el mes actual (agregado diario).',
                'source_dataset' => 'measurements',
                'default_config' => null,
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
                'slug' => 'scatter_today_chart',
                'name' => 'Voltaje vs corriente (hoy)',
                'kind' => 'chart',
                'description' => 'Dispersión de voltaje y corriente del día actual.',
                'source_dataset' => 'measurements',
                'default_config' => null,
            ],
            [
                'slug' => 'scatter_month_chart',
                'name' => 'Voltaje vs corriente (mes)',
                'kind' => 'chart',
                'description' => 'Dispersión promedio diaria de voltaje y corriente del mes en curso.',
                'source_dataset' => 'measurements',
                'default_config' => null,
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
                'slug' => 'timeseries_today_chart',
                'name' => 'Potencia promedio (hoy)',
                'kind' => 'chart',
                'description' => 'Serie temporal horaria de la potencia del día actual.',
                'source_dataset' => 'measurements',
                'default_config' => null,
            ],
            [
                'slug' => 'timeseries_month_chart',
                'name' => 'Potencia promedio (mes)',
                'kind' => 'chart',
                'description' => 'Serie temporal diaria de la potencia del mes actual.',
                'source_dataset' => 'measurements',
                'default_config' => null,
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
                'slug' => 'bar_today_chart',
                'name' => 'Energía por dispositivo (hoy)',
                'kind' => 'chart',
                'description' => 'Energía acumulada por dispositivo durante el día actual.',
                'source_dataset' => 'measurements',
                'default_config' => null,
            ],
            [
                'slug' => 'bar_month_chart',
                'name' => 'Energía por dispositivo (mes)',
                'kind' => 'chart',
                'description' => 'Energía acumulada por dispositivo en el mes en curso.',
                'source_dataset' => 'measurements',
                'default_config' => null,
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
                'slug' => 'heatmap_today_chart',
                'name' => 'Mapa de calor (hoy)',
                'kind' => 'chart',
                'description' => 'Mapa de calor de potencia por hora durante el día actual.',
                'source_dataset' => 'measurements',
                'default_config' => null,
            ],
            [
                'slug' => 'heatmap_month_chart',
                'name' => 'Mapa de calor (mes)',
                'kind' => 'chart',
                'description' => 'Mapa de calor diario de potencia durante el mes actual.',
                'source_dataset' => 'measurements',
                'default_config' => null,
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
            [
                'slug' => 'energy_today_kpi',
                'name' => 'Energía generada hoy',
                'kind' => 'kpi',
                'description' => 'Energía total acumulada del sitio en el día actual.',
                'source_dataset' => 'site_daily_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'peak_power_kpi',
                'name' => 'Potencia pico del día',
                'kind' => 'kpi',
                'description' => 'Valor máximo de potencia registrado para el sitio durante el día.',
                'source_dataset' => 'site_daily_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'load_factor_kpi',
                'name' => 'Load factor diario',
                'kind' => 'kpi',
                'description' => 'Relación entre la energía real generada y la energía máxima posible (load factor).',
                'source_dataset' => 'site_daily_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'pf_compliance_kpi',
                'name' => 'Cumplimiento factor de potencia',
                'kind' => 'kpi',
                'description' => 'Porcentaje de cumplimiento del factor de potencia objetivo en el sitio.',
                'source_dataset' => 'site_daily_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'data_freshness_kpi',
                'name' => 'Latencia de datos',
                'kind' => 'kpi',
                'description' => 'Minutos transcurridos desde la última actualización de datos del sitio.',
                'source_dataset' => 'site_daily_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'active_devices_kpi',
                'name' => 'Dispositivos activos',
                'kind' => 'kpi',
                'description' => 'Número de dispositivos reportando datos en la última hora.',
                'source_dataset' => 'site_hourly_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'energy_last7_chart',
                'name' => 'Energía últimos 7 días',
                'kind' => 'chart',
                'description' => 'Tendencia de energía diaria generada en la última semana.',
                'source_dataset' => 'site_daily_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'power_factor_trend_chart',
                'name' => 'Tendencia factor de potencia',
                'kind' => 'chart',
                'description' => 'Seguimiento del factor de potencia promedio diario.',
                'source_dataset' => 'site_daily_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'availability_trend_chart',
                'name' => 'Disponibilidad horaria',
                'kind' => 'chart',
                'description' => 'Disponibilidad porcentual por hora durante los últimos días.',
                'source_dataset' => 'site_hourly_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'device_energy_rank_chart',
                'name' => 'Ranking energía por dispositivo',
                'kind' => 'chart',
                'description' => 'Comparativo de energía generada por dispositivo en el periodo seleccionado.',
                'source_dataset' => 'device_daily_kpi',
                'default_config' => null,
            ],
            [
                'slug' => 'ingestion_lag_chart',
                'name' => 'Latencia de ingesta',
                'kind' => 'chart',
                'description' => 'Latencia promedio de los procesos de ingesta de datos recientes.',
                'source_dataset' => 'ingestion_run_kpi',
                'default_config' => null,
            ],
        ];
    }
}
