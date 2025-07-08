<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mediciones extends Model
{
    use HasFactory;

    protected $table = 'mediciones';

    protected $fillable = [
        'nombre',
        'corriente',
        'voltaje',
        'poder',
        'energia',
    ];
}
