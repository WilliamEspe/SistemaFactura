<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class RemoveUnwantedRoles extends Command
{
    protected $signature = 'system:remove-unwanted-roles';
    protected $description = 'Eliminar roles no deseados en minúsculas';

    public function handle()
    {
        $this->info('🗑️ ELIMINANDO ROLES NO DESEADOS');
        $this->newLine();

        try {
            // 1. Mostrar estado actual
            $this->info('📋 Estado actual de roles:');
            $allRoles = DB::table('roles')->select('id', 'name', 'guard_name')->get();
            foreach ($allRoles as $role) {
                $userCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();
                $this->line("   - {$role->name} (ID: {$role->id}) → {$userCount} usuarios");
            }

            $this->newLine();

            // 2. Identificar roles a eliminar
            $rolesToDelete = ['administrador', 'empleado', 'cliente'];
            $this->info('🎯 Roles a eliminar (minúsculas):');
            foreach ($rolesToDelete as $roleName) {
                $role = DB::table('roles')->where('name', $roleName)->first();
                if ($role) {
                    $userCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();
                    $this->line("   - {$roleName} (ID: {$role->id}) → {$userCount} usuarios");
                }
            }

            $this->newLine();
            if (!$this->confirm('¿Proceder con la eliminación de estos roles?')) {
                return 0;
            }

            // 3. Reasignar usuarios que tengan roles "empleado" al rol "Ventas"
            $this->info('👥 Reasignando usuarios...');
            
            $empleadoRole = DB::table('roles')->where('name', 'empleado')->first();
            $ventasRole = DB::table('roles')->where('name', 'Ventas')->first();
            
            if ($empleadoRole && $ventasRole) {
                $usersWithEmpleado = DB::table('model_has_roles')
                    ->where('role_id', $empleadoRole->id)
                    ->get();

                foreach ($usersWithEmpleado as $assignment) {
                    // Cambiar de "empleado" a "Ventas"
                    DB::table('model_has_roles')
                        ->where('role_id', $empleadoRole->id)
                        ->where('model_id', $assignment->model_id)
                        ->update(['role_id' => $ventasRole->id]);
                }
                
                $this->line("   ✅ {$usersWithEmpleado->count()} usuarios reasignados de 'empleado' a 'Ventas'");
            }

            // 4. Eliminar asignaciones de roles no deseados
            $this->info('🗑️ Eliminando asignaciones...');
            foreach ($rolesToDelete as $roleName) {
                $role = DB::table('roles')->where('name', $roleName)->first();
                if ($role) {
                    $deletedAssignments = DB::table('model_has_roles')
                        ->where('role_id', $role->id)
                        ->delete();
                    $this->line("   🗑️ {$deletedAssignments} asignaciones eliminadas para '{$roleName}'");
                }
            }

            // 5. Eliminar los roles
            $this->info('🗑️ Eliminando roles...');
            foreach ($rolesToDelete as $roleName) {
                $deleted = DB::table('roles')->where('name', $roleName)->delete();
                if ($deleted > 0) {
                    $this->line("   ✅ Rol '{$roleName}' eliminado");
                }
            }

            $this->newLine();
            $this->info('✅ LIMPIEZA COMPLETADA');

            // 6. Mostrar estado final
            $this->info('📊 Roles restantes:');
            $finalRoles = DB::table('roles')->select('id', 'name', 'guard_name')->get();
            foreach ($finalRoles as $role) {
                $userCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();
                $this->line("   - {$role->name} (usuarios: {$userCount})");
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
