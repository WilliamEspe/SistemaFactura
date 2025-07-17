<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
     use HasFactory;

    protected $fillable = ['nombre'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
