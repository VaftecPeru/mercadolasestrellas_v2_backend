<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos'; // explícitamente

    protected $primaryKey = 'id_documento';

    public $timestamps = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'numero_documento',
        'serie',
        'estado',
        'fecha_registro',
    ];

    public function pago()
    {
        return $this->hasOne(Pago::class, 'id_documento', 'id_documento');
    }
}

