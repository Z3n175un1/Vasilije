<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'global.usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    /**
     * Columna que identifica al usuario para el login.
     *
     * Sin esto Laravel usa `username`, que no existe en `usuarios`: la
     * consulta de autenticación fallaba con "no existe la columna username"
     * y ningún usuario podía entrar.
     */
    public function getAuthIdentifierName(): string
    {
        return 'usuario';
    }

    protected $fillable = [
        'usuario', 'contrasenha', 'nombres', 'apellidos', 'email', 'rol', 'estado',
        'documento_identidad', 'telefono', 'observaciones'
    ];

    protected $hidden = [
        'contrasenha', 'remember_token',
    ];

    public function getAuthPassword()
    {
        return $this->contrasenha;
    }

    public function getNameAttribute()
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['contrasenha'] = $value;
    }
}
