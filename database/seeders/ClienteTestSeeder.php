<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;

class ClienteTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existe el cliente de prueba
        if (Cliente::where('email', 'cliente.test@ejemplo.com')->exists()) {
            $this->command->info('Cliente de prueba ya existe.');
            return;
        }

        try {
            // 1. Crear usuario
            $user = User::create([
                'name' => 'Cliente Test',
                'email' => 'cliente.test@ejemplo.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now()
            ]);
            
            // 2. Asignar rol Cliente
            $user->assignRole('Cliente');
            
            // 3. Crear cliente asociado
            $cliente = Cliente::create([
                'nombre' => 'Cliente Test',
                'email' => 'cliente.test@ejemplo.com',
                'telefono' => '+1234567890',
                'direccion' => 'Calle de Prueba 123, Ciudad',
                'user_id' => $user->id
            ]);
            
            // 4. Generar token automáticamente
            $tokenData = $cliente->createToken('Token Test Automático');
            
            $this->command->info('Cliente de prueba creado exitosamente:');
            $this->command->info('Email: cliente.test@ejemplo.com');
            $this->command->info('Contraseña: password123');
            $this->command->info('Token: ' . $tokenData['token']);
            
        } catch (\Exception $e) {
            $this->command->error('Error al crear cliente de prueba: ' . $e->getMessage());
        }
    }
}
