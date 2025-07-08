<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarifas extends Model
{
    use HasFactory;

    protected $table = 'tarifas';

    protected $fillable = [
        'clasificacion',
        'subtransmision',
        'transmision',
 
    ];
}
