<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class FixAdminRole extends Command
{
    protected $signature = 'roles:fix-admin';
    protected $description = 'Restaurar el rol de administrador al usuario admin@factura.com';

    public function handle()
    {
        $this->info('Restaurando roles y permisos...');

        try {
            // Buscar el usuario administrador
            $adminUser = User::where('email', 'admin@factura.com')->first();
            
            if (!$adminUser) {
                $this->error("❌ Usuario admin@factura.com no encontrado");
                return 1;
            }

            // Crear el rol administrador directamente en la base de datos si no existe
            $adminRoleId = DB::table('roles')->where('name', 'administrador')->value('id');
            
            if (!$adminRoleId) {
                $adminRoleId = DB::table('roles')->insertGetId([
                    'name' => 'administrador',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info('Rol administrador creado.');
            }

            // Limpiar roles existentes del usuario
            DB::table('model_has_roles')->where('model_id', $adminUser->id)->where('model_type', User::class)->delete();
            
            // Asignar el rol administrador
            DB::table('model_has_roles')->insert([
                'role_id' => $adminRoleId,
                'model_type' => User::class,
                'model_id' => $adminUser->id,
            ]);

            $this->info("✅ Rol 'administrador' asignado correctamente a admin@factura.com");
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        $this->info('🎉 Proceso completado exitosamente.');
        $this->info('Ya puedes iniciar sesión con las opciones de administrador.');
        return 0;
    }
}
