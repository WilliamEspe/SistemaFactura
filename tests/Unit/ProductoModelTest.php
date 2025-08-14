<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Producto;
use App\Models\FacturaDetalle;
use App\Models\Factura;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class ProductoModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /** @test */
    public function puede_crear_producto_con_datos_validos()
    {
        $datos = [
            'nombre' => 'Laptop Gaming',
            'precio' => 1500.99,
            'stock' => 10,
            'descripcion' => 'Laptop para gaming de alta gama'
        ];

        $producto = Producto::create($datos);

        $this->assertInstanceOf(Producto::class, $producto);
        $this->assertEquals('Laptop Gaming', $producto->nombre);
        $this->assertEquals(1500.99, $producto->precio);
        $this->assertEquals(10, $producto->stock);
        $this->assertEquals('Laptop para gaming de alta gama', $producto->descripcion);
        $this->assertDatabaseHas('productos', $datos);
    }

    /** @test */
    public function nombre_es_obligatorio()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Producto::create([
            'precio' => 100.00,
            'stock' => 5
        ]);
    }

    /** @test */
    public function precio_debe_ser_numerico()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Producto::factory()->create(['precio' => 'no-numerico']);
    }

    /** @test */
    public function tiene_relacion_con_factura_detalles()
    {
        $producto = Producto::factory()->create();
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        
        $factura = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id
        ]);

        $detalle1 = FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 50.00
        ]);

        $detalle2 = FacturaDetalle::factory()->create([
            'factura_id' => $factura->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 50.00
        ]);

        $detalles = $producto->detalles;

        $this->assertInstanceOf(Collection::class, $detalles);
        $this->assertCount(2, $detalles);
        $this->assertTrue($detalles->contains($detalle1));
        $this->assertTrue($detalles->contains($detalle2));
    }

    /** @test */
    public function puede_obtener_facturas_relacionadas_a_traves_de_detalles()
    {
        $producto = Producto::factory()->create(['nombre' => 'Producto Test']);
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();

        // Crear múltiples facturas que incluyen este producto
        $factura1 = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 100.00
        ]);

        $factura2 = Factura::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $usuario->id,
            'total' => 200.00
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura1->id,
            'producto_id' => $producto->id,
            'cantidad' => 2
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura2->id,
            'producto_id' => $producto->id,
            'cantidad' => 1
        ]);

        // Obtener facturas a través de la relación
        $facturas = $producto->detalles()
            ->with('factura')
            ->get()
            ->pluck('factura')
            ->unique('id');

        $this->assertCount(2, $facturas);
        $this->assertTrue($facturas->contains('id', $factura1->id));
        $this->assertTrue($facturas->contains('id', $factura2->id));
    }

    /** @test */
    public function puede_calcular_cantidad_total_vendida()
    {
        $producto = Producto::factory()->create(['stock' => 100]);
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

        // Crear ventas en diferentes facturas
        FacturaDetalle::factory()->create([
            'factura_id' => $factura1->id,
            'producto_id' => $producto->id,
            'cantidad' => 5
        ]);

        FacturaDetalle::factory()->create([
            'factura_id' => $factura2->id,
            'producto_id' => $producto->id,
            'cantidad' => 3
        ]);

        $cantidadVendida = $producto->detalles()->sum('cantidad');

        $this->assertEquals(8, $cantidadVendida);
    }

    /** @test */
    public function puede_verificar_disponibilidad_de_stock()
    {
        $producto = Producto::factory()->create(['stock' => 10]);

        $this->assertTrue($producto->stock > 0);
        $this->assertTrue($producto->stock >= 5); // Suficiente para venta de 5
        $this->assertFalse($producto->stock >= 15); // No suficiente para venta de 15
    }

    /** @test */
    public function puede_actualizar_informacion_del_producto()
    {
        $producto = Producto::factory()->create([
            'nombre' => 'Producto Original',
            'precio' => 100.00,
            'stock' => 20,
            'descripcion' => 'Descripción original'
        ]);

        $datosActualizados = [
            'nombre' => 'Producto Actualizado',
            'precio' => 150.00,
            'stock' => 25,
            'descripcion' => 'Descripción actualizada'
        ];

        $producto->update($datosActualizados);

        $this->assertEquals('Producto Actualizado', $producto->fresh()->nombre);
        $this->assertEquals(150.00, $producto->fresh()->precio);
        $this->assertEquals(25, $producto->fresh()->stock);
        $this->assertEquals('Descripción actualizada', $producto->fresh()->descripcion);

        $this->assertDatabaseHas('productos', array_merge(['id' => $producto->id], $datosActualizados));
    }

    /** @test */
    public function fillable_incluye_campos_necesarios()
    {
        $producto = new Producto();
        $fillableFields = $producto->getFillable();

        $expectedFields = ['nombre', 'precio', 'stock', 'descripcion'];

        foreach ($expectedFields as $field) {
            $this->assertContains($field, $fillableFields, "Campo '{$field}' debe estar en fillable");
        }
    }

    /** @test */
    public function puede_convertir_a_array_con_campos_correctos()
    {
        $producto = Producto::factory()->create([
            'nombre' => 'Test Producto',
            'precio' => 99.99,
            'stock' => 15,
            'descripcion' => 'Producto de prueba'
        ]);

        $array = $producto->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('nombre', $array);
        $this->assertArrayHasKey('precio', $array);
        $this->assertArrayHasKey('stock', $array);
        $this->assertArrayHasKey('descripcion', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertEquals('Test Producto', $array['nombre']);
        $this->assertEquals(99.99, $array['precio']);
        $this->assertEquals(15, $array['stock']);
    }

    /** @test */
    public function puede_eliminar_producto_sin_dependencias()
    {
        $producto = Producto::factory()->create();
        $productoId = $producto->id;

        $this->assertDatabaseHas('productos', ['id' => $productoId]);

        $producto->delete();

        $this->assertSoftDeleted('productos', ['id' => $productoId]);
    }

    /** @test */
    public function timestamps_se_actualizan_automaticamente()
    {
        $producto = Producto::factory()->create();
        $createdAt = $producto->created_at;
        $updatedAt = $producto->updated_at;

        $this->assertNotNull($createdAt);
        $this->assertNotNull($updatedAt);
        $this->assertEquals($createdAt->format('Y-m-d H:i:s'), $updatedAt->format('Y-m-d H:i:s'));

        sleep(1);

        $producto->update(['nombre' => 'Nombre Actualizado']);
        $producto->refresh();

        $this->assertEquals($createdAt->format('Y-m-d H:i:s'), $producto->created_at->format('Y-m-d H:i:s'));
        $this->assertTrue($producto->updated_at->gt($updatedAt));
    }
}
