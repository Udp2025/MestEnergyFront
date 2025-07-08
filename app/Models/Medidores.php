<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medidores extends Model
{
    use HasFactory;

    protected $table = 'medidores';

    protected $fillable = [
        'nombre',
 
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
