<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usuario = User::firstOrCreate(
            ['email' => 'admin@factura.com'],
            [
                'name' => 'Admin Principal',
                'password' => Hash::make('password123'),
                'activo' => true
            ]
        );

        // Asignar el rol de Administrador
        $roleAdmin = Role::where('nombre', 'Administrador')->first();
        $usuario->roles()->syncWithoutDetaching([$roleAdmin->id]);

        // Crear usuarios adicionales
        $usuarios = [
            [
                'name' => 'Usuario Ventas',
                'email' => 'ventas@factura.com',
                'password' => Hash::make('password123'),
                'activo' => true
            ],
            [
                'name' => 'Usuario Bodega',
                'email' => 'bodega@factura.com',
                'password' => Hash::make('password123'),
                'activo' => true
            ],
            [
                'name' => 'Usuario Secretario',
                'email' => 'secretario@factura.com',
                'password' => Hash::make('password123'),
                'activo' => true
            ]
        ];

        foreach ($usuarios as $data) {
            $usuario = User::firstOrCreate(['email' => $data['email']], $data);
            $role = Role::where('nombre', 'Ventas')->first();
            $usuario->roles()->syncWithoutDetaching([$role->id]);
        }
        
    }
}
