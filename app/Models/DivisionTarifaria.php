<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DivisionTarifaria extends Model
{
    use HasFactory;

    protected $table = 'division_tarifarias';

    protected $fillable = [
        'nombre',
    ];
}
