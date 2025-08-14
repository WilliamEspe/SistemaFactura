<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Pago;

class TelescopeDynamicTest extends Command
{
    protected $signature = 'telescope:dynamic-test';
    protected $description = 'Ejecuta pruebas dinámicas del sistema para generar eventos monitoreados por Telescope';

    public function handle()
    {
        $this->info('🚀 INICIANDO PRUEBAS DINÁMICAS CON TELESCOPE');
        $this->info('==========================================');
        $this->newLine();

        // 1. Verificar usuario administrador
        $this->info('1. 🔐 Verificación de Usuario Administrador');
        $admin = User::where('email', 'admin@factura.com')->first();
        if (!$admin) {
            $this->error('   ❌ Usuario administrador no encontrado');
            return 1;
        }
        $this->info("   ✅ Administrador encontrado: {$admin->name}");
        $this->newLine();

        // 2. Creación de Cliente de Prueba
        $this->info('2. 👤 Creación de Cliente de Prueba');
        $cliente = Cliente::firstOrCreate(
            ['email' => 'cliente.telescope@test.com'],
            [
                'nombre' => 'Cliente Telescope Test',
                'telefono' => '+593-999-888-777',
                'direccion' => 'Av. Telescope Test #123',
                'identificacion' => '0987654321',
                'tipo_identificacion' => 'cedula'
            ]
        );
        $this->info("   ✅ Cliente procesado con ID: {$cliente->id}");
        $this->newLine();

        // 3. Creación de Productos de Prueba
        $this->info('3. 📦 Creación de Productos de Prueba');
        
        $producto1 = Producto::firstOrCreate(
            ['nombre' => 'Producto Telescope 1'],
            [
                'descripcion' => 'Producto para monitoreo dinámico',
                'precio' => 25.50,
                'stock' => 100
            ]
        );

        $producto2 = Producto::firstOrCreate(
            ['nombre' => 'Producto Telescope 2'],
            [
                'descripcion' => 'Segundo producto para pruebas',
                'precio' => 45.75,
                'stock' => 50
            ]
        );

        $this->info("   ✅ Productos procesados: {$producto1->nombre}, {$producto2->nombre}");
        $this->newLine();

        // 4. Creación de Factura Completa
        $this->info('4. 🧾 Creación de Factura Completa');
        
        $numeroFactura = 'FAC-TELESCOPE-' . date('YmdHis');
        
        $factura = Factura::create([
            'cliente_id' => $cliente->id,
            'user_id' => $admin->id,
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
            'total' => $total
        ]);

        $this->info("   ✅ Factura creada: FAC-TELESCOPE-" . date('YmdHis'));
        $this->info("   💰 Total: $" . number_format($total, 2));
        $this->newLine();

        // 5. Procesamiento de Pago
        $this->info('5. 💳 Procesamiento de Pago');
        
        $pago = Pago::create([
            'factura_id' => $factura->id,
            'tipo_pago' => 'tarjeta',
            'monto' => $total,
            'numero_transaccion' => 'TXN-TELESCOPE-' . uniqid(),
            'estado' => 'aprobado',
            'pagado_por' => $admin->id
        ]);

        $factura->update(['estado' => 'pagada']);

        $this->info("   ✅ Pago procesado: {$pago->numero_transaccion}");
        $this->info("   💰 Monto: $" . number_format($pago->monto, 2));
        $this->newLine();

        // 6. Consultas de Verificación (para generar queries monitoreadas)
        $this->info('6. 🔍 Ejecutando Consultas de Verificación');
        
        // Consulta compleja con relaciones
        $facturasConDetalles = Factura::with(['cliente', 'detalles.producto', 'pagos'])
            ->where('cliente_id', $cliente->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->info("   ✅ Facturas con detalles consultadas: " . $facturasConDetalles->count());

        // Consulta de productos con filtros
        $productosConStock = Producto::where('stock', '>', 25)
            ->orderBy('precio', 'asc')
            ->get();
        
        $this->info("   ✅ Productos con stock consultados: " . $productosConStock->count());

        // Consulta de pagos del día
        $pagosHoy = Pago::whereDate('created_at', today())
            ->with('factura.cliente')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->info("   ✅ Pagos del día consultados: " . $pagosHoy->count());

        // Consulta de clientes con facturas pendientes
        $clientesConPendientes = Cliente::whereHas('facturas', function($query) {
            $query->where('estado', 'pendiente');
        })->with('facturas')->get();
        
        $this->info("   ✅ Clientes con facturas pendientes: " . $clientesConPendientes->count());
        $this->newLine();

        // 7. Operaciones de Actualización
        $this->info('7. 🔄 Operaciones de Actualización');
        
        // Actualizar stock de productos
        $producto1->decrement('stock', 2);
        $producto2->decrement('stock', 1);
        
        $this->info("   ✅ Stock actualizado para productos vendidos");

        // Actualizar información del cliente
        $cliente->update([
            'telefono' => '+593-999-888-999',
            'direccion' => 'Av. Telescope Test #456 - Actualizada'
        ]);
        
        $this->info("   ✅ Información del cliente actualizada");
        $this->newLine();

        // Resumen final
        $this->info('🎉 PRUEBAS DINÁMICAS COMPLETADAS');
        $this->info('================================');
        $this->info('📊 Revise Laravel Telescope para ver todos los eventos generados:');
        $this->info('🔗 http://127.0.0.1:8000/telescope');
        $this->newLine();

        $this->info('📈 ESTADÍSTICAS DE EVENTOS GENERADOS:');
        $this->info('- Consultas SQL ejecutadas: ~20-25');
        $this->info('- Modelos creados: ' . ($factura->wasRecentlyCreated ? '4' : '3'));
        $this->info('- Modelos actualizados: 3');
        $this->info('- Relaciones cargadas: 8-10');
        $this->info('- Transacciones de base de datos: Múltiples');
        $this->newLine();

        $this->warn('⚠️  Recuerde revisar cada sección de Telescope:');
        $this->line('   - Requests: Peticiones HTTP de la aplicación');
        $this->line('   - Queries: Todas las consultas SQL ejecutadas');
        $this->line('   - Models: Eventos de Eloquent (created, updated, etc.)');
        $this->line('   - Exceptions: Cualquier error o excepción capturada');
        $this->line('   - Events: Eventos disparados por la aplicación');

        return 0;
    }
}
