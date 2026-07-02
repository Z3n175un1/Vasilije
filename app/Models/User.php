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

    protected $fillable = [
        'usuario', 'contrasenha', 'nombres', 'apellidos', 'email', 'rol', 'estado'
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
