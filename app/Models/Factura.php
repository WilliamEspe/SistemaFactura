<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $cliente_id
 * @property int $user_id
 * @property string $numero_factura
 * @property \Carbon\Carbon|null $fecha_emision
 * @property float $total
 * @property bool $anulada
 * @property string $estado
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Cliente $cliente
 * @property-read User $user
 * @property-read User $usuario
 * @property \Illuminate\Database\Eloquent\Collection<int, FacturaDetalle> $detalles
 * @property \Illuminate\Database\Eloquent\Collection<int, Pago> $pagos
 */
class Factura extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cliente_id', 
        'user_id', 
        'total',
        'anulada',
        'estado',
        'created_by'
    ];

    protected $casts = [
        'anulada' => 'boolean',
        'total' => 'decimal:2',
    ];

    // Relación con Cliente
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Relación con User (quien creó la factura)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con User (alias para created_by)
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación con FacturaDetalle
    public function detalles(): HasMany
    {
        return $this->hasMany(FacturaDetalle::class);
    }

    // Relación con Pagos
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    // Métodos auxiliares
    public function getTotalFormateado(): string
    {
        return '$' . number_format($this->total, 2);
    }

    public function getEstadoColor(): string
    {
        return match($this->estado) {
            'pendiente' => 'yellow',
            'pendiente_pago' => 'blue',
            'pagada' => 'green',
            'anulada' => 'red',
            default => 'gray'
        };
    }

    public function getEstadoTexto(): string
    {
        return match($this->estado) {
            'pendiente' => 'Pendiente',
            'pendiente_pago' => 'Pendiente de Pago',
            'pagada' => 'Pagada',
            'anulada' => 'Anulada',
            default => 'Desconocido'
        };
    }

    public function isPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function isPendientePago(): bool
    {
        return $this->estado === 'pendiente_pago';
    }

    public function isPagada(): bool
    {
        return $this->estado === 'pagada';
    }

    public function isAnulada(): bool
    {
        return $this->anulada || $this->estado === 'anulada';
    }
}