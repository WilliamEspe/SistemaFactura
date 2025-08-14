<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Factura;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $email
 * @property string|null $telefono
 * @property string|null $direccion
 * @property bool|null $activo
 * @property string|null $motivo_eliminacion
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Factura> $facturas
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ClienteAccessToken> $accessTokens
 */
class Cliente extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['nombre', 'email', 'telefono', 'direccion', 'activo', 'motivo_eliminacion', 'user_id'];

    protected $casts = [
        'activo' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relación con facturas
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Factura, $this>
     */
    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }
    
    /**
     * Relación con el usuario asociado
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Relación con tokens de acceso
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ClienteAccessToken, $this>
     */
    public function accessTokens()
    {
        return $this->hasMany(ClienteAccessToken::class);
    }
    
    /**
     * Crear un nuevo token de acceso
     */
    public function createToken(string $name, array $abilities = ['*']): array
    {
        return ClienteAccessToken::createToken($this, $name, $abilities);
    }
}
