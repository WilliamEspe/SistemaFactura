<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\ClienteAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ClienteAccessTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Las migraciones se ejecutan automáticamente por RefreshDatabase
    }

    /** @test */
    public function createToken_genera_token_correctamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();

        // Act
        $resultado = ClienteAccessToken::createToken($cliente, 'Token de prueba');

        // Assert
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('token', $resultado);
        $this->assertArrayHasKey('plainTextToken', $resultado);
        
        $token = $resultado['token'];
        $this->assertInstanceOf(ClienteAccessToken::class, $token);
        $this->assertEquals($cliente->id, $token->cliente_id);
        $this->assertEquals('Token de prueba', $token->name);
        $this->assertNotEmpty($resultado['plainTextToken']);
    }

    /** @test */
    public function token_se_hashea_correctamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();

        // Act
        $resultado = ClienteAccessToken::createToken($cliente, 'Token hash test');
        $plainTextToken = $resultado['plainTextToken'];
        $hashedToken = $resultado['token']->token;

        // Assert
        $expectedHash = hash('sha256', $plainTextToken);
        $this->assertEquals($expectedHash, $hashedToken);
        $this->assertNotEquals($plainTextToken, $hashedToken);
    }

    /** @test */
    public function findToken_encuentra_token_por_plain_text()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $resultado = ClienteAccessToken::createToken($cliente, 'Token búsqueda');
        $plainTextToken = $resultado['plainTextToken'];

        // Act
        $tokenEncontrado = ClienteAccessToken::findToken($plainTextToken);

        // Assert
        $this->assertNotNull($tokenEncontrado);
        $this->assertEquals($resultado['token']->id, $tokenEncontrado->id);
        $this->assertEquals($cliente->id, $tokenEncontrado->cliente_id);
    }

    /** @test */
    public function findToken_actualiza_last_used_at()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $resultado = ClienteAccessToken::createToken($cliente, 'Token con timestamp');
        $token = $resultado['token'];
        $plainTextToken = $resultado['plainTextToken'];

        // Verificar que last_used_at es null inicialmente
        $this->assertNull($token->last_used_at);

        // Act
        $tokenEncontrado = ClienteAccessToken::findToken($plainTextToken);

        // Assert
        $this->assertNotNull($tokenEncontrado->last_used_at);
        $this->assertInstanceOf(Carbon::class, $tokenEncontrado->last_used_at);
        $this->assertTrue($tokenEncontrado->last_used_at->isToday());
    }

    /** @test */
    public function findToken_retorna_null_para_token_inexistente()
    {
        // Act
        $tokenEncontrado = ClienteAccessToken::findToken('token_que_no_existe');

        // Assert
        $this->assertNull($tokenEncontrado);
    }

    /** @test */
    public function habilidades_por_defecto_son_todas()
    {
        // Arrange
        $cliente = Cliente::factory()->create();

        // Act
        $resultado = ClienteAccessToken::createToken($cliente, 'Token habilidades');
        $token = $resultado['token'];

        // Assert
        $this->assertEquals(['*'], $token->abilities);
    }

    /** @test */
    public function puede_crear_token_con_habilidades_personalizadas()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $habilidades = ['ver-facturas', 'crear-facturas'];

        // Act
        $resultado = ClienteAccessToken::createToken($cliente, 'Token limitado', $habilidades);
        $token = $resultado['token'];

        // Assert
        $this->assertEquals($habilidades, $token->abilities);
    }

    /** @test */
    public function relacion_con_cliente_funciona()
    {
        // Arrange
        $cliente = Cliente::factory()->create(['nombre' => 'Cliente Relación']);
        $resultado = ClienteAccessToken::createToken($cliente, 'Token relación');
        $token = $resultado['token'];

        // Act
        $clienteDelToken = $token->cliente;

        // Assert
        $this->assertInstanceOf(Cliente::class, $clienteDelToken);
        $this->assertEquals($cliente->id, $clienteDelToken->id);
        $this->assertEquals('Cliente Relación', $clienteDelToken->nombre);
    }

    /** @test */
    public function plain_text_token_se_guarda_para_debug()
    {
        // Arrange
        $cliente = Cliente::factory()->create();

        // Act
        $resultado = ClienteAccessToken::createToken($cliente, 'Token debug');
        $token = $resultado['token'];
        $plainTextToken = $resultado['plainTextToken'];

        // Assert
        $this->assertEquals($plainTextToken, $token->plain_text_token);
        $this->assertNotEmpty($token->plain_text_token);
    }

    /** @test */
    public function token_generado_es_unico()
    {
        // Arrange
        $cliente = Cliente::factory()->create();

        // Act
        $token1 = ClienteAccessToken::createToken($cliente, 'Token 1');
        $token2 = ClienteAccessToken::createToken($cliente, 'Token 2');
        $token3 = ClienteAccessToken::createToken($cliente, 'Token 3');

        // Assert
        $plainTextTokens = [
            $token1['plainTextToken'],
            $token2['plainTextToken'],
            $token3['plainTextToken']
        ];

        $this->assertEquals(3, count(array_unique($plainTextTokens)));
        $this->assertNotEquals($token1['plainTextToken'], $token2['plainTextToken']);
        $this->assertNotEquals($token2['plainTextToken'], $token3['plainTextToken']);
        $this->assertNotEquals($token1['plainTextToken'], $token3['plainTextToken']);
    }

    /** @test */
    public function timestamps_se_establecen_automaticamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();

        // Act
        $resultado = ClienteAccessToken::createToken($cliente, 'Token timestamps');
        $token = $resultado['token'];

        // Assert
        $this->assertNotNull($token->created_at);
        $this->assertNotNull($token->updated_at);
        $this->assertInstanceOf(Carbon::class, $token->created_at);
        $this->assertInstanceOf(Carbon::class, $token->updated_at);
    }

    /** @test */
    public function fillable_campos_funcionan_correctamente()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $datos = [
            'cliente_id' => $cliente->id,
            'name' => 'Token manual',
            'token' => 'hash_manual_123',
            'plain_text_token' => 'token_manual_456',
            'abilities' => ['accion-1', 'accion-2'],
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30)
        ];

        // Act
        $token = ClienteAccessToken::create($datos);

        // Assert
        $this->assertEquals($cliente->id, $token->cliente_id);
        $this->assertEquals('Token manual', $token->name);
        $this->assertEquals('hash_manual_123', $token->token);
        $this->assertEquals('token_manual_456', $token->plain_text_token);
        $this->assertEquals(['accion-1', 'accion-2'], $token->abilities);
        $this->assertNotNull($token->last_used_at);
        $this->assertNotNull($token->expires_at);
    }
}
