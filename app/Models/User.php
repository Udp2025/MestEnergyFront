<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
 


class User extends Authenticatable
{
    use HasFactory, Notifiable; // Agrega HasRoles

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_image', // Se mantiene si lo usas para subir imagen de perfil
        'role',
        'cliente_id'  // campo para la relación con clientes

    ];
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    public function files()
    {
        return $this->hasMany(ClienteFile::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
