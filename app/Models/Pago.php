<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los pagos de facturas
 * 
 * @property int $id
 * @property int $factura_id
 * @property string $tipo_pago
 * @property float $monto
 * @property string|null $numero_transaccion
 * @property string|null $observaciones
 * @property string $estado
 * @property int $pagado_por
 * @property int|null $validado_por
 * @property \Carbon\Carbon|null $validated_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Factura $factura
 * @property-read User $cliente
 * @property-read User|null $validador
 */
class Pago extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'factura_id',
        'tipo_pago',
        'monto',
        'numero_transaccion',
        'observaciones',
        'estado',
        'pagado_por',
        'validado_por',
        'validated_at',
    ];
    
    protected $casts = [
        'monto' => 'decimal:2',
        'validated_at' => 'datetime',
    ];
    
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }
    
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }
    
    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}
