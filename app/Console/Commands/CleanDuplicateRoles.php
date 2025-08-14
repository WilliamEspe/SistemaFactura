<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class CleanDuplicateRoles extends Command
{
    protected $signature = 'system:clean-roles';
    protected $description = 'Limpiar roles duplicados y consolidar el sistema';

    public function handle()
    {
        $this->info('🧹 LIMPIANDO SISTEMA DE ROLES...');
        $this->newLine();

        try {
            // 1. Mostrar roles actuales
            $this->info('📋 Roles actuales:');
            $allRoles = DB::table('roles')->select('id', 'nombre', 'name', 'guard_name')->get();
            foreach ($allRoles as $role) {
                $userCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();
                $this->line("   ID: {$role->id} | nombre: '{$role->nombre}' | name: '{$role->name}' | usuarios: {$userCount}");
            }

            $this->newLine();
            if (!$this->confirm('¿Desea proceder con la limpieza?')) {
                return 0;
            }

            // 2. Limpiar asignaciones de roles
            $this->info('🗑️  Limpiando asignaciones existentes...');
            DB::table('model_has_roles')->where('model_type', 'App\Models\User')->delete();

            // 3. Eliminar roles duplicados/no deseados
            $this->info('🗑️  Eliminando roles duplicados...');
            $keepRoles = ['administrador', 'empleado', 'cliente'];
            $rolesToDelete = DB::table('roles')
                ->whereNotIn('name', $keepRoles)
                ->pluck('id');

            foreach ($rolesToDelete as $roleId) {
                DB::table('roles')->where('id', $roleId)->delete();
            }
            $this->line("   ✅ {$rolesToDelete->count()} roles eliminados");

            // 4. Verificar y crear roles necesarios
            $this->info('📝 Verificando roles necesarios...');
            foreach ($keepRoles as $roleName) {
                $exists = DB::table('roles')->where('name', $roleName)->exists();
                if (!$exists) {
                    DB::table('roles')->insert([
                        'nombre' => $roleName,
                        'name' => $roleName,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->line("   ✅ Rol '{$roleName}' creado");
                }
            }

            // 5. Reasignar roles a usuarios
            $this->info('👥 Reasignando roles a usuarios...');
            
            $adminRoleId = DB::table('roles')->where('name', 'administrador')->value('id');
            $empleadoRoleId = DB::table('roles')->where('name', 'empleado')->value('id');

            // Admin
            $adminUser = User::where('email', 'admin@factura.com')->first();
            if ($adminUser && $adminRoleId) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $adminRoleId,
                    'model_type' => 'App\Models\User',
                    'model_id' => $adminUser->id,
                ]);
                $this->line("   ✅ admin@factura.com → administrador");
            }

            // Otros usuarios
            $otherUsers = User::where('email', '!=', 'admin@factura.com')->get();
            $employeeAssignments = [];
            foreach ($otherUsers as $user) {
                $employeeAssignments[] = [
                    'role_id' => $empleadoRoleId,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ];
            }
            
            if (!empty($employeeAssignments)) {
                DB::table('model_has_roles')->insert($employeeAssignments);
                $this->line("   ✅ {$otherUsers->count()} usuarios → empleado");
            }

            $this->newLine();
            $this->info('✅ Sistema de roles limpiado y reorganizado');

            // 6. Verificación final
            $this->call('system:check-roles');

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
