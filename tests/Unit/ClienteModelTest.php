<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\ClienteAccessToken;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClienteModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /** @test */
    public function cliente_puede_tener_facturas()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        
        $factura1 = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);
        $factura2 = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        // Act
        $facturas = $cliente->facturas;

        // Assert
        $this->assertCount(2, $facturas);
        $this->assertTrue($facturas->contains($factura1));
        $this->assertTrue($facturas->contains($factura2));
    }

    /** @test */
    public function cliente_puede_tener_tokens_de_acceso()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        
        // Act
        $token1 = ClienteAccessToken::createToken($cliente, 'Token 1');
        $token2 = ClienteAccessToken::createToken($cliente, 'Token 2');

        // Assert
        $this->assertCount(2, $cliente->accessTokens);
        $this->assertEquals('Token 1', $cliente->accessTokens->first()->name);
    }

    /** @test */
    public function metodo_createToken_del_cliente_funciona()
    {
        // Arrange
        $cliente = Cliente::factory()->create();

        // Act
        $tokenData = $cliente->createToken('Mi Token Personal');

        // Assert
        $this->assertIsArray($tokenData);
        $this->assertArrayHasKey('token', $tokenData);
        $this->assertArrayHasKey('plainTextToken', $tokenData);
        $this->assertEquals('Mi Token Personal', $tokenData['token']->name);
        $this->assertEquals($cliente->id, $tokenData['token']->cliente_id);
    }

    /** @test */
    public function cliente_soft_delete_funciona()
    {
        // Arrange
        $cliente = Cliente::factory()->create(['nombre' => 'Cliente a eliminar']);

        // Act
        $cliente->delete();

        // Assert
        $this->assertSoftDeleted('clientes', ['id' => $cliente->id]);
        
        // Verificar que no aparece en consultas normales
        $this->assertNull(Cliente::find($cliente->id));
        
        // Pero sí en consultas con trashed
        $this->assertNotNull(Cliente::withTrashed()->find($cliente->id));
    }

    /** @test */
    public function campos_fillable_del_cliente()
    {
        // Arrange
        $datos = [
            'nombre' => 'Juan Pérez',
            'email' => 'juan@test.com',
            'telefono' => '1234567890',
            'direccion' => 'Calle 123'
        ];

        // Act
        $cliente = Cliente::create($datos);

        // Assert
        $this->assertEquals('Juan Pérez', $cliente->nombre);
        $this->assertEquals('juan@test.com', $cliente->email);
        $this->assertEquals('1234567890', $cliente->telefono);
        $this->assertEquals('Calle 123', $cliente->direccion);
    }

    /** @test */
    public function relacion_cliente_tokens_es_bidireccional()
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token bidireccional');
        $token = $tokenData['token'];

        // Act & Assert: Cliente -> Token
        $this->assertTrue($cliente->accessTokens->contains($token));
        
        // Act & Assert: Token -> Cliente
        $this->assertEquals($cliente->id, $token->cliente->id);
        $this->assertEquals($cliente->nombre, $token->cliente->nombre);
    }

    /** @test */
    public function cliente_notificaciones_funcionan()
    {
        // Arrange
        $cliente = Cliente::factory()->create([
            'email' => 'notificacion@test.com'
        ]);

        // Act & Assert: Verificar que el trait Notifiable está funcionando
        $this->assertTrue(method_exists($cliente, 'notify'));
        $this->assertTrue(method_exists($cliente, 'notifications'));
    }

    /** @test */
    public function timestamps_se_manejan_automaticamente()
    {
        // Arrange & Act
        $cliente = Cliente::factory()->create();

        // Assert
        $this->assertNotNull($cliente->created_at);
        $this->assertNotNull($cliente->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $cliente->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $cliente->updated_at);
    }
}
