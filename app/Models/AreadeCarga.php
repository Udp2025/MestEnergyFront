<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreadeCarga extends Model
{
    use HasFactory;

    protected $table = 'areade_cargas';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    // Relación con ZonasDeCarga
    public function zonasDeCarga()
    {
        return $this->hasMany(ZonaDeCarga::class, 'area');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
