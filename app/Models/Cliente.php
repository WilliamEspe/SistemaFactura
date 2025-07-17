<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Factura;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Cliente extends Model
{
    use HasFactory, Notifiable,SoftDeletes;

    protected $fillable = ['nombre', 'email', 'telefono', 'direccion'];

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }
}
