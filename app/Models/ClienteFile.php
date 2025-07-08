<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteFile extends Model
{
    protected $table = 'cliente_files';

    protected $fillable = [
        'cliente_id',
        'file_name',
        'file_path',
    ];

    /**
     * Cada archivo pertenece a un cliente.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
