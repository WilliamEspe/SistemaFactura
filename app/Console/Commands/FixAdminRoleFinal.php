<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\SpatieRole;

class FixAdminRoleFinal extends Command
{
    protected $signature = 'fix:admin-role-final';
    protected $description = 'Solución final para el rol de administrador';

    public function handle()
    {
        $this->info('🔧 SOLUCIÓN FINAL - ROL ADMINISTRADOR');
        $this->newLine();

        try {
            // 1. Obtener usuario admin
            $admin = User::where('email', 'admin@factura.com')->first();
            $this->info("👤 Usuario: {$admin->email}");

            // 2. Limpiar TODOS los roles del usuario
            $this->info('🗑️ Limpiando roles existentes...');
            $admin->roles()->detach();

            // 3. Obtener rol Administrador usando nuestro modelo personalizado
            $adminRole = SpatieRole::where('name', 'Administrador')->first();
            if (!$adminRole) {
                $this->error('❌ Rol Administrador no encontrado');
                return 1;
            }

            $this->line("   🏷️ Rol encontrado: ID {$adminRole->id} - {$adminRole->name}");

            // 4. Asignar rol usando el método de Spatie
            $admin->assignRole($adminRole);
            $this->line('   ✅ Rol asignado usando assignRole()');

            // 5. Verificar inmediatamente
            $admin = $admin->fresh(); // Refrescar desde BD
            $roles = $admin->getRoleNames();
            $hasRole = $admin->hasRole('Administrador');

            $this->newLine();
            $this->info('✅ VERIFICACIÓN INMEDIATA:');
            $this->line("   Roles asignados: " . $roles->implode(', '));
            $this->line("   hasRole('Administrador'): " . ($hasRole ? 'TRUE' : 'FALSE'));

            if ($hasRole) {
                $this->info('🎉 ¡ROL ADMINISTRADOR ASIGNADO CORRECTAMENTE!');
            } else {
                $this->error('❌ Aún hay problemas con la asignación');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
