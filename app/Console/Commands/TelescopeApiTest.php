<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class TelescopeApiTest extends Command
{
    protected $signature = 'telescope:api-test';
    protected $description = 'Ejecuta pruebas de API para generar peticiones HTTP monitoreadas por Telescope';

    public function handle()
    {
        $this->info('🌐 INICIANDO PRUEBAS DE API PARA TELESCOPE');
        $this->info('=========================================');
        $this->newLine();

        // Obtener token de administrador
        $this->info('1. 🔑 Obteniendo Token de Autenticación');
        
        try {
            $loginResponse = Http::post('http://127.0.0.1:8000/api/login', [
                'email' => 'admin@factura.com',
                'password' => 'password123'
            ]);

            if ($loginResponse->successful()) {
                $token = $loginResponse->json('access_token');
                $this->info('   ✅ Token obtenido exitosamente');
                $this->newLine();

                // Configurar headers con token
                $headers = [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ];

                // 2. Pruebas de API de Clientes
                $this->info('2. 👥 Pruebas de API - Clientes');
                
                // GET - Listar clientes
                $clientesResponse = Http::withHeaders($headers)
                    ->get('http://127.0.0.1:8000/api/clientes');
                
                if ($clientesResponse->successful()) {
                    $clientes = $clientesResponse->json('data');
                    $this->info("   ✅ GET /api/clientes - {$clientesResponse->status()} - " . count($clientes) . " clientes obtenidos");
                } else {
                    $this->error("   ❌ GET /api/clientes - Error {$clientesResponse->status()}");
                }

                // POST - Crear cliente
                $nuevoClienteResponse = Http::withHeaders($headers)
                    ->post('http://127.0.0.1:8000/api/clientes', [
                        'nombre' => 'Cliente API Test',
                        'email' => 'api.test@telescope.com',
                        'telefono' => '+593-999-111-222',
                        'direccion' => 'Av. API Test #789',
                        'identificacion' => '0123456789',
                        'tipo_identificacion' => 'cedula'
                    ]);

                if ($nuevoClienteResponse->successful()) {
                    $nuevoCliente = $nuevoClienteResponse->json('data');
                    $this->info("   ✅ POST /api/clientes - {$nuevoClienteResponse->status()} - Cliente creado con ID: {$nuevoCliente['id']}");
                    $clienteId = $nuevoCliente['id'];
                } else {
                    $this->error("   ❌ POST /api/clientes - Error {$nuevoClienteResponse->status()}");
                    $clienteId = null;
                }

                $this->newLine();

                // 3. Pruebas de API de Productos
                $this->info('3. 📦 Pruebas de API - Productos');
                
                // GET - Listar productos
                $productosResponse = Http::withHeaders($headers)
                    ->get('http://127.0.0.1:8000/api/productos');
                
                if ($productosResponse->successful()) {
                    $productos = $productosResponse->json('data');
                    $this->info("   ✅ GET /api/productos - {$productosResponse->status()} - " . count($productos) . " productos obtenidos");
                } else {
                    $this->error("   ❌ GET /api/productos - Error {$productosResponse->status()}");
                }

                // POST - Crear producto
                $nuevoProductoResponse = Http::withHeaders($headers)
                    ->post('http://127.0.0.1:8000/api/productos', [
                        'nombre' => 'Producto API Test',
                        'descripcion' => 'Producto creado via API para pruebas Telescope',
                        'precio' => 75.25,
                        'stock' => 25
                    ]);

                if ($nuevoProductoResponse->successful()) {
                    $nuevoProducto = $nuevoProductoResponse->json('data');
                    $this->info("   ✅ POST /api/productos - {$nuevoProductoResponse->status()} - Producto creado con ID: {$nuevoProducto['id']}");
                    $productoId = $nuevoProducto['id'];
                } else {
                    $this->error("   ❌ POST /api/productos - Error {$nuevoProductoResponse->status()}");
                    $productoId = null;
                }

                $this->newLine();

                // 4. Pruebas de API de Facturas
                if ($clienteId && $productoId) {
                    $this->info('4. 🧾 Pruebas de API - Facturas');
                    
                    // POST - Crear factura
                    $nuevaFacturaResponse = Http::withHeaders($headers)
                        ->post('http://127.0.0.1:8000/api/facturas', [
                            'cliente_id' => $clienteId,
                            'detalles' => [
                                [
                                    'producto_id' => $productoId,
                                    'cantidad' => 2,
                                    'precio_unitario' => 75.25
                                ]
                            ]
                        ]);

                    if ($nuevaFacturaResponse->successful()) {
                        $nuevaFactura = $nuevaFacturaResponse->json('data');
                        $this->info("   ✅ POST /api/facturas - {$nuevaFacturaResponse->status()} - Factura creada con ID: {$nuevaFactura['id']}");
                        $facturaId = $nuevaFactura['id'];
                    } else {
                        $this->error("   ❌ POST /api/facturas - Error {$nuevaFacturaResponse->status()}");
                        $facturaId = null;
                    }

                    // GET - Obtener factura específica
                    if ($facturaId) {
                        $facturaResponse = Http::withHeaders($headers)
                            ->get("http://127.0.0.1:8000/api/facturas/{$facturaId}");
                        
                        if ($facturaResponse->successful()) {
                            $this->info("   ✅ GET /api/facturas/{$facturaId} - {$facturaResponse->status()} - Factura obtenida");
                        } else {
                            $this->error("   ❌ GET /api/facturas/{$facturaId} - Error {$facturaResponse->status()}");
                        }
                    }
                }

                $this->newLine();

                // 5. Pruebas de Endpoints de Estado
                $this->info('5. 📊 Pruebas de Endpoints de Estado');
                
                // GET - Dashboard stats (si existe)
                $statsResponse = Http::withHeaders($headers)
                    ->get('http://127.0.0.1:8000/api/dashboard/stats');
                
                $this->info("   ⚡ GET /api/dashboard/stats - {$statsResponse->status()}");

                // GET - Profile
                $profileResponse = Http::withHeaders($headers)
                    ->get('http://127.0.0.1:8000/api/profile');
                
                $this->info("   ⚡ GET /api/profile - {$profileResponse->status()}");

                $this->newLine();

                // 6. Logout
                $this->info('6. 🚪 Cierre de Sesión');
                $logoutResponse = Http::withHeaders($headers)
                    ->post('http://127.0.0.1:8000/api/logout');
                
                if ($logoutResponse->successful()) {
                    $this->info("   ✅ POST /api/logout - {$logoutResponse->status()} - Sesión cerrada exitosamente");
                } else {
                    $this->error("   ❌ POST /api/logout - Error {$logoutResponse->status()}");
                }

            } else {
                $this->error('   ❌ Error al obtener token de autenticación');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("Error en las pruebas de API: {$e->getMessage()}");
            return 1;
        }

        $this->newLine();
        $this->info('🎉 PRUEBAS DE API COMPLETADAS');
        $this->info('=============================');
        $this->info('📊 Revise Laravel Telescope - Sección "Requests" para ver:');
        $this->info('   - Todas las peticiones HTTP/API ejecutadas');
        $this->info('   - Códigos de respuesta y tiempos');
        $this->info('   - Headers y payloads de cada petición');
        $this->info('   - Middleware ejecutado en cada request');
        $this->newLine();
        
        $this->info('🔗 URL Telescope Requests: http://127.0.0.1:8000/telescope/requests');

        return 0;
    }
}
