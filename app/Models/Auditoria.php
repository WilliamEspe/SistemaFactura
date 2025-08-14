<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $fillable = [
        'user_id',
        'accion',
        'descripcion',
        'modulo',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function registrar(int $userId, string $accion, string $descripcion, string $modulo = 'general'): self
    {
        return self::create([
            'user_id' => $userId,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'modulo' => $modulo,
        ]);
    }
}
