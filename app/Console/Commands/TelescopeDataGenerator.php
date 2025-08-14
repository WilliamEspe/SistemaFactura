<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Pago;
use App\Models\User;

class TelescopeDataGenerator extends Command
{
    protected $signature = 'telescope:generate-data';
    protected $description = 'Genera datos adicionales para enriquecer la información de Telescope';

    public function handle()
    {
        $this->info('🔄 GENERANDO DATOS ADICIONALES PARA TELESCOPE');
        $this->info('===========================================');
        $this->newLine();

        $admin = User::where('email', 'admin@factura.com')->first();

        // 1. Crear clientes adicionales
        $this->info('1. 👥 Creando Clientes Adicionales');
        $clientesData = [
            ['nombre' => 'Empresa Tech Solutions', 'email' => 'tech@telescope.demo', 'telefono' => '+593-991-111-001'],
            ['nombre' => 'Comercial Digital Pro', 'email' => 'digital@telescope.demo', 'telefono' => '+593-992-222-002'],
            ['nombre' => 'Servicios Innovadores SA', 'email' => 'innovadores@telescope.demo', 'telefono' => '+593-993-333-003']
        ];

        $clientes = [];
        foreach ($clientesData as $index => $data) {
            $cliente = Cliente::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nombre' => $data['nombre'],
                    'telefono' => $data['telefono'],
                    'direccion' => 'Dirección Demo ' . ($index + 1),
                    'identificacion' => '0987654' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'tipo_identificacion' => 'ruc'
                ]
            );
            $clientes[] = $cliente;
            $this->info("   ✅ {$cliente->nombre} (ID: {$cliente->id})");
        }

        $this->newLine();

        // 2. Crear productos adicionales
        $this->info('2. 📦 Creando Productos Adicionales');
        $productosData = [
            ['nombre' => 'Laptop Gaming Pro', 'precio' => 1299.99, 'stock' => 25],
            ['nombre' => 'Mouse Gaming RGB', 'precio' => 89.50, 'stock' => 150],
            ['nombre' => 'Teclado Mecánico', 'precio' => 125.75, 'stock' => 75],
            ['nombre' => 'Monitor 4K Ultra', 'precio' => 450.25, 'stock' => 35],
            ['nombre' => 'Auriculares Pro', 'precio' => 199.99, 'stock' => 60]
        ];

        $productos = [];
        foreach ($productosData as $data) {
            $producto = Producto::firstOrCreate(
                ['nombre' => $data['nombre']],
                [
                    'descripcion' => 'Producto de alta calidad - ' . $data['nombre'],
                    'precio' => $data['precio'],
                    'stock' => $data['stock']
                ]
            );
            $productos[] = $producto;
            $this->info("   ✅ {$producto->nombre} - \${$producto->precio}");
        }

        $this->newLine();

        // 3. Crear facturas con múltiples productos
        $this->info('3. 🧾 Creando Facturas Complejas');
        
        foreach ($clientes as $index => $cliente) {
            if ($index >= 2) break; // Solo crear 2 facturas para no sobrecargar

            $factura = Factura::create([
                'cliente_id' => $cliente->id,
                'user_id' => $admin->id,
                'total' => 0,
                'estado' => 'pendiente'
            ]);

            $totalFactura = 0;
            $productosParaFactura = collect($productos)->random(3); // 3 productos aleatorios

            foreach ($productosParaFactura as $producto) {
                $cantidad = rand(1, 3);
                $subtotal = $producto->precio * $cantidad;
                
                FacturaDetalle::create([
                    'factura_id' => $factura->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotal
                ]);

                $totalFactura += $subtotal;
            }

            // Actualizar total de factura
            $factura->update(['total' => $totalFactura]);

            // Crear pago
            $pago = Pago::create([
                'factura_id' => $factura->id,
                'tipo_pago' => ['efectivo', 'tarjeta', 'transferencia'][rand(0, 2)],
                'monto' => $totalFactura,
                'numero_transaccion' => 'TXN-DEMO-' . uniqid(),
                'estado' => 'aprobado',
                'pagado_por' => $admin->id
            ]);

            $factura->update(['estado' => 'pagada']);

            $this->info("   ✅ Factura {$factura->id} - Cliente: {$cliente->nombre} - Total: \${$totalFactura}");
        }

        $this->newLine();

        // 4. Ejecutar consultas complejas para generar actividad
        $this->info('4. 🔍 Ejecutando Consultas Complejas');

        // Consulta 1: Facturas con todos los detalles
        $facturasCompletas = Factura::with(['cliente', 'detalles.producto', 'pagos', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        $this->info("   ✅ Facturas completas consultadas: {$facturasCompletas->count()}");

        // Consulta 2: Productos con más ventas
        $productosConVentas = Producto::whereHas('detalles')
            ->with('detalles')
            ->orderBy('precio', 'desc')
            ->get();
        $this->info("   ✅ Productos con ventas consultados: {$productosConVentas->count()}");

        // Consulta 3: Clientes con facturas
        $clientesConFacturas = Cliente::whereHas('facturas')
            ->with('facturas')
            ->orderBy('created_at', 'desc')
            ->get();
        $this->info("   ✅ Clientes con facturas: {$clientesConFacturas->count()}");

        // Consulta 4: Pagos por método
        $pagosPorMetodo = Pago::selectRaw('tipo_pago, COUNT(*) as total, SUM(monto) as monto_total')
            ->groupBy('tipo_pago')
            ->get();
        $this->info("   ✅ Análisis de pagos por método realizado");

        $this->newLine();

        // 5. Operaciones de actualización masiva
        $this->info('5. 🔄 Operaciones de Actualización');

        // Actualizar stock de productos
        Producto::where('stock', '>', 50)->decrement('stock', 5);
        $this->info("   ✅ Stock actualizado para productos con inventario alto");

        // Actualizar información de algunos clientes
        Cliente::where('id', '>', 25)
            ->update(['direccion' => 'Dirección actualizada - ' . now()->format('H:i:s')]);
        $this->info("   ✅ Direcciones actualizadas para clientes recientes");

        $this->newLine();

        $this->info('🎉 GENERACIÓN DE DATOS COMPLETADA');
        $this->info('=================================');
        $this->info('📊 Telescope ahora tiene información rica para analizar:');
        $this->info('🔗 http://127.0.0.1:8000/telescope');
        $this->newLine();

        $this->info('📈 NUEVOS DATOS GENERADOS:');
        $this->info('- Clientes adicionales: 3');
        $this->info('- Productos adicionales: 5');
        $this->info('- Facturas complejas: 2');
        $this->info('- Detalles de factura: 6-8');
        $this->info('- Pagos procesados: 2');
        $this->info('- Consultas SQL ejecutadas: ~25-30');
        $this->info('- Operaciones de actualización: 2 masivas');

        return 0;
    }
}
