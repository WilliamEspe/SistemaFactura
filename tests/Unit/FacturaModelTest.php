<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class FacturaModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /** @test */
    public function puede_crear_factura_con_datos_validos()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        $datos = [
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 150.75,
            'anulada' => false
        ];

        $factura = Factura::create($datos);

        $this->assertInstanceOf(Factura::class, $factura);
        $this->assertEquals($cliente->id, $factura->cliente_id);
        $this->assertEquals($usuario->id, $factura->user_id);
        $this->assertEquals(150.75, $factura->total);
        $this->assertFalse($factura->anulada);
        $this->assertDatabaseHas('facturas', $datos);
    }

    /** @test */
    public function cliente_id_es_obligatorio()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        $usuario = User::factory()->create();
        
        Factura::create([
            'user_id' => $usuario->id,
            'total' => 100.00
        ]);
    }

    /** @test */
    public function user_id_es_obligatorio()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        $cliente = Cliente::factory()->create();
        
        Factura::create([
            'cliente_id' => $cliente->id,
            'total' => 100.00
        ]);
    }

    /** @test */
    public function tiene_relacion_con_cliente()
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Juan Pérez']);
        $usuario = User::factory()->create();
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        $this->assertInstanceOf(Cliente::class, $factura->cliente);
        $this->assertEquals('Juan Pérez', $factura->cliente->nombre);
        $this->assertEquals($cliente->id, $factura->cliente->id);
    }

    /** @test */
    public function tiene_relacion_con_usuario()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create(['name' => 'Vendedor Test']);
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        $this->assertInstanceOf(User::class, $factura->usuario);
        $this->assertEquals('Vendedor Test', $factura->usuario->name);
        $this->assertEquals($usuario->id, $factura->usuario->id);
    }

    /** @test */
    public function tiene_relacion_con_detalles()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        $producto1 = Producto::factory()->create();
        $producto2 = Producto::factory()->create();
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        $detalle1 = FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto1->id,
            'cantidad' => 2,
            'precio_unitario' => 50.00,
            'subtotal' => 100.00
        ]);

        $detalle2 = FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto2->id,
            'cantidad' => 1,
            'precio_unitario' => 25.00,
            'subtotal' => 25.00
        ]);

        $detalles = $factura->detalles;

        $this->assertInstanceOf(Collection::class, $detalles);
        $this->assertCount(2, $detalles);
        $this->assertTrue($detalles->contains($detalle1));
        $this->assertTrue($detalles->contains($detalle2));
    }

    /** @test */
    public function puede_calcular_total_desde_detalles()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        $producto1 = Producto::factory()->create();
        $producto2 = Producto::factory()->create();
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 0 // Lo calcularemos
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto1->id,
            'cantidad' => 3,
            'precio_unitario' => 25.00,
            'subtotal' => 75.00
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto2->id,
            'cantidad' => 2,
            'precio_unitario' => 50.00,
            'subtotal' => 100.00
        ]);

        $totalCalculado = $factura->detalles()->sum('subtotal');

        $this->assertEquals(175.00, $totalCalculado);

        // Actualizar la factura con el total calculado
        $factura->update(['total' => $totalCalculado]);
        $this->assertEquals(175.00, $factura->fresh()->total);
    }

    /** @test */
    public function puede_verificar_estado_anulada()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        $facturaActiva = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'anulada' => false
        ]);

        $facturaAnulada = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'anulada' => true
        ]);

        $this->assertFalse($facturaActiva->anulada);
        $this->assertTrue($facturaAnulada->anulada);
    }

    /** @test */
    public function puede_anular_factura()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'anulada' => false
        ]);

        $this->assertFalse($factura->anulada);

        $factura->update(['anulada' => true]);

        $this->assertTrue($factura->fresh()->anulada);
        $this->assertDatabaseHas('facturas', [
            'id' => $factura->id,
            'anulada' => true
        ]);
    }

    /** @test */
    public function puede_obtener_productos_de_la_factura()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        $producto1 = Producto::factory()->create(['nombre' => 'Producto A']);
        $producto2 = Producto::factory()->create(['nombre' => 'Producto B']);
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto1->id,
            'cantidad' => 1
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto2->id,
            'cantidad' => 2
        ]);

        $productos = $factura->detalles()
            ->with('producto')
            ->get()
            ->pluck('producto');

        $this->assertCount(2, $productos);
        $this->assertTrue($productos->contains('nombre', 'Producto A'));
        $this->assertTrue($productos->contains('nombre', 'Producto B'));
    }

    /** @test */
    public function fillable_incluye_campos_necesarios()
    {
        $factura = new Factura();
        $fillableFields = $factura->getFillable();

        $expectedFields = ['cliente_id', 'user_id', 'total', 'anulada'];

        foreach ($expectedFields as $field) {
            $this->assertContains($field, $fillableFields, "Campo '{$field}' debe estar en fillable");
        }
    }

    /** @test */
    public function puede_convertir_a_array_con_campos_correctos()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 199.99,
            'anulada' => false
        ]);

        $array = $factura->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('cliente_id', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('total', $array);
        $this->assertArrayHasKey('anulada', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertEquals($cliente->id, $array['cliente_id']);
        $this->assertEquals($usuario->id, $array['user_id']);
        $this->assertEquals(199.99, $array['total']);
        $this->assertEquals(0, $array['anulada']); // Boolean se convierte a 0/1
    }

    /** @test */
    public function puede_consultar_facturas_por_cliente()
    {
        $cliente1 = Cliente::factory()->create();
        $cliente2 = Cliente::factory()->create();
        $usuario = User::factory()->create();

        // Facturas del cliente 1
        Factura::factory()->count(3)->create([
            'cliente_id' => $cliente1->id,
            'user_id' => $usuario->id
        ]);

        // Facturas del cliente 2
        Factura::factory()->count(2)->create([
            'cliente_id' => $cliente2->id,
            'user_id' => $usuario->id
        ]);

        $facturasCliente1 = Factura::where('cliente_id', $cliente1->id)->get();
        $facturasCliente2 = Factura::where('cliente_id', $cliente2->id)->get();

        $this->assertCount(3, $facturasCliente1);
        $this->assertCount(2, $facturasCliente2);

        // Verificar que todas las facturas pertenecen al cliente correcto
        foreach ($facturasCliente1 as $factura) {
            $this->assertEquals($cliente1->id, $factura->cliente_id);
        }
    }

    /** @test */
    public function puede_consultar_facturas_activas_y_anuladas()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        // Facturas activas
        Factura::factory()->count(3)->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'anulada' => false
        ]);

        // Facturas anuladas
        Factura::factory()->count(2)->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'anulada' => true
        ]);

        $facturasActivas = Factura::where('anulada', false)->get();
        $facturasAnuladas = Factura::where('anulada', true)->get();

        $this->assertCount(3, $facturasActivas);
        $this->assertCount(2, $facturasAnuladas);

        foreach ($facturasActivas as $factura) {
            $this->assertFalse($factura->anulada);
        }

        foreach ($facturasAnuladas as $factura) {
            $this->assertTrue($factura->anulada);
        }
    }

    /** @test */
    public function timestamps_se_actualizan_automaticamente()
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        $createdAt = $factura->created_at;
        $updatedAt = $factura->updated_at;

        $this->assertNotNull($createdAt);
        $this->assertNotNull($updatedAt);

        sleep(1);

        $factura->update(['total' => 299.99]);
        $factura->refresh();

        $this->assertEquals($createdAt->format('Y-m-d H:i:s'), $factura->created_at->format('Y-m-d H:i:s'));
        $this->assertTrue($factura->updated_at->gt($updatedAt));
    }
}
