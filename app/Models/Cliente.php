<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre','rfc','email','telefono','calle','numero','colonia',
        'codigo_postal','ciudad','estado','pais','cambio_dolar','site',
        'tarifa_region','factor_carga','latitud','longitud','contacto_nombre',
        'estado_cliente','capacitacion'
    ];

    protected $hidden = [
        'password',
    ];

    public function user() {
        return $this->hasOne(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ClienteFile::class, 'cliente_id');
    }

    public function locaciones() {
        return $this->hasMany(Locacion::class);
    }

    public function areas() {
        return $this->hasMany(AreadeCarga::class);
    }

    public function medidores() {
        return $this->hasMany(Medidores::class);
    }

    public function reportes() {
        return $this->hasMany(Reportes::class);
    }

    public function infoFiscal()
    {
        return $this->hasOne(InfoFiscalUsuario::class, 'cliente_id');
    }

    public function planUsuario()
    {
        return $this->hasOne(PlanUsuario::class, 'cliente_id');
    }
}
