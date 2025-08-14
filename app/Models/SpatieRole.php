<?php

namespace App\Models;

use Spatie\Permission\Models\Role as BaseRole;

class SpatieRole extends BaseRole
{
    protected $table = 'roles';
    
    // Sobrescribir para usar nuestra tabla existente
    protected $fillable = [
        'name',
        'nombre', 
        'guard_name',
    ];

    // Mantener compatibilidad con el campo 'nombre'
    public function getNombreAttribute()
    {
        return $this->attributes['name'];
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['nombre'] = $value;
    }
}
