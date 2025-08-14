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

class FacturaControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /** @test */
    public function index_retorna_facturas_del_cliente_autenticado()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        // Facturas del cliente autenticado
        $factura1 = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 100.00,
            'created_at' => now()->subDays(2)
        ]);

        $factura2 = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 200.00,
            'created_at' => now()->subDays(1)
        ]);

        // Factura de otro cliente (no debe aparecer)
        Factura::factory()->create([
            'cliente_id' => $otroCliente->id,
            'user_id' => $usuario->id,
            'total' => 300.00
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Facturas obtenidas exitosamente'
                ]);

        $facturas = $response->json('data.data');
        $this->assertCount(2, $facturas);

        // Verificar orden descendente por fecha (más reciente primero)
        $this->assertEquals($factura2->id, $facturas[0]['id']);
        $this->assertEquals($factura1->id, $facturas[1]['id']);

        // Verificar que contiene los datos esperados
        $this->assertEquals('200.00', $facturas[0]['total']);
        $this->assertEquals('100.00', $facturas[1]['total']);
    }

    /** @test */
    public function index_aplica_filtros_de_fecha_correctamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        // Facturas en diferentes fechas
        $facturaAntigua = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'created_at' => now()->subDays(20)
        ]);

        $facturaReciente = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'created_at' => now()->subDays(5)
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act - Filtrar últimos 10 días
        $fechaFiltro = now()->subDays(10)->format('Y-m-d');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas?fecha_desde={$fechaFiltro}");

        // Assert
        $response->assertStatus(200);
        $facturas = $response->json('data.data');
        
        $this->assertCount(1, $facturas);
        $this->assertEquals($facturaReciente->id, $facturas[0]['id']);
    }

    /** @test */
    public function index_maneja_paginacion_correctamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        // Crear 15 facturas
        Factura::factory()->count(15)->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act - Primera página con 5 elementos
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas?per_page=5&page=1');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(5, $data['data']);
        $this->assertEquals(15, $data['total']);
        $this->assertEquals(3, $data['last_page']);
        $this->assertEquals(1, $data['current_page']);
    }

    /** @test */
    public function show_retorna_factura_especifica_del_cliente()
    {
        // Arrange
        $cliente = Cliente::factory()->create(['nombre' => 'Juan Pérez']);
        $usuario = User::factory()->create(['name' => 'Vendedor Test']);
        $producto = Producto::factory()->create(['nombre' => 'Laptop']);

        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 1500.00
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 1500.00,
            'subtotal' => 1500.00
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas/{$factura->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Factura obtenida exitosamente'
                ]);

        $facturaData = $response->json('data');
        $this->assertEquals($factura->id, $facturaData['id']);
        $this->assertEquals('1500.00', $facturaData['total']);
        
        // Verificar relaciones cargadas
        $this->assertEquals('Juan Pérez', $facturaData['cliente']['nombre']);
        $this->assertEquals('Vendedor Test', $facturaData['user']['name']);
        $this->assertCount(1, $facturaData['detalles']);
        $this->assertEquals('Laptop', $facturaData['detalles'][0]['producto']['nombre']);
    }

    /** @test */
    public function show_retorna_404_para_factura_de_otro_cliente()
    {
        // Arrange
        $cliente1 = Cliente::factory()->create();
        $cliente2 = Cliente::factory()->create();
        $usuario = User::factory()->create();

        $factura = Factura::factory()->create([
            'cliente_id' => $cliente2->id, // Factura del cliente 2
            'user_id' => $usuario->id
        ]);

        $token = ClienteAccessToken::createToken($cliente1, 'Token Test')['plainTextToken']; // Token del cliente 1

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas/{$factura->id}");

        // Assert
        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Factura no encontrada'
                ]);
    }

    /** @test */
    public function show_retorna_404_para_factura_inexistente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];
        $facturaIdInexistente = 99999;

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas/{$facturaIdInexistente}");

        // Assert
        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Factura no encontrada'
                ]);
    }

    /** @test */
    public function index_requiere_autenticacion()
    {
        // Act - Sin token
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function show_requiere_autenticacion()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        // Act - Sin token
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson("/api/facturas/{$factura->id}");

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function index_maneja_token_invalido()
    {
        // Act - Token inválido
        $response = $this->withHeaders([
            'Authorization' => 'Bearer token_invalido',
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function index_incluye_metadatos_de_paginacion()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        Factura::factory()->count(8)->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas?per_page=3');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertArrayHasKey('current_page', $data);
        $this->assertArrayHasKey('last_page', $data);
        $this->assertArrayHasKey('per_page', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('from', $data);
        $this->assertArrayHasKey('to', $data);
        
        $this->assertEquals(3, $data['per_page']);
        $this->assertEquals(8, $data['total']);
        $this->assertEquals(3, $data['last_page']);
    }

    /** @test */
    public function show_incluye_todas_las_relaciones_necesarias()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        $producto1 = Producto::factory()->create();
        $producto2 = Producto::factory()->create();

        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto1->id
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto2->id
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson("/api/facturas/{$factura->id}");

        // Assert
        $response->assertStatus(200);
        $facturaData = $response->json('data');
        
        // Verificar que incluye cliente
        $this->assertArrayHasKey('cliente', $facturaData);
        $this->assertArrayHasKey('id', $facturaData['cliente']);
        
        // Verificar que incluye usuario
        $this->assertArrayHasKey('user', $facturaData);
        $this->assertArrayHasKey('id', $facturaData['user']);
        
        // Verificar que incluye detalles con productos
        $this->assertArrayHasKey('detalles', $facturaData);
        $this->assertCount(2, $facturaData['detalles']);
        
        foreach ($facturaData['detalles'] as $detalle) {
            $this->assertArrayHasKey('producto', $detalle);
            $this->assertArrayHasKey('id', $detalle['producto']);
        }
    }
}
