<?php

/**
 * Script de Pruebas Dinámicas para Laravel Telescope
 * 
 * Este script ejecuta operaciones completas del sistema de facturación
 * para generar eventos monitoreados por Laravel Telescope
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Pago;

class TelescopeDynamicTest extends TestCase
{
    public function test_complete_system_workflow()
    {
        echo "🚀 INICIANDO PRUEBAS DINÁMICAS CON TELESCOPE\n";
        echo "==========================================\n\n";

        // 1. Prueba de Autenticación
        echo "1. 🔐 Prueba de Autenticación\n";
        $admin = User::where('email', 'admin@factura.com')->first();
        $this->actingAs($admin);
        echo "   ✅ Administrador autenticado: {$admin->name}\n\n";

        // 2. Creación de Cliente
        echo "2. 👤 Creación de Cliente\n";
        $cliente = Cliente::create([
            'nombre' => 'Cliente Test Telescope',
            'email' => 'cliente.test@telescope.com',
            'telefono' => '+593-999-888-777',
            'direccion' => 'Av. Test Telescope #123',
            'identificacion' => '0987654321',
            'tipo_identificacion' => 'cedula'
        ]);
        echo "   ✅ Cliente creado con ID: {$cliente->id}\n\n";

        // 3. Creación de Productos
        echo "3. 📦 Creación de Productos\n";
        $producto1 = Producto::create([
            'codigo' => 'PROD-TELESCOPE-001',
            'nombre' => 'Producto Telescope 1',
            'descripcion' => 'Producto para pruebas de monitoreo',
            'precio' => 25.50,
            'stock' => 100,
            'categoria' => 'Pruebas'
        ]);

        $producto2 = Producto::create([
            'codigo' => 'PROD-TELESCOPE-002',
            'nombre' => 'Producto Telescope 2',
            'descripcion' => 'Segundo producto para pruebas',
            'precio' => 45.75,
            'stock' => 50,
            'categoria' => 'Pruebas'
        ]);

        echo "   ✅ Productos creados: {$producto1->id}, {$producto2->id}\n\n";

        // 4. Creación de Factura
        echo "4. 🧾 Creación de Factura\n";
        $factura = Factura::create([
            'cliente_id' => $cliente->id,
            'numero_factura' => 'FAC-TELESCOPE-' . date('YmdHis'),
            'fecha_emision' => now(),
            'subtotal' => 0,
            'impuesto' => 0,
            'total' => 0,
            'estado' => 'pendiente'
        ]);

        // Agregar detalles a la factura
        $detalle1 = FacturaDetalle::create([
            'factura_id' => $factura->id,
            'producto_id' => $producto1->id,
            'cantidad' => 2,
            'precio_unitario' => $producto1->precio,
            'subtotal' => 2 * $producto1->precio
        ]);

        $detalle2 = FacturaDetalle::create([
            'factura_id' => $factura->id,
            'producto_id' => $producto2->id,
            'cantidad' => 1,
            'precio_unitario' => $producto2->precio,
            'subtotal' => 1 * $producto2->precio
        ]);

        // Calcular totales
        $subtotal = $detalle1->subtotal + $detalle2->subtotal;
        $impuesto = $subtotal * 0.12; // IVA 12%
        $total = $subtotal + $impuesto;

        $factura->update([
            'subtotal' => $subtotal,
            'impuesto' => $impuesto,
            'total' => $total
        ]);

        echo "   ✅ Factura creada: {$factura->numero_factura}\n";
        echo "   💰 Total: $" . number_format($total, 2) . "\n\n";

        // 5. Procesamiento de Pago
        echo "5. 💳 Procesamiento de Pago\n";
        $pago = Pago::create([
            'factura_id' => $factura->id,
            'monto' => $total,
            'metodo_pago' => 'tarjeta_credito',
            'numero_transaccion' => 'TXN-TELESCOPE-' . uniqid(),
            'estado' => 'completado',
            'fecha_pago' => now()
        ]);

        $factura->update(['estado' => 'pagada']);

        echo "   ✅ Pago procesado: {$pago->numero_transaccion}\n";
        echo "   💰 Monto: $" . number_format($pago->monto, 2) . "\n\n";

        // 6. Consultas de Verificación
        echo "6. 🔍 Consultas de Verificación\n";
        
        // Consultar facturas del cliente
        $facturasCliente = Factura::where('cliente_id', $cliente->id)
            ->with(['detalles.producto', 'pagos'])
            ->get();
        
        echo "   ✅ Facturas del cliente consultadas: " . $facturasCliente->count() . "\n";

        // Consultar productos con bajo stock (simulado)
        $productosStock = Producto::where('stock', '<', 75)->get();
        echo "   ✅ Productos con stock bajo: " . $productosStock->count() . "\n";

        // Consultar pagos del día
        $pagosHoy = Pago::whereDate('fecha_pago', today())->get();
        echo "   ✅ Pagos del día consultados: " . $pagosHoy->count() . "\n\n";

        echo "🎉 PRUEBAS DINÁMICAS COMPLETADAS\n";
        echo "================================\n";
        echo "📊 Revise Laravel Telescope para ver todos los eventos generados:\n";
        echo "🔗 http://127.0.0.1:8000/telescope\n\n";

        // Estadísticas finales
        echo "📈 ESTADÍSTICAS GENERADAS:\n";
        echo "- Clientes creados: 1\n";
        echo "- Productos creados: 2\n";
        echo "- Facturas generadas: 1\n";
        echo "- Detalles de factura: 2\n";
        echo "- Pagos procesados: 1\n";
        echo "- Consultas SQL ejecutadas: ~15-20\n";
        echo "- Peticiones HTTP monitoreadas: Todas las de la sesión\n\n";

        return true;
    }
}

// Ejecutar las pruebas
$test = new TelescopeDynamicTest();
$test->setUp();
$test->test_complete_system_workflow();
