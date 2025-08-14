<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\ClienteAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class MultiModelAuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /** @test */
    public function middleware_autentica_cliente_con_token_valido()
    {
        // Arrange
        $cliente = Cliente::factory()->create(['nombre' => 'Cliente Test']);
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token válido');
        $token = $tokenData['plainTextToken'];

        // Act: Hacer petición con token válido
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert: Debe permitir el acceso
        $response->assertStatus(200);
    }

    /** @test */
    public function middleware_rechaza_token_invalido()
    {
        // Act: Petición con token inválido
        $response = $this->withHeaders([
            'Authorization' => 'Bearer token_completamente_falso',
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert: Debe rechazar
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Token no válido o no encontrado'
                ]);
    }

    /** @test */
    public function middleware_rechaza_peticion_sin_token()
    {
        // Act: Petición sin token
        $response = $this->getJson('/api/facturas');

        // Assert: Debe rechazar
        $response->assertStatus(401);
    }

    /** @test */
    public function middleware_actualiza_last_used_at_del_token()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token de prueba');
        $token = $tokenData['plainTextToken'];
        $tokenModel = $tokenData['token'];

        // Verificar que last_used_at es null inicialmente
        $this->assertNull($tokenModel->fresh()->last_used_at);

        // Act: Usar el token
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert: last_used_at debe estar actualizado
        $tokenActualizado = $tokenModel->fresh();
        $this->assertNotNull($tokenActualizado->last_used_at);
        $this->assertTrue($tokenActualizado->last_used_at->isToday());
    }

    /** @test */
    public function middleware_maneja_tokens_de_usuario_del_sistema()
    {
        // Arrange: Crear usuario del sistema con token Sanctum
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        // Act: Intentar acceder a endpoint de cliente
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert: Debe rechazar porque no es un cliente
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Acceso denegado. Solo los clientes pueden acceder a esta API.'
                ]);
    }

    /** @test */
    public function middleware_distingue_entre_tipos_de_tokens()
    {
        // Arrange: Crear cliente y usuario
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        // Crear tokens para ambos
        $tokenCliente = ClienteAccessToken::createToken($cliente, 'Token Cliente');
        $tokenUsuario = $usuario->createToken('Token Usuario');

        // Act & Assert: Token de cliente debe funcionar en API de clientes
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenCliente['plainTextToken'],
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');
        
        $response->assertStatus(200);

        // Token de usuario debe ser rechazado en API de clientes
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenUsuario->plainTextToken,
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');
        
        $response2->assertStatus(403);
    }

    /** @test */
    public function middleware_proporciona_informacion_de_debug_util()
    {
        // Act: Petición con token malformado
        $response = $this->withHeaders([
            'Authorization' => 'Bearer token_malformado',
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert: Debe incluir información de debug
        $response->assertStatus(401);
        $responseData = $response->json();
        
        $this->assertArrayHasKey('debug', $responseData);
        $this->assertArrayHasKey('token_recibido', $responseData['debug']);
        $this->assertEquals('token_malformado', $responseData['debug']['token_recibido']);
    }

    /** @test */
    public function middleware_maneja_token_con_formato_incorrecto()
    {
        // Act: Petición sin "Bearer " en el header
        $response = $this->withHeaders([
            'Authorization' => 'token_sin_bearer',
            'Accept' => 'application/json',
        ])->getJson('/api/facturas');

        // Assert: Debe rechazar
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Token no válido o no encontrado'
                ]);
    }

    /** @test */
    public function middleware_funciona_con_diferentes_endpoints()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token multifuncional');
        $token = $tokenData['plainTextToken'];

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];

        // Act & Assert: Probar diferentes endpoints
        $this->withHeaders($headers)->getJson('/api/facturas')->assertStatus(200);
        $this->withHeaders($headers)->getJson('/api/productos')->assertStatus(200);
        
        // Endpoint que no existe debe dar 404, no 401
        $this->withHeaders($headers)->getJson('/api/endpoint-inexistente')->assertStatus(404);
    }
}
