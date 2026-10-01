<?php

namespace App\Models;

use App\Support\Texto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Socio extends Model
{
    use HasFactory;

    protected $table = 'socios';

    protected $primaryKey = 'id_socio';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'id_socio',
        'id_usuario',
        'fecha_registro',
        'estado',
    ];

    // Eager load persona por defecto para evitar N+1 queries
    protected $with = ['persona'];

    // Accessors para obtener datos de persona sin duplicar en BD
    // COMENTADO: Interfiere con paginación
    // protected $appends = [
    //     'nombres',
    //     'apellido_paterno',
    //     'apellido_materno',
    //     'dni',
    //     'correo',
    //     'telefono',
    //     'direccion',
    //     'sexo',
    //     'nombre_completo'
    // ];

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function Deuda()
    {
        return $this->hasOne(Deuda::class, 'id_socio', 'id_socio');
    }

    public function Puestos()
    {
        return $this->hasMany(Puesto::class, 'id_socio', 'id_socio');
    }

    public function Puesto()
    {
        return $this->hasOne(Puesto::class, 'id_socio', 'id_socio');
    }

    public function Pago()
    {
        return $this->hasOne(Pago::class, 'id_socio', 'id_socio');
    }

    public function Persona()
    {
        return $this->belongsTo(Persona::class, 'id_socio', 'id_persona');
    }

    // Orden alfabético por nombre completo de la persona (personas.id_persona = socios.id_socio)
    public function scopeOrderByNombreCompleto($query)
    {
        return $query->orderByRaw('upper((select nombre_completo from personas where personas.id_persona = socios.id_socio)) asc');
    }

    // Accessors: Obtener datos desde Persona
    public function getNombresAttribute()
    {
        return $this->persona->nombre ?? $this->attributes['nombres'] ?? '';
    }

    public function getApellidoPaternoAttribute()
    {
        return $this->persona->apellido_paterno ?? $this->attributes['apellido_paterno'] ?? '';
    }

    public function getApellidoMaternoAttribute()
    {
        return $this->persona->apellido_materno ?? $this->attributes['apellido_materno'] ?? '';
    }

    public function getDniAttribute()
    {
        return $this->persona->dni ?? $this->attributes['dni'] ?? '';
    }

    public function getCorreoAttribute()
    {
        return $this->persona->correo ?? $this->attributes['correo'] ?? '';
    }

    public function getTelefonoAttribute()
    {
        return $this->persona->telefono ?? $this->attributes['telefono'] ?? '';
    }

    public function getDireccionAttribute()
    {
        return $this->persona->direccion ?? $this->attributes['direccion'] ?? '';
    }

    public function getSexoAttribute()
    {
        return $this->persona->sexo ?? $this->attributes['sexo'] ?? '';
    }

    public function getNombreCompletoAttribute()
    {
        $nombre = $this->persona && $this->persona->nombre_completo ? $this->persona->nombre_completo : '';

        if ($nombre !== '') {
            return $nombre;
        }

        return Texto::capitalizarNombre(trim(($this->nombres ?? '').' '.($this->apellido_paterno ?? '').' '.($this->apellido_materno ?? '')));
    }
}
