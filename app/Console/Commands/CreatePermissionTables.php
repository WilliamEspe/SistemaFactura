<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class CreatePermissionTables extends Command
{
    protected $signature = 'system:create-permission-tables';
    protected $description = 'Crear manualmente las tablas de Spatie/Permission';

    public function handle()
    {
        $this->info('🔧 Creando tablas de Spatie/Permission...');

        try {
            // 1. Tabla permissions
            if (!DB::getSchemaBuilder()->hasTable('permissions')) {
                DB::statement('
                    CREATE TABLE permissions (
                        id bigserial PRIMARY KEY,
                        name varchar(255) NOT NULL,
                        guard_name varchar(255) NOT NULL,
                        created_at timestamp NULL,
                        updated_at timestamp NULL,
                        CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name)
                    )
                ');
                $this->line('   ✅ Tabla permissions creada');
            } else {
                $this->line('   ⚠️  Tabla permissions ya existe');
            }

            // 2. Tabla model_has_permissions
            if (!DB::getSchemaBuilder()->hasTable('model_has_permissions')) {
                DB::statement('
                    CREATE TABLE model_has_permissions (
                        permission_id bigint NOT NULL,
                        model_type varchar(255) NOT NULL,
                        model_id bigint NOT NULL,
                        PRIMARY KEY (permission_id, model_id, model_type),
                        CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
                    )
                ');
                DB::statement('CREATE INDEX model_has_permissions_model_id_model_type_index ON model_has_permissions (model_id, model_type)');
                $this->line('   ✅ Tabla model_has_permissions creada');
            } else {
                $this->line('   ⚠️  Tabla model_has_permissions ya existe');
            }

            // 3. Tabla model_has_roles
            if (!DB::getSchemaBuilder()->hasTable('model_has_roles')) {
                DB::statement('
                    CREATE TABLE model_has_roles (
                        role_id bigint NOT NULL,
                        model_type varchar(255) NOT NULL,
                        model_id bigint NOT NULL,
                        PRIMARY KEY (role_id, model_id, model_type),
                        CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
                    )
                ');
                DB::statement('CREATE INDEX model_has_roles_model_id_model_type_index ON model_has_roles (model_id, model_type)');
                $this->line('   ✅ Tabla model_has_roles creada');
            } else {
                $this->line('   ⚠️  Tabla model_has_roles ya existe');
            }

            // 4. Tabla role_has_permissions
            if (!DB::getSchemaBuilder()->hasTable('role_has_permissions')) {
                DB::statement('
                    CREATE TABLE role_has_permissions (
                        permission_id bigint NOT NULL,
                        role_id bigint NOT NULL,
                        PRIMARY KEY (permission_id, role_id),
                        CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
                        CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
                    )
                ');
                $this->line('   ✅ Tabla role_has_permissions creada');
            } else {
                $this->line('   ⚠️  Tabla role_has_permissions ya existe');
            }

            // 5. Actualizar tabla roles para compatibilidad con Spatie
            if (!DB::getSchemaBuilder()->hasColumn('roles', 'guard_name')) {
                DB::statement('ALTER TABLE roles ADD COLUMN guard_name varchar(255) NOT NULL DEFAULT \'web\'');
                $this->line('   ✅ Columna guard_name agregada a roles');
            }

            // 6. Crear roles básicos
            $this->createBasicRoles();

            // 7. Asignar roles a todos los usuarios
            $this->assignRolesToAllUsers();

            $this->info('✅ Sistema de permisos creado y configurado correctamente');

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function createBasicRoles()
    {
        $this->info('   📝 Actualizando roles existentes...');

        // Primero, agregar columna name si no existe (para compatibilidad con Spatie)
        if (!DB::getSchemaBuilder()->hasColumn('roles', 'name')) {
            DB::statement('ALTER TABLE roles ADD COLUMN name varchar(255)');
            $this->line('   ✅ Columna name agregada a roles');
            
            // Copiar valores de nombre a name
            DB::statement('UPDATE roles SET name = nombre');
            $this->line('   ✅ Valores copiados de nombre a name');
        }

        $roles = [
            'administrador' => 'administrador',
            'empleado' => 'empleado', 
            'cliente' => 'cliente'
        ];

        foreach ($roles as $nombreValue => $nameValue) {
            // Verificar si existe por nombre o name
            $exists = DB::table('roles')
                ->where('nombre', $nombreValue)
                ->orWhere('name', $nameValue)
                ->exists();
            
            if (!$exists) {
                DB::table('roles')->insert([
                    'nombre' => $nombreValue,
                    'name' => $nameValue,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->line("     ✅ Rol '{$nameValue}' creado");
            } else {
                // Actualizar roles existentes
                DB::table('roles')
                    ->where('nombre', $nombreValue)
                    ->update([
                        'name' => $nameValue,
                        'guard_name' => 'web'
                    ]);
                $this->line("     ⚠️  Rol '{$nameValue}' actualizado");
            }
        }
    }

    private function assignRolesToAllUsers()
    {
        $this->info('   👥 Asignando roles a TODOS los usuarios...');

        // Limpiar asignaciones existentes
        DB::table('model_has_roles')->where('model_type', 'App\Models\User')->delete();
        if (DB::getSchemaBuilder()->hasTable('role_user')) {
            DB::table('role_user')->delete();
        }

        // Obtener IDs de roles
        $adminRoleId = DB::table('roles')->where('name', 'administrador')->value('id');
        $empleadoRoleId = DB::table('roles')->where('name', 'empleado')->value('id');

        $assignedCount = 0;

        // Asignar administrador a admin@factura.com
        $adminUser = User::where('email', 'admin@factura.com')->first();
        if ($adminUser && $adminRoleId) {
            DB::table('model_has_roles')->insert([
                'role_id' => $adminRoleId,
                'model_type' => 'App\Models\User',
                'model_id' => $adminUser->id,
            ]);
            $this->line("     ✅ admin@factura.com → administrador");
            $assignedCount++;
        }

        // Asignar empleado a todos los demás usuarios
        $otherUsers = User::where('email', '!=', 'admin@factura.com')->get();
        foreach ($otherUsers as $user) {
            if ($empleadoRoleId) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $empleadoRoleId,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ]);
                $assignedCount++;
            }
        }
        $this->line("     ✅ {$otherUsers->count()} usuarios → empleado");

        $this->info("   📊 Total asignaciones realizadas: {$assignedCount}");
        
        // Verificar resultado final
        $totalWithRoles = DB::table('model_has_roles')
            ->where('model_type', 'App\Models\User')
            ->distinct('model_id')
            ->count();
        
        $this->info("   📈 Usuarios con roles asignados: {$totalWithRoles}");
    }
}
