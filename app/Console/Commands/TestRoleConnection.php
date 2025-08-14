<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class TestRoleConnection extends Command
{
    protected $signature = 'test:roles';
    protected $description = 'Probar conexión directa con roles';

    public function handle()
    {
        $this->info('🔍 PRUEBA DIRECTA DE ROLES');
        $this->newLine();

        try {
            // 1. Obtener usuario admin
            $admin = User::where('email', 'admin@factura.com')->first();
            $this->info("👤 Usuario: {$admin->email} (ID: {$admin->id})");

            // 2. Verificar asignaciones directas en BD
            $roleAssignments = DB::table('model_has_roles')
                ->where('model_type', 'App\Models\User')
                ->where('model_id', $admin->id)
                ->get();

            $this->info("🔗 Asignaciones en BD:");
            foreach ($roleAssignments as $assignment) {
                $roleName = DB::table('roles')->where('id', $assignment->role_id)->value('name');
                $this->line("   - Role ID: {$assignment->role_id} → {$roleName}");
            }

            // 3. Probar métodos de Spatie directamente
            $this->info("🧪 Pruebas de métodos Spatie:");
            
            // Refrescar el usuario desde BD
            $admin = $admin->fresh();
            
            try {
                $roles = $admin->getRoleNames();
                $this->line("   getRoleNames(): " . $roles->implode(', '));
            } catch (\Exception $e) {
                $this->error("   getRoleNames() ERROR: " . $e->getMessage());
            }

            try {
                $hasAdmin1 = $admin->hasRole('Administrador');
                $this->line("   hasRole('Administrador'): " . ($hasAdmin1 ? 'TRUE' : 'FALSE'));
            } catch (\Exception $e) {
                $this->error("   hasRole('Administrador') ERROR: " . $e->getMessage());
            }

            try {
                $hasAdmin2 = $admin->hasRole('administrador');
                $this->line("   hasRole('administrador'): " . ($hasAdmin2 ? 'TRUE' : 'FALSE'));
            } catch (\Exception $e) {
                $this->error("   hasRole('administrador') ERROR: " . $e->getMessage());
            }

            // 4. Test modelo personalizado
            $this->info("🔍 Test modelo personalizado:");
            try {
                $spatieRoles = \App\Models\SpatieRole::all();
                $this->line("   Roles encontrados por SpatieRole: " . $spatieRoles->count());
                foreach($spatieRoles as $role) {
                    $this->line("     - ID: {$role->id} | Name: {$role->name} | Guard: {$role->guard_name}");
                }
            } catch (\Exception $e) {
                $this->error("   Error con SpatieRole: " . $e->getMessage());
            }

            // 5. Verificar configuración de Spatie
            $this->info("⚙️  Configuración Spatie:");
            $guardName = config('auth.defaults.guard');
            $this->line("   Guard por defecto: {$guardName}");
            
            $permissionConfig = config('permission.models.role');
            $this->line("   Modelo de rol: {$permissionConfig}");

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
