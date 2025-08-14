<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;

class ClientePagosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios con rol de validador de pagos
        $usuarioPagos = User::firstOrCreate(
            ['email' => 'pagos@factura.com'],
            [
                'name' => 'Validador de Pagos',
                'password' => Hash::make('password123'),
                'activo' => true
            ]
        );

        $rolePagos = Role::where('nombre', 'Pagos')->first();
        if ($rolePagos) {
            $usuarioPagos->roles()->syncWithoutDetaching([$rolePagos->id]);
        }

        // Crear algunos usuarios clientes para pruebas
        $clientesUsuarios = [
            [
                'name' => 'Juan Cliente',
                'email' => 'juan@cliente.com',
                'password' => Hash::make('password123'),
                'activo' => true
            ],
            [
                'name' => 'Maria Cliente',
                'email' => 'maria@cliente.com',
                'password' => Hash::make('password123'),
                'activo' => true
            ],
        ];

        $roleCliente = Role::where('nombre', 'Cliente')->first();

        foreach ($clientesUsuarios as $data) {
            $usuario = User::firstOrCreate(['email' => $data['email']], $data);
            
            if ($roleCliente) {
                $usuario->roles()->syncWithoutDetaching([$roleCliente->id]);
            }

            // También crear el registro en la tabla clientes si no existe
            Cliente::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nombre' => $data['name'],
                    'telefono' => '123456789',
                    'direccion' => 'Dirección de ejemplo'
                ]
            );
        }

        echo "✅ Usuarios de Pagos y Clientes creados exitosamente.\n";
        echo "📧 Validador de pagos: pagos@factura.com / password123\n";
        echo "📧 Cliente 1: juan@cliente.com / password123\n";
        echo "📧 Cliente 2: maria@cliente.com / password123\n";
    }
}
