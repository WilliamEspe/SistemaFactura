<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\ClienteAccessToken;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ProductoControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /** @test */
    public function index_retorna_lista_de_productos_disponibles()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        
        $producto1 = Producto::factory()->create([
            'nombre' => 'Laptop Dell',
            'precio' => 1200.00,
            'stock' => 10,
            'descripcion' => 'Laptop para trabajo'
        ]);

        $producto2 = Producto::factory()->create([
            'nombre' => 'Mouse Logitech',
            'precio' => 25.00,
            'stock' => 50,
            'descripcion' => 'Mouse inalámbrico'
        ]);

        $producto3 = Producto::factory()->create([
            'nombre' => 'Teclado Mecánico',
            'precio' => 89.99,
            'stock' => 0, // Sin stock
            'descripcion' => 'Teclado gaming'
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Productos obtenidos exitosamente'
                ]);

        $productos = $response->json('data');
        $this->assertCount(3, $productos);

        // Verificar que incluye todos los productos (incluso sin stock)
        $nombresProductos = array_column($productos, 'nombre');
        $this->assertContains('Laptop Dell', $nombresProductos);
        $this->assertContains('Mouse Logitech', $nombresProductos);
        $this->assertContains('Teclado Mecánico', $nombresProductos);

        // Verificar estructura de respuesta
        foreach ($productos as $producto) {
            $this->assertArrayHasKey('id', $producto);
            $this->assertArrayHasKey('nombre', $producto);
            $this->assertArrayHasKey('precio', $producto);
            $this->assertArrayHasKey('stock', $producto);
            $this->assertArrayHasKey('descripcion', $producto);
        }
    }

    /** @test */
    public function index_muestra_precios_correctamente_formateados()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        
        $producto = Producto::factory()->create([
            'nombre' => 'Producto Test',
            'precio' => 123.456, // Precio con decimales
            'stock' => 5
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(200);
        $productos = $response->json('data');
        
        $productoResponse = collect($productos)->firstWhere('nombre', 'Producto Test');
        $this->assertNotNull($productoResponse);
        $this->assertEquals(123.456, $productoResponse['precio']);
    }

    /** @test */
    public function index_incluye_productos_con_stock_cero()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        
        $productoSinStock = Producto::factory()->create([
            'nombre' => 'Producto Agotado',
            'precio' => 99.99,
            'stock' => 0
        ]);

        $productoConStock = Producto::factory()->create([
            'nombre' => 'Producto Disponible',
            'precio' => 50.00,
            'stock' => 15
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(200);
        $productos = $response->json('data');
        $this->assertCount(2, $productos);

        $nombresProductos = array_column($productos, 'nombre');
        $this->assertContains('Producto Agotado', $nombresProductos);
        $this->assertContains('Producto Disponible', $nombresProductos);

        // Verificar stocks
        $productoAgotado = collect($productos)->firstWhere('nombre', 'Producto Agotado');
        $productoDisponible = collect($productos)->firstWhere('nombre', 'Producto Disponible');
        
        $this->assertEquals(0, $productoAgotado['stock']);
        $this->assertEquals(15, $productoDisponible['stock']);
    }

    /** @test */
    public function index_retorna_array_vacio_cuando_no_hay_productos()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Productos obtenidos exitosamente',
                    'data' => []
                ]);
    }

    /** @test */
    public function index_ordena_productos_por_nombre_alfabeticamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        
        // Crear productos en orden no alfabético
        Producto::factory()->create(['nombre' => 'Zebra Producto']);
        Producto::factory()->create(['nombre' => 'Alpha Producto']);
        Producto::factory()->create(['nombre' => 'Beta Producto']);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(200);
        $productos = $response->json('data');
        $nombresProductos = array_column($productos, 'nombre');

        // Verificar orden alfabético
        $this->assertEquals('Alpha Producto', $nombresProductos[0]);
        $this->assertEquals('Beta Producto', $nombresProductos[1]);
        $this->assertEquals('Zebra Producto', $nombresProductos[2]);
    }

    /** @test */
    public function index_requiere_autenticacion()
    {
        // Arrange
        Producto::factory()->create(['nombre' => 'Producto Test']);

        // Act - Sin token
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function index_maneja_token_invalido()
    {
        // Arrange
        Producto::factory()->create(['nombre' => 'Producto Test']);

        // Act - Token inválido
        $response = $this->withHeaders([
            'Authorization' => 'Bearer token_invalido',
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function index_todos_los_clientes_ven_los_mismos_productos()
    {
        // Arrange
        $cliente1 = Cliente::factory()->create();
        $cliente2 = Cliente::factory()->create();
        
        $producto1 = Producto::factory()->create(['nombre' => 'Producto Compartido 1']);
        $producto2 = Producto::factory()->create(['nombre' => 'Producto Compartido 2']);

        $token1 = ClienteAccessToken::createToken($cliente1, 'Token Cliente 1')['plainTextToken'];
        $token2 = ClienteAccessToken::createToken($cliente2, 'Token Cliente 2')['plainTextToken'];

        // Act - Cliente 1 consulta productos
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Act - Cliente 2 consulta productos
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert - Ambos ven los mismos productos
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $productos1 = $response1->json('data');
        $productos2 = $response2->json('data');

        $this->assertCount(2, $productos1);
        $this->assertCount(2, $productos2);

        $nombres1 = array_column($productos1, 'nombre');
        $nombres2 = array_column($productos2, 'nombre');

        $this->assertEquals($nombres1, $nombres2);
        $this->assertContains('Producto Compartido 1', $nombres1);
        $this->assertContains('Producto Compartido 2', $nombres1);
    }

    /** @test */
    public function index_incluye_timestamps_en_la_respuesta()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['nombre' => 'Producto con Timestamps']);
        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(200);
        $productos = $response->json('data');
        $producto = $productos[0];

        $this->assertArrayHasKey('created_at', $producto);
        $this->assertArrayHasKey('updated_at', $producto);
        $this->assertNotNull($producto['created_at']);
        $this->assertNotNull($producto['updated_at']);
    }

    /** @test */
    public function index_maneja_productos_con_descripcion_null()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        
        $producto = Producto::factory()->create([
            'nombre' => 'Producto Sin Descripción',
            'precio' => 100.00,
            'stock' => 5,
            'descripcion' => null
        ]);

        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(200);
        $productos = $response->json('data');
        $this->assertCount(1, $productos);

        $producto = $productos[0];
        $this->assertEquals('Producto Sin Descripción', $producto['nombre']);
        $this->assertNull($producto['descripcion']);
    }

    /** @test */
    public function index_retorna_estructura_json_consistente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        Producto::factory()->count(3)->create();
        $token = ClienteAccessToken::createToken($cliente, 'Token Test')['plainTextToken'];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/productos');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'nombre',
                            'precio',
                            'stock',
                            'descripcion',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Productos obtenidos exitosamente', $response->json('message'));
        $this->assertIsArray($response->json('data'));
    }
}
