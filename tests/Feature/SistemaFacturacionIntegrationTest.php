<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\ClienteAccessToken;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class SistemaFacturacionIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /** @test */
    public function flujo_completo_cliente_consulta_sus_facturas()
    {
        // Arrange: Preparar todo el ecosistema
        $cliente = Cliente::factory()->create([
            'nombre' => 'Juan Pérez',
            'email' => 'juan@empresa.com'
        ]);
        
        $usuario = User::factory()->create(['name' => 'Vendedor Test']);
        
        $producto1 = Producto::factory()->create([
            'nombre' => 'Laptop Dell',
            'precio' => 1200.00
        ]);
        
        $producto2 = Producto::factory()->create([
            'nombre' => 'Mouse Logitech',
            'precio' => 25.00
        ]);

        // Crear facturas con detalles
        $factura1 = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 1225.00,
            'anulada' => false,
            'created_at' => now()->subDays(5)
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura1->id,
            'producto_id' => $producto1->id,
            'cantidad' => 1,
            'precio_unitario' => 1200.00,
            'subtotal' => 1200.00
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura1->id,
            'producto_id' => $producto2->id,
            'cantidad' => 1,
            'precio_unitario' => 25.00,
            'subtotal' => 25.00
        ]);

        $factura2 = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 50.00,
            'anulada' => false,
            'created_at' => now()->subDays(2)
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura2->id,
            'producto_id' => $producto2->id,
            'cantidad' => 2,
            'precio_unitario' => 25.00,
            'subtotal' => 50.00
        ]);

        // Crear token para el cliente
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token Empresarial');
        $token = $tokenData['plainTextToken'];

        // Act 1: Cliente consulta todas sus facturas
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert 1: Verificar respuesta de listado
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Facturas obtenidas exitosamente'
                ]);

        $facturas = $response->json('data.data');
        $this->assertCount(2, $facturas);
        
        // Verificar orden descendente por fecha
        $this->assertEquals($factura2->id, $facturas[0]['id']); // Más reciente primero
        $this->assertEquals($factura1->id, $facturas[1]['id']);

        // Act 2: Cliente consulta detalle de factura específica
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas/{$factura1->id}");

        // Assert 2: Verificar detalle completo
        $response2->assertStatus(200);
        $facturaDetalle = $response2->json('data');
        
        $this->assertEquals($factura1->id, $facturaDetalle['id']);
        $this->assertEquals('1225.00', $facturaDetalle['total']);
        $this->assertEquals(false, $facturaDetalle['anulada']);
        
        // Verificar datos del cliente
        $this->assertEquals('Juan Pérez', $facturaDetalle['cliente']['nombre']);
        $this->assertEquals('juan@empresa.com', $facturaDetalle['cliente']['email']);
        
        // Verificar detalles de productos
        $this->assertCount(2, $facturaDetalle['detalles']);
        
        $detalle1 = collect($facturaDetalle['detalles'])->firstWhere('producto.nombre', 'Laptop Dell');
        $this->assertNotNull($detalle1);
        $this->assertEquals(1, $detalle1['cantidad']);
        $this->assertEquals('1200.00', $detalle1['precio_unitario']);

        // Act 3: Cliente consulta productos disponibles
        $response3 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert 3: Verificar lista de productos
        $response3->assertStatus(200);
        $productos = $response3->json('data');
        $this->assertCount(2, $productos);
        
        $nombresProductos = array_column($productos, 'nombre');
        $this->assertContains('Laptop Dell', $nombresProductos);
        $this->assertContains('Mouse Logitech', $nombresProductos);
    }

    /** @test */
    public function sistema_mantiene_separacion_entre_clientes()
    {
        // Arrange: Crear dos clientes con facturas
        $cliente1 = Cliente::factory()->create(['nombre' => 'Cliente Uno']);
        $cliente2 = Cliente::factory()->create(['nombre' => 'Cliente Dos']);
        $usuario = User::factory()->create();

        $factura1 = Factura::factory()->create([
            'cliente_id' => $cliente1->id,
            'user_id' => $usuario->id,
            'total' => 100.00
        ]);

        $factura2 = Factura::factory()->create([
            'cliente_id' => $cliente2->id,
            'user_id' => $usuario->id,
            'total' => 200.00
        ]);

        // Crear tokens para ambos clientes
        $token1 = ClienteAccessToken::createToken($cliente1, 'Token Cliente 1')['plainTextToken'];
        $token2 = ClienteAccessToken::createToken($cliente2, 'Token Cliente 2')['plainTextToken'];

        // Act & Assert: Cliente 1 solo ve sus facturas
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        $response1->assertStatus(200);
        $facturas1 = $response1->json('data.data');
        $this->assertCount(1, $facturas1);
        $this->assertEquals($factura1->id, $facturas1[0]['id']);

        // Act & Assert: Cliente 2 solo ve sus facturas
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        $response2->assertStatus(200);
        $facturas2 = $response2->json('data.data');
        $this->assertCount(1, $facturas2);
        $this->assertEquals($factura2->id, $facturas2[0]['id']);

        // Act & Assert: Cliente 1 no puede ver factura de Cliente 2
        $response3 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas/{$factura2->id}");

        $response3->assertStatus(404);
    }

    /** @test */
    public function sistema_maneja_filtros_y_paginacion_correctamente()
    {
        // Arrange: Cliente con múltiples facturas en diferentes fechas
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        // Facturas antiguas (hace 20 días)
        Factura::factory()->count(3)->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'created_at' => now()->subDays(20)
        ]);

        // Facturas recientes (hace 5 días)
        Factura::factory()->count(5)->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'created_at' => now()->subDays(5)
        ]);

        // Facturas muy recientes (hoy)
        Factura::factory()->count(2)->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'created_at' => now()
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token filtros')['plainTextToken'];

        // Act & Assert: Filtrar por fecha (últimos 10 días)
        $fechaFiltro = now()->subDays(10)->format('Y-m-d');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas?fecha_desde={$fechaFiltro}");

        $response->assertStatus(200);
        $facturas = $response->json('data.data');
        $this->assertCount(7, $facturas); // 5 + 2 = 7 facturas recientes

        // Act & Assert: Paginación
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas?per_page=3&page=1');

        $response2->assertStatus(200);
        $data = $response2->json('data');
        $this->assertCount(3, $data['data']); // 3 por página
        $this->assertEquals(10, $data['total']); // Total de facturas
        $this->assertEquals(4, $data['last_page']); // Páginas totales
    }

    /** @test */
    public function sistema_actualiza_metadatos_de_tokens_correctamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token metadata');
        $token = $tokenData['plainTextToken'];
        $tokenModel = $tokenData['token'];

        // Verificar estado inicial
        $this->assertNull($tokenModel->last_used_at);

        // Act: Usar el token múltiples veces
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];

        $this->withHeaders($headers)->getJson('/api/facturas');
        $primerUso = $tokenModel->fresh()->last_used_at;

        sleep(1); // Esperar para diferencia en timestamp

        $this->withHeaders($headers)->getJson('/api/productos');
        $segundoUso = $tokenModel->fresh()->last_used_at;

        // Assert: Verificar que last_used_at se actualiza
        $this->assertNotNull($primerUso);
        $this->assertNotNull($segundoUso);
        $this->assertTrue($segundoUso->gt($primerUso));
    }

    /** @test */
    public function sistema_maneja_errores_de_autenticacion_graciosamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $token = ClienteAccessToken::createToken($cliente, 'Token válido')['plainTextToken'];

        $headers = ['Accept' => 'application/json'];

        // Act & Assert: Sin token
        $response1 = $this->withHeaders($headers)->getJson('/api/facturas');
        $response1->assertStatus(401)
                 ->assertJson(['success' => false]);

        // Act & Assert: Token inválido
        $response2 = $this->withHeaders(array_merge($headers, [
            'Authorization' => 'Bearer token_falso'
        ]))->getJson('/api/facturas');
        $response2->assertStatus(401)
                 ->assertJson(['success' => false]);

        // Act & Assert: Token válido funciona
        $response3 = $this->withHeaders(array_merge($headers, [
            'Authorization' => 'Bearer ' . $token
        ]))->getJson('/api/facturas');
        $response3->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}
