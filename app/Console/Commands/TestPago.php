<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\User;

class TestPago extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:pago';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar funcionalidad de pagos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Probando funcionalidad de pagos...');
        
        // 1. Verificar cliente de prueba
        $cliente = Cliente::where('email', 'cliente.test@ejemplo.com')->first();
        if (!$cliente) {
            $this->error('Cliente de prueba no encontrado');
            return;
        }
        
        $this->info("Cliente encontrado: {$cliente->nombre}");
        
        // 2. Verificar usuario asociado
        if (!$cliente->user_id) {
            $this->error('Cliente sin usuario asociado');
            return;
        }
        
        $user = User::find($cliente->user_id);
        if (!$user) {
            $this->error('Usuario del cliente no encontrado');
            return;
        }
        
        $this->info("Usuario asociado: {$user->name}");
        
        // 3. Verificar facturas del cliente
        $facturas = $cliente->facturas;
        $this->info("Facturas del cliente: {$facturas->count()}");
        
        if ($facturas->count() == 0) {
            $this->warn('No hay facturas para este cliente');
            return;
        }
        
        $factura = $facturas->first();
        $this->info("Factura de prueba: #{$factura->id} - Total: \${$factura->total}");
        
        // 4. Intentar crear un pago de prueba
        try {
            $this->info('Intentando crear pago de prueba...');
            
            $pago = new Pago();
            $pago->factura_id = $factura->id;
            $pago->pagado_por = $user->id;
            $pago->monto = 100.00;
            $pago->tipo_pago = 'efectivo';
            $pago->numero_transaccion = 'TEST-12345';
            $pago->observaciones = 'Pago de prueba desde comando';
            $pago->estado = 'pendiente';
            
            $pago->save();
            
            $this->info("Pago creado exitosamente - ID: {$pago->id}");
            
        } catch (\Exception $e) {
            $this->error("Error al crear pago: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
        }
        
        $this->info('Test completado');
    }
}
