<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
 


class User extends Authenticatable
{
    use HasFactory, Notifiable; // Agrega HasRoles

    public const ROLE_SUPER_ADMIN = 'admin';
    public const ROLE_CLIENT = 'normal';

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

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN || (int) $this->cliente_id === 0;
    }

    public function isClientUser(): bool
    {
        return !$this->isSuperAdmin() && $this->role === self::ROLE_CLIENT;
    }

    public function siteId(): ?string
    {
        return $this->cliente?->site;
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
