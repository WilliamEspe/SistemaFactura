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

class FacturaApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /** @test */
    public function cliente_puede_ver_solo_sus_facturas_via_api()
    {
        // Arrange: Crear dos clientes con sus facturas
        $cliente1 = Cliente::factory()->create(['nombre' => 'Cliente 1']);
        $cliente2 = Cliente::factory()->create(['nombre' => 'Cliente 2']);
        $usuario = User::factory()->create();
        
        // Crear facturas para cada cliente
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

        // Crear token para cliente1
        $tokenData = ClienteAccessToken::createToken($cliente1, 'API Token');
        $token = $tokenData['plainTextToken'];

        // Act: Hacer petición a la API con token del cliente1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert: Solo debe ver sus propias facturas
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonCount(1, 'data.data'); // Solo 1 factura

        $facturaDevuelta = $response->json('data.data.0');
        $this->assertEquals($cliente1->id, $facturaDevuelta['cliente_id']);
        $this->assertEquals(100.00, $facturaDevuelta['total']);
    }

    /** @test */
    public function api_rechaza_acceso_sin_token()
    {
        // Act: Petición sin token
        $response = $this->getJson('/api/facturas');

        // Assert: Debe rechazar el acceso
        $response->assertStatus(401);
    }

    /** @test */
    public function api_rechaza_token_invalido()
    {
        // Act: Petición con token falso
        $response = $this->withHeaders([
            'Authorization' => 'Bearer token_falso_12345',
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert: Debe rechazar el acceso
        $response->assertStatus(401);
    }

    /** @test */
    public function cliente_puede_ver_detalle_de_su_factura()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        $producto = Producto::factory()->create(['nombre' => 'Producto Test']);
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 150.00
        ]);
        
        FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'precio_unitario' => 50.00,
            'subtotal' => 150.00
        ]);

        $tokenData = ClienteAccessToken::createToken($cliente, 'API Token');
        $token = $tokenData['plainTextToken'];

        // Act: Obtener detalle de la factura
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas/{$factura->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $factura->id,
                        'total' => '150.00',
                        'cliente_id' => $cliente->id
                    ]
                ]);

        // Verificar que incluye los detalles
        $this->assertArrayHasKey('detalles', $response->json('data'));
        $detalles = $response->json('data.detalles');
        $this->assertCount(1, $detalles);
        $this->assertEquals(3, $detalles[0]['cantidad']);
    }

    /** @test */
    public function cliente_no_puede_ver_factura_de_otro_cliente()
    {
        // Arrange
        $cliente1 = Cliente::factory()->create();
        $cliente2 = Cliente::factory()->create();
        $usuario = User::factory()->create();
        
        $facturaDeOtroCliente = Factura::factory()->create([
            'cliente_id' => $cliente2->id,
            'user_id' => $usuario->id
        ]);

        $tokenData = ClienteAccessToken::createToken($cliente1, 'API Token');
        $token = $tokenData['plainTextToken'];

        // Act: Intentar ver factura de otro cliente
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas/{$facturaDeOtroCliente->id}");

        // Assert: Debe rechazar el acceso
        $response->assertStatus(404); // No encontrada (por seguridad)
    }

    /** @test */
    public function api_facturas_soporta_paginacion()
    {
        // Arrange: Crear cliente con múltiples facturas
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        
        // Crear 15 facturas
        for ($i = 1; $i <= 15; $i++) {
            Factura::factory()->create([
                'cliente_id' => $cliente->id,
                'user_id' => $usuario->id,
                'total' => $i * 10
            ]);
        }

        $tokenData = ClienteAccessToken::createToken($cliente, 'API Token');
        $token = $tokenData['plainTextToken'];

        // Act: Petición con paginación
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas?per_page=5&page=1');

        // Assert
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals(5, count($data['data'])); // 5 facturas por página
        $this->assertEquals(15, $data['total']); // Total de facturas
        $this->assertEquals(3, $data['last_page']); // 3 páginas en total
    }

    /** @test */
    public function api_facturas_soporta_filtros_por_fecha()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        
        // Crear facturas en diferentes fechas
        $facturaAntigua = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'created_at' => now()->subDays(10)
        ]);
        
        $facturaReciente = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'created_at' => now()->subDays(2)
        ]);

        $tokenData = ClienteAccessToken::createToken($cliente, 'API Token');
        $token = $tokenData['plainTextToken'];

        // Act: Filtrar por fecha (últimos 5 días)
        $fechaFiltro = now()->subDays(5)->format('Y-m-d');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas?fecha_desde={$fechaFiltro}");

        // Assert: Solo debe devolver la factura reciente
        $response->assertStatus(200);
        $facturas = $response->json('data.data');
        $this->assertCount(1, $facturas);
        $this->assertEquals($facturaReciente->id, $facturas[0]['id']);
    }

    /** @test */
    public function cliente_puede_acceder_a_lista_de_productos()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $producto1 = Producto::factory()->create(['nombre' => 'Producto 1']);
        $producto2 = Producto::factory()->create(['nombre' => 'Producto 2']);

        $tokenData = ClienteAccessToken::createToken($cliente, 'API Token');
        $token = $tokenData['plainTextToken'];

        // Act: Obtener lista de productos
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonCount(2, 'data');

        $productos = $response->json('data');
        $nombresProductos = array_column($productos, 'nombre');
        $this->assertContains('Producto 1', $nombresProductos);
        $this->assertContains('Producto 2', $nombresProductos);
    }
}
