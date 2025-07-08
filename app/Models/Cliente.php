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
        'nombre',
        'razon_social',
        'email',
        'telefono',
        'calle',
        'numero',
        'colonia',
        'codigo_postal',
        'ciudad',
        'estado',
        'pais',
        'cambio_dolar',
    ];
    protected $hidden = [
        'password',
        // Agrega otros campos que necesites ocultar
    ];
// En el modelo Cliente
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
}
