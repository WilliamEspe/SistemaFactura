<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckRoleSystem extends Command
{
    protected $signature = 'system:check-roles';
    protected $description = 'Verificar el estado actual del sistema de roles';

    public function handle()
    {
        $this->info('🔍 VERIFICACIÓN DEL SISTEMA DE ROLES');
        $this->newLine();

        try {
            // 1. Usuario admin
            $admin = User::where('email', 'admin@factura.com')->first();
            if ($admin) {
                $this->info("👤 Usuario administrador:");
                $this->line("   Email: {$admin->email}");
                $this->line("   Nombre: {$admin->name}");
                
                // Verificar roles usando Spatie
                $roles = $admin->getRoleNames();
                $this->line("   Roles asignados: " . ($roles->count() > 0 ? $roles->implode(', ') : 'NINGUNO'));
                
                $isAdmin = $admin->hasRole('Administrador') || $admin->hasRole('administrador');
                $this->line("   Es administrador: " . ($isAdmin ? '✅ SÍ' : '❌ NO'));
            } else {
                $this->error("❌ Usuario admin@factura.com NO ENCONTRADO");
            }

            $this->newLine();

            // 2. Estadísticas generales
            $this->info("📊 ESTADÍSTICAS GENERALES:");
            $totalUsers = User::count();
            $this->line("   Total usuarios: {$totalUsers}");
            
            $usersWithRoles = DB::table('model_has_roles')
                ->where('model_type', 'App\Models\User')
                ->distinct('model_id')
                ->count();
            $this->line("   Usuarios con roles: {$usersWithRoles}");
            
            $usersWithoutRoles = $totalUsers - $usersWithRoles;
            if ($usersWithoutRoles > 0) {
                $this->error("   ❌ Usuarios SIN roles: {$usersWithoutRoles}");
            } else {
                $this->line("   ✅ Todos los usuarios tienen roles asignados");
            }

            $this->newLine();

            // 3. Roles disponibles
            $this->info("🏷️  ROLES DISPONIBLES:");
            $roles = DB::table('roles')->select('name', 'nombre', 'guard_name')->get();
            foreach ($roles as $role) {
                $userCount = DB::table('model_has_roles')->where('role_id', DB::table('roles')->where('name', $role->name)->value('id'))->count();
                $this->line("   - {$role->name} (usuarios: {$userCount})");
            }

            $this->newLine();
            $this->info('✅ Verificación completada');

        } catch (\Exception $e) {
            $this->error('❌ Error durante la verificación: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
