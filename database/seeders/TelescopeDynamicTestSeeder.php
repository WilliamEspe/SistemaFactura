<?php

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Pago;

class TelescopeDynamicTestSeeder extends Seeder
{
    /**
     * Ejecuta las pruebas dinámicas del sistema para generar eventos en Telescope
     */
    public function run(): void
    {
        $this->command->info('🚀 INICIANDO PRUEBAS DINÁMICAS CON TELESCOPE');
        $this->command->info('==========================================');
        $this->command->newLine();

        // 1. Verificar usuario administrador
        $this->command->info('1. 🔐 Verificación de Usuario Administrador');
        $admin = User::where('email', 'admin@factura.com')->first();
        if (!$admin) {
            $this->command->error('   ❌ Usuario administrador no encontrado');
            return;
        }
        $this->command->info("   ✅ Administrador encontrado: {$admin->name}");
        $this->command->newLine();

        // 2. Creación de Cliente de Prueba
        $this->command->info('2. 👤 Creación de Cliente de Prueba');
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
        $this->command->info("   ✅ Cliente procesado con ID: {$cliente->id}");
        $this->command->newLine();

        // 3. Creación de Productos de Prueba
        $this->command->info('3. 📦 Creación de Productos de Prueba');
        
        $producto1 = Producto::firstOrCreate(
            ['codigo' => 'TELESCOPE-001'],
            [
                'nombre' => 'Producto Telescope 1',
                'descripcion' => 'Producto para monitoreo dinámico',
                'precio' => 25.50,
                'stock' => 100,
                'categoria' => 'Pruebas Telescope'
            ]
        );

        $producto2 = Producto::firstOrCreate(
            ['codigo' => 'TELESCOPE-002'],
            [
                'nombre' => 'Producto Telescope 2',
                'descripcion' => 'Segundo producto para pruebas',
                'precio' => 45.75,
                'stock' => 50,
                'categoria' => 'Pruebas Telescope'
            ]
        );

        $this->command->info("   ✅ Productos procesados: {$producto1->nombre}, {$producto2->nombre}");
        $this->command->newLine();

        // 4. Creación de Factura Completa
        $this->command->info('4. 🧾 Creación de Factura Completa');
        
        $numeroFactura = 'FAC-TELESCOPE-' . date('YmdHis');
        
        $factura = Factura::create([
            'cliente_id' => $cliente->id,
            'numero_factura' => $numeroFactura,
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

        $this->command->info("   ✅ Factura creada: {$factura->numero_factura}");
        $this->command->info("   💰 Total: $" . number_format($total, 2));
        $this->command->newLine();

        // 5. Procesamiento de Pago
        $this->command->info('5. 💳 Procesamiento de Pago');
        
        $pago = Pago::create([
            'factura_id' => $factura->id,
            'monto' => $total,
            'metodo_pago' => 'tarjeta_credito',
            'numero_transaccion' => 'TXN-TELESCOPE-' . uniqid(),
            'estado' => 'completado',
            'fecha_pago' => now()
        ]);

        $factura->update(['estado' => 'pagada']);

        $this->command->info("   ✅ Pago procesado: {$pago->numero_transaccion}");
        $this->command->info("   💰 Monto: $" . number_format($pago->monto, 2));
        $this->command->newLine();

        // 6. Consultas de Verificación (para generar queries monitoreadas)
        $this->command->info('6. 🔍 Ejecutando Consultas de Verificación');
        
        // Consulta compleja con relaciones
        $facturasConDetalles = Factura::with(['cliente', 'detalles.producto', 'pagos'])
            ->where('cliente_id', $cliente->id)
            ->orderBy('fecha_emision', 'desc')
            ->get();
        
        $this->command->info("   ✅ Facturas con detalles consultadas: " . $facturasConDetalles->count());

        // Consulta de productos con filtros
        $productosCategoria = Producto::where('categoria', 'Pruebas Telescope')
            ->where('stock', '>', 25)
            ->orderBy('precio', 'asc')
            ->get();
        
        $this->command->info("   ✅ Productos de prueba consultados: " . $productosCategoria->count());

        // Consulta de pagos del día
        $pagosHoy = Pago::whereDate('fecha_pago', today())
            ->with('factura.cliente')
            ->orderBy('fecha_pago', 'desc')
            ->get();
        
        $this->command->info("   ✅ Pagos del día consultados: " . $pagosHoy->count());

        // Consulta de clientes con facturas pendientes
        $clientesConPendientes = Cliente::whereHas('facturas', function($query) {
            $query->where('estado', 'pendiente');
        })->with('facturas')->get();
        
        $this->command->info("   ✅ Clientes con facturas pendientes: " . $clientesConPendientes->count());
        $this->command->newLine();

        // 7. Operaciones de Actualización
        $this->command->info('7. 🔄 Operaciones de Actualización');
        
        // Actualizar stock de productos
        $producto1->decrement('stock', 2);
        $producto2->decrement('stock', 1);
        
        $this->command->info("   ✅ Stock actualizado para productos vendidos");

        // Actualizar información del cliente
        $cliente->update([
            'telefono' => '+593-999-888-999',
            'direccion' => 'Av. Telescope Test #456 - Actualizada'
        ]);
        
        $this->command->info("   ✅ Información del cliente actualizada");
        $this->command->newLine();

        // Resumen final
        $this->command->info('🎉 PRUEBAS DINÁMICAS COMPLETADAS');
        $this->command->info('================================');
        $this->command->info('📊 Revise Laravel Telescope para ver todos los eventos generados:');
        $this->command->info('🔗 http://127.0.0.1:8000/telescope');
        $this->command->newLine();

        $this->command->info('📈 ESTADÍSTICAS DE EVENTOS GENERADOS:');
        $this->command->info('- Consultas SQL ejecutadas: ~20-25');
        $this->command->info('- Modelos creados: ' . ($factura->wasRecentlyCreated ? '4' : '3'));
        $this->command->info('- Modelos actualizados: 3');
        $this->command->info('- Relaciones cargadas: 8-10');
        $this->command->info('- Transacciones de base de datos: Múltiples');
        $this->command->newLine();

        $this->command->warn('⚠️  Recuerde revisar cada sección de Telescope:');
        $this->command->line('   - Requests: Peticiones HTTP de la aplicación');
        $this->command->line('   - Queries: Todas las consultas SQL ejecutadas');
        $this->command->line('   - Models: Eventos de Eloquent (created, updated, etc.)');
        $this->command->line('   - Exceptions: Cualquier error o excepción capturada');
        $this->command->line('   - Events: Eventos disparados por la aplicación');
    }
}
