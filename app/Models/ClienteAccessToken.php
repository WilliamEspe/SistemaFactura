<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para tokens de acceso de clientes
 * 
 * @property int $id
 * @property int $cliente_id
 * @property string $name
 * @property string $token
 * @property string|null $plain_text_token
 * @property array|null $abilities
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Cliente $cliente
 */
class ClienteAccessToken extends Model
{
    protected $table = 'cliente_access_tokens';
    
    protected $fillable = [
        'cliente_id',
        'name',
        'token',
        'plain_text_token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];
    
    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    
    /**
     * Relación con el cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
    
    /**
     * Generar un nuevo token
     */
    public static function createToken(Cliente $cliente, string $name, array $abilities = ['*']): array
    {
        $plainTextToken = bin2hex(random_bytes(20)); // 40 caracteres
        $hashedToken = hash('sha256', $plainTextToken);
        
        $token = static::create([
            'cliente_id' => $cliente->id,
            'name' => $name,
            'token' => $hashedToken,
            'plain_text_token' => $plainTextToken,
            'abilities' => $abilities,
        ]);
        
        return [
            'token' => $token,
            'plainTextToken' => $plainTextToken
        ];
    }
    
    /**
     * Buscar token por texto plano
     */
    public static function findToken(string $token): ?self
    {
        $accessToken = static::where('token', hash('sha256', $token))->first();
        
        if ($accessToken) {
            $accessToken->update(['last_used_at' => now()]);
        }
        
        return $accessToken;
    }
}
