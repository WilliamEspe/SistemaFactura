<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\FacturaDetalle;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['nombre', 'descripcion', 'precio', 'stock'];

    public function detalles()
    {
        return $this->hasMany(FacturaDetalle::class);
    }
}
