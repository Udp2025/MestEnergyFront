<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonadeCarga extends Model
{
    use HasFactory;

    protected $table = 'zonade_cargas';

    protected $fillable = [
        'nombre',
        'area',
        'descripcion',
    ];

    // Relación con ÁreaDeCarga
    public function areaDeCarga()
    {
        return $this->belongsTo(AreaDeCarga::class, 'area');
    }
}
