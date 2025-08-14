<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreatePagosUser extends Command
{
    protected $signature = 'create:pagos-user {email?} {password?}';
    protected $description = 'Crear un usuario con rol de validación de pagos';

    public function handle()
    {
        $email = $this->argument('email') ?: $this->ask('Email del usuario');
        $password = $this->argument('password') ?: $this->secret('Contraseña');
        $name = $this->ask('Nombre del usuario', 'Usuario Pagos');

        // Verificar si el usuario ya existe
        if (User::where('email', $email)->exists()) {
            $this->error('❌ Ya existe un usuario con ese email.');
            return 1;
        }

        DB::beginTransaction();
        try {
            // Crear el usuario
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'estado' => 'activo',
            ]);

            // Asignar rol de pagos
            $pagosRole = DB::table('roles')->where('nombre', 'pagos')->first();
            if ($pagosRole) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => $pagosRole->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            
            $this->info('✅ Usuario creado exitosamente:');
            $this->line("   📧 Email: {$email}");
            $this->line("   👤 Nombre: {$name}");
            $this->line("   🔑 Rol: pagos");
            $this->line("   🌐 Puede acceder a: /pagos/validacion");
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->error('❌ Error creando usuario: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
