<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiroNegocio extends Model
{
    use HasFactory;

    protected $fiillable = [

    ];
    public function Puesto()
    {
        return $this->belongsTo(Puesto::class,'id_gironegocio','id_gironegocio');
    }
}
