<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use HasFactory;

    protected $table = 'servicios';

    protected $primaryKey = 'id_servicio';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'costo_unitario',
        'tipo_servicio',
        'fecha_registro',
        'activo',
    ];

    public function cuotaServicios()
    {
        return $this->hasMany(CuotaServicios::class, 'id_servicio');
    }

    public static function nombreTipoServicio($tipo): string
    {
        return match ((string) $tipo) {
            '1' => 'Ordinario',
            '2' => 'Extraordinario',
            '3' => 'Por metro cuadrado',
            '4' => 'Cuota Extraordinaria',
            default => '-',
        };
    }
}
