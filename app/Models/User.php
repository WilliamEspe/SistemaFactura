<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Role as CustomRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property bool|null $activo
 * @property string|null $motivo_bloqueo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection $roles
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(CustomRole::class, 'role_user', 'user_id', 'role_id');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    /**
     * Relación con el cliente asociado (si el usuario tiene rol de cliente)
     */
    public function cliente()
    {
        return $this->hasOne(Cliente::class);
    }

    public function hasRole($roleName)
    {
        // Si recibe un array, verifica si tiene alguno de esos roles
        if (is_array($roleName)) {
            foreach ($roleName as $role) {
                if ($this->roles->contains('nombre', $role)) {
                    return true;
                }
            }
            return false;
        }
        
        // Si es un solo rol
        return $this->roles->contains('nombre', $roleName);
    }
}