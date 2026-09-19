<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $primaryKey = 'id_venta';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_venta',
        'fecha',
        'vendedor',
        'region',
        'producto',
        'monto',
        'estatus',
    ];
}
