<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class RepairRoleSystem extends Command
{
    protected $signature = 'system:repair-roles';
    protected $description = 'Diagnosticar y reparar completamente el sistema de roles';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO DEL SISTEMA DE ROLES');
        $this->newLine();

        // 1. Verificar tablas existentes
        $this->info('1. Verificando tablas...');
        $this->checkTables();

        // 2. Verificar usuarios sin roles
        $this->info('2. Verificando usuarios...');
        $this->checkUsers();

        // 3. Reparar sistema completo
        $this->info('3. Reparando sistema...');
        if ($this->confirm('¿Desea reparar el sistema de roles?')) {
            $this->repairSystem();
        }

        $this->info('✅ Diagnóstico completado');
    }

    private function checkTables()
    {
        $requiredTables = ['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'];
        
        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line("   ✅ {$table}: {$count} registros");
            } else {
                $this->error("   ❌ {$table}: NO EXISTE");
            }
        }

        // Verificar tabla personalizada role_user
        if (Schema::hasTable('role_user')) {
            $count = DB::table('role_user')->count();
            $this->line("   ⚠️  role_user (personalizada): {$count} registros");
        }
    }

    private function checkUsers()
    {
        $totalUsers = User::count();
        $this->line("   Total usuarios: {$totalUsers}");

        // Usuarios con roles en Spatie/Permission
        $usersWithSpatie = 0;
        if (Schema::hasTable('model_has_roles')) {
            $usersWithSpatie = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->distinct('model_id')
                ->count();
        }
        $this->line("   Usuarios con roles (Spatie): {$usersWithSpatie}");

        // Usuarios con roles en sistema personalizado
        $usersWithCustom = 0;
        if (Schema::hasTable('role_user')) {
            $usersWithCustom = DB::table('role_user')->distinct('user_id')->count();
        }
        $this->line("   Usuarios con roles (personalizado): {$usersWithCustom}");

        $usersWithoutRoles = $totalUsers - max($usersWithSpatie, $usersWithCustom);
        if ($usersWithoutRoles > 0) {
            $this->error("   ❌ {$usersWithoutRoles} usuarios SIN ROLES");
        }
    }

    private function repairSystem()
    {
        $this->info('🔧 REPARANDO SISTEMA DE ROLES...');

        // 1. Crear tablas si no existen
        if (!Schema::hasTable('model_has_roles')) {
            $this->warn('   Ejecutando migración de Spatie/Permission...');
            $this->call('vendor:publish', ['--provider' => 'Spatie\Permission\PermissionServiceProvider']);
            $this->call('migrate');
        }

        // 2. Crear roles básicos
        $this->createBasicRoles();

        // 3. Asignar roles a usuarios existentes
        $this->assignRolesToUsers();

        $this->info('✅ Sistema reparado correctamente');
    }

    private function createBasicRoles()
    {
        $this->info('   📝 Creando roles básicos...');

        $roles = [
            ['name' => 'administrador', 'guard_name' => 'web'],
            ['name' => 'empleado', 'guard_name' => 'web'],
            ['name' => 'cliente', 'guard_name' => 'web']
        ];

        foreach ($roles as $roleData) {
            $exists = DB::table('roles')->where('name', $roleData['name'])->exists();
            if (!$exists) {
                DB::table('roles')->insert(array_merge($roleData, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $this->line("     ✅ Rol '{$roleData['name']}' creado");
            } else {
                $this->line("     ⚠️  Rol '{$roleData['name']}' ya existe");
            }
        }
    }

    private function assignRolesToUsers()
    {
        $this->info('   👥 Asignando roles a usuarios...');

        // Limpiar asignaciones existentes
        DB::table('model_has_roles')->where('model_type', User::class)->delete();

        // Obtener IDs de roles
        $adminRoleId = DB::table('roles')->where('name', 'administrador')->value('id');
        $empleadoRoleId = DB::table('roles')->where('name', 'empleado')->value('id');

        // Asignar administrador a admin@factura.com
        $adminUser = User::where('email', 'admin@factura.com')->first();
        if ($adminUser && $adminRoleId) {
            DB::table('model_has_roles')->insert([
                'role_id' => $adminRoleId,
                'model_type' => User::class,
                'model_id' => $adminUser->id,
            ]);
            $this->line("     ✅ admin@factura.com → administrador");
        }

        // Asignar empleado a usuarios restantes
        $otherUsers = User::where('email', '!=', 'admin@factura.com')->get();
        foreach ($otherUsers as $user) {
            if ($empleadoRoleId) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $empleadoRoleId,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ]);
            }
        }
        $this->line("     ✅ {$otherUsers->count()} usuarios → empleado");

        $this->info("   📊 Resumen final:");
        $totalAssigned = DB::table('model_has_roles')->where('model_type', User::class)->count();
        $this->line("     Total asignaciones: {$totalAssigned}");
    }
}
