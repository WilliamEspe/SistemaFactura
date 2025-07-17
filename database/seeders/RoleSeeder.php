<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $roles = ['Administrador', 'Ventas', 'Bodega', 'Secretario'];

        foreach ($roles as $rol) {
            Role::firstOrCreate(['nombre' => $rol]);
        }
    }
}
