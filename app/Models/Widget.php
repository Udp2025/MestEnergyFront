<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    protected $fillable = [
        'panel_id',
        'name',
        'type',
        'config'
    ];

    protected $casts = [
        'config' => 'array' // convierte config automáticamente a array
    ];

    // Relación inversa con Panel
    public function panel()
    {
        return $this->belongsTo(Panel::class);
    }
}
