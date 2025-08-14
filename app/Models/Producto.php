<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\FacturaDetalle;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property float $precio
 * @property int $stock
 * @property string|null $motivo_eliminacion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['nombre', 'descripcion', 'precio', 'stock', 'motivo_eliminacion'];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function detalles()
    {
        return $this->hasMany(FacturaDetalle::class);
    }
}
