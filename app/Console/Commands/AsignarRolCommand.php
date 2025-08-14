<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class AsignarRolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-role {email} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asignar un rol a un usuario por su email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("Usuario con email {$email} no encontrado.");
            return 1;
        }

        $role = Role::where('nombre', $roleName)->first();
        if (!$role) {
            $this->error("Rol {$roleName} no encontrado.");
            $this->info("Roles disponibles: ventas, cliente, pagos");
            return 1;
        }

        $user->assignRole($roleName);
        $this->info("Rol '{$roleName}' asignado exitosamente al usuario {$user->name} ({$email})");
        
        return 0;
    }
}
