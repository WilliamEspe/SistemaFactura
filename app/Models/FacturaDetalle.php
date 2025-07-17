<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Factura;
use App\Models\Producto;


class FacturaDetalle extends Model
{
    use HasFactory;

    protected $fillable = ['factura_id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal'];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
