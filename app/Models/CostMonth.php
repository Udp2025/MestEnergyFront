<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostMonth extends Model
{
    use HasFactory;

    protected $table = 'cost_month';
    protected $primaryKey = 'id_cost';
    public $timestamps = false;
    
    protected $fillable = [
        'site_id',
        'fecha_inicio',
        'fecha_fin',
        'cargo_fijo',
        'cargo_base',
        'cargo_intermedio',
        'cargo_punta',
        'cargo_distribucion',
        'cargo_capacidad',
        'subtotal',
        'iva',
        'total',
        'cargo_fijo_pt',
        'consumo_capa_pt',
        'consumo_dist_pt',
        'consumo_base_pt',
        'consumo_intermedio_pt',
        'consumo_punta_pt',
        'factor_potencia_pt'
    ];
    
    // REMOVER el casting automático o ajustarlo
    // protected $casts = [
    //     'fecha_inicio' => 'datetime',
    //     'fecha_fin' => 'datetime',
    // ];
}