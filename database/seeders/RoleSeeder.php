<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles si no existen
        $adminRole = Role::firstOrCreate(['name' => 'administrador']);
        $clienteRole = Role::firstOrCreate(['name' => 'cliente']);
        $usuarioRole = Role::firstOrCreate(['name' => 'usuario']);
        $ventasRole = Role::firstOrCreate(['name' => 'ventas']);
        $pagosRole = Role::firstOrCreate(['name' => 'pagos']);

        // Crear permisos básicos
        $permissions = [
            'gestionar_usuarios',
            'gestionar_clientes', 
            'gestionar_productos',
            'gestionar_facturas',
            'gestionar_pagos',
            'ver_reportes',
            'acceder_api',
            'gestionar_roles'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Asignar todos los permisos al rol administrador
        $adminRole->syncPermissions(Permission::all());
        
        // Asignar permisos limitados al cliente
        $clienteRole->syncPermissions([
            'acceder_api',
            'gestionar_pagos'
        ]);

        // Encontrar y asignar rol al usuario administrador
        $adminUser = User::where('email', 'admin@factura.com')->first();
        
        if ($adminUser) {
            // Remover roles existentes y asignar administrador
            $adminUser->syncRoles(['administrador']);
            $this->command->info("Rol 'administrador' asignado correctamente a admin@factura.com");
        } else {
            // Crear usuario administrador si no existe
            $adminUser = User::create([
                'name' => 'Administrador',
                'email' => 'admin@factura.com',
                'password' => bcrypt('admin123'),
                'email_verified_at' => now(),
            ]);
            
            $adminUser->assignRole('administrador');
            $this->command->info("Usuario administrador creado y rol asignado correctamente");
        }

        $this->command->info('Roles y permisos configurados correctamente');
        $this->command->info('Usuarios con roles:');
        
        User::with('roles')->get()->each(function ($user) {
            $roles = $user->roles->pluck('name')->implode(', ');
            $this->command->info("- {$user->email}: {$roles}");
        });
    }
}
