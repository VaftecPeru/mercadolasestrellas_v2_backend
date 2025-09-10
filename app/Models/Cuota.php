<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    use HasFactory;

    protected $table = 'cuotas';
    protected $primaryKey = 'id_cuota';
    public $timestamps = false;

    protected $fillable = [
        'importe',
        'fecha_vencimiento',
        'fecha_registro',
        'global',
    ];

    public function deudas()
    {
        return $this->hasMany(\App\Models\Deuda::class, 'id_cuota');
    }

    public function cuotaServicios()
    {
        return $this->hasMany(CuotaServicios::class, 'id_cuota');
    }

    public function servicios()
    {
        return $this->cuotaServicios()->with('servicio');
    }

    public function puestosCuota()
    {
        return $this->hasMany(PuestoCuota::class, 'id_cuota');
    }
}
