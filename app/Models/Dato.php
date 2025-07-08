<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dato extends Model
{
    protected $fillable = [       
    'cliente_id',
    'fecha',
    'site_name',
    'device_name',
    'site_id',
    'device_id',
    'voltage',
    'current',
    'energy',
    'power',
    'cost',
];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
