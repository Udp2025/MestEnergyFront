<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'sites';
    protected $fillable = ['site_id', 'site_name'];

    // Si no usas timestamps en la tabla, pon public $timestamps = false;
    // public $timestamps = false;
}
