<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    public $incrementing = true;

    protected $fillable = [
        'id_usuario',
        'id_persona',
        'nombre_usuario',
        'contrasenia',
        'estado',
        'token',
        'fecha_registro',
        'id_rol',
        'debe_cambiar_password',
        'bloqueado',
        'ultimo_acceso',
    ];

    protected $casts = [
        'debe_cambiar_password' => 'boolean',
        'bloqueado' => 'boolean',
    ];

    public function Socio()
    {
        return $this->hasOne(Socio::class, 'id_usuario', 'id_usuario');
    }

    public function Persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function Rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    /**
     * Estado de cuenta derivado de los campos existentes (estado, bloqueado, debe_cambiar_password).
     */
    public function getEstadoCuentaAttribute(): string
    {
        if ($this->bloqueado) {
            return 'bloqueado';
        }

        if ($this->estado !== '1') {
            return 'inactivo';
        }

        if ($this->debe_cambiar_password) {
            return 'pendiente';
        }

        return 'activo';
    }
}
