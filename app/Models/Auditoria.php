<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $fillable = [
        'user_id',
        'accion',
        'descripcion',
        'modulo',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
