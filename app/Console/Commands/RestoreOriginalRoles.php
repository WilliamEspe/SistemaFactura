<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class RestoreOriginalRoles extends Command
{
    protected $signature = 'system:restore-original-roles';
    protected $description = 'Restaurar los roles originales del proyecto';

    public function handle()
    {
        $this->info('🔄 RESTAURANDO ROLES ORIGINALES DEL PROYECTO...');
        $this->newLine();

        try {
            // 1. Restaurar los roles originales
            $originalRoles = [
                'Administrador',
                'Ventas', 
                'Bodega',
                'Secretario',
                'Cliente',
                'Pagos'
            ];

            $this->info('📋 Creando roles originales...');
            foreach ($originalRoles as $roleName) {
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
                } else {
                    $this->line("   ⚠️  Rol '{$roleName}' ya existe");
                }
            }

            // 2. Asignar el rol Administrador al usuario admin@factura.com
            $this->info('👤 Asignando rol Administrador a admin@factura.com...');
            $adminUser = User::where('email', 'admin@factura.com')->first();
            $adminRoleId = DB::table('roles')->where('name', 'Administrador')->value('id');

            if ($adminUser && $adminRoleId) {
                // Limpiar roles existentes del admin
                DB::table('model_has_roles')
                    ->where('model_type', 'App\Models\User')
                    ->where('model_id', $adminUser->id)
                    ->delete();

                // Asignar rol Administrador
                DB::table('model_has_roles')->insert([
                    'role_id' => $adminRoleId,
                    'model_type' => 'App\Models\User',
                    'model_id' => $adminUser->id,
                ]);
                $this->line("   ✅ admin@factura.com → Administrador");
            } else {
                $this->error("   ❌ No se pudo asignar rol Administrador");
            }

            // 3. Mostrar estado actual
            $this->newLine();
            $this->info('📊 ROLES DISPONIBLES AHORA:');
            $allRoles = DB::table('roles')->select('id', 'name')->get();
            foreach ($allRoles as $role) {
                $userCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();
                $this->line("   - {$role->name} (usuarios: {$userCount})");
            }

            $this->newLine();
            $this->info('✅ Roles originales restaurados correctamente');
            $this->warn('⚠️  Los demás usuarios conservan sus roles anteriores (empleado)');

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
