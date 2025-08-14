<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\ClienteAccessToken;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ClienteTokenSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecutar migraciones en memoria
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /** @test */
    public function puede_crear_token_para_cliente()
    {
        // Arrange: Crear un cliente
        $cliente = Cliente::factory()->create([
            'nombre' => 'Cliente Test',
            'email' => 'cliente@test.com'
        ]);

        // Act: Crear token usando el nuevo sistema
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token de prueba');

        // Assert: Verificar que el token se creó correctamente
        $this->assertIsArray($tokenData);
        $this->assertArrayHasKey('token', $tokenData);
        $this->assertArrayHasKey('plainTextToken', $tokenData);
        
        // Verificar que se guardó en la base de datos
        $this->assertDatabaseHas('cliente_access_tokens', [
            'cliente_id' => $cliente->id,
            'name' => 'Token de prueba'
        ]);

        // Verificar que el token se puede recuperar
        $tokenEncontrado = ClienteAccessToken::findToken($tokenData['plainTextToken']);
        $this->assertNotNull($tokenEncontrado);
        $this->assertEquals($cliente->id, $tokenEncontrado->cliente_id);
    }

    /** @test */
    public function cliente_puede_tener_multiples_tokens()
    {
        // Arrange
        $cliente = Cliente::factory()->create();

        // Act: Crear múltiples tokens
        $token1 = ClienteAccessToken::createToken($cliente, 'Token 1');
        $token2 = ClienteAccessToken::createToken($cliente, 'Token 2');
        $token3 = ClienteAccessToken::createToken($cliente, 'Token 3');

        // Assert
        $this->assertEquals(3, $cliente->accessTokens()->count());
        
        // Verificar que cada token es único
        $this->assertNotEquals($token1['plainTextToken'], $token2['plainTextToken']);
        $this->assertNotEquals($token2['plainTextToken'], $token3['plainTextToken']);
        $this->assertNotEquals($token1['plainTextToken'], $token3['plainTextToken']);
    }

    /** @test */
    public function token_invalido_no_se_encuentra()
    {
        // Arrange: Token falso
        $tokenFalso = 'token_inexistente_12345';

        // Act & Assert
        $tokenEncontrado = ClienteAccessToken::findToken($tokenFalso);
        $this->assertNull($tokenEncontrado);
    }

    /** @test */
    public function puede_eliminar_token_de_cliente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token temporal');
        $token = $tokenData['token'];

        // Act: Eliminar token
        $token->delete();

        // Assert
        $this->assertDatabaseMissing('cliente_access_tokens', [
            'id' => $token->id
        ]);

        // Verificar que no se puede encontrar
        $tokenEncontrado = ClienteAccessToken::findToken($tokenData['plainTextToken']);
        $this->assertNull($tokenEncontrado);
    }

    /** @test */
    public function relacion_cliente_tokens_funciona_correctamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        ClienteAccessToken::createToken($cliente, 'Token 1');
        ClienteAccessToken::createToken($cliente, 'Token 2');

        // Act: Cargar cliente con tokens
        $clienteConTokens = Cliente::with('accessTokens')->find($cliente->id);

        // Assert
        $this->assertCount(2, $clienteConTokens->accessTokens);
        $this->assertEquals('Token 1', $clienteConTokens->accessTokens->first()->name);
    }

    /** @test */
    public function token_actualiza_last_used_at_cuando_se_encuentra()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token de uso');
        
        // Verificar que last_used_at es null inicialmente
        $token = $tokenData['token'];
        $this->assertNull($token->last_used_at);

        // Act: Buscar el token (simula uso)
        $tokenEncontrado = ClienteAccessToken::findToken($tokenData['plainTextToken']);

        // Assert: last_used_at debe estar actualizado
        $tokenActualizado = $tokenEncontrado->fresh();
        $this->assertNotNull($tokenActualizado->last_used_at);
    }
}
