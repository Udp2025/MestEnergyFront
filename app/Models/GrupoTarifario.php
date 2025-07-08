<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoTarifario extends Model
{
    use HasFactory;

    protected $table = 'grupo_tarifarios';

    protected $fillable = [
        'nombre',
        'factor_carga',
    ];
}
