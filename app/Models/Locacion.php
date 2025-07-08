<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locacion extends Model
{
    use HasFactory;

    protected $table = 'locaciones';

    protected $fillable = [
        'nombre',
        'monitoreando_desde',
        'area_de_carga',
        'zona_de_carga',
        'division_tarifaria',
        'tipo_sistema_fotovoltaico',
        'tamaño_sistema_fotovoltaico',
        'numero_modulos',
        'potencia_modulos',
        'fecha_instalacion',
        'transformador',
        'voltaje',
        'grupo_tarifario',
        'minimo_factor_carga',
        'calle',
        'numero',
        'colonia',
        'codigo_postal',
        'ciudad',
        'estado',
        'pais',     
    ];

    // Relación con ÁreaDeCarga
    public function areaDeCarga()
    {
        return $this->belongsTo(AreaDeCarga::class, 'area_de_carga');
    }

    // Relación con ZonaDeCarga
    public function zonaDeCarga()
    {
        return $this->belongsTo(ZonaDeCarga::class, 'zona_de_carga');
    }

    // Relación con DivisiónTarifaria
    public function divisionTarifaria()
    {
        return $this->belongsTo(DivisionTarifaria::class, 'division_tarifaria');
    }

    // Relación con GrupoTarifario
    public function grupoTarifario()
    {
        return $this->belongsTo(GrupoTarifario::class, 'grupo_tarifario');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
