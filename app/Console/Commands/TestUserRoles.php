<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestUserRoles extends Command
{
    protected $signature = 'test:user-roles {email?}';
    protected $description = 'Verificar los roles de un usuario';

    public function handle()
    {
        $email = $this->argument('email') ?: $this->ask('Email del usuario a verificar');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error('Usuario no encontrado');
            return 1;
        }
        
        $this->info("Usuario: {$user->name} ({$user->email})");
        $this->line("Roles:");
        
        $roles = $user->roles->pluck('nombre')->toArray();
        
        if (empty($roles)) {
            $this->warn('  - Sin roles asignados');
        } else {
            foreach ($roles as $rol) {
                $this->line("  - {$rol}");
            }
        }
        
        // Probar método hasRole
        $this->line("\nPruebas de hasRole:");
        $this->line("  hasRole('Administrador'): " . ($user->hasRole('Administrador') ? 'SI' : 'NO'));
        $this->line("  hasRole('pagos'): " . ($user->hasRole('pagos') ? 'SI' : 'NO'));
        $this->line("  hasRole(['Administrador', 'pagos']): " . ($user->hasRole(['Administrador', 'pagos']) ? 'SI' : 'NO'));
        
        return 0;
    }
}
