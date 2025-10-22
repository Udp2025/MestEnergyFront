<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InfoFiscalUsuario extends Model {
  protected $table = 'info_fiscal_usuarios';
  protected $fillable = ['cliente_id','razon_social','regimen_fiscal','domicilio_fiscal','uso_cfdi','contrato_aceptado','notas','csf'];
  public $timestamps = false;
  public function cliente(){ return $this->belongsTo(Cliente::class, 'cliente_id'); }
}
