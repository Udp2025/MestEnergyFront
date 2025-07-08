<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reportes extends Model
{
    use HasFactory;

    protected $table = 'reportes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'locacion',
    ];

    // Relación con Locaciones
    public function locacion()
    {
        return $this->belongsTo(Locacion::class, 'locacion');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
