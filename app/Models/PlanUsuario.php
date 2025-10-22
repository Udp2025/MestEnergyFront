<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PlanUsuario extends Model {
  protected $table = 'plan_usuarios';
  protected $fillable = ['cliente_id','plan','monto','ciclo','fecha_corte','metodo_pago','fact_automatica','recordatorios_pago'];
  public $timestamps = false;
  public function cliente(){ return $this->belongsTo(Cliente::class, 'cliente_id'); }
}
