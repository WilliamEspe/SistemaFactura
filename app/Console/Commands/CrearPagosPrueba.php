<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Factura;
use App\Models\Pago;

class CrearPagosPrueba extends Command
{
    /**
     * Nombre y firma del comando de consola
     */
    protected $signature = 'pagos:crear-prueba';

    /**
     * Descripción del comando
     */
    protected $description = 'Crear dos pagos de prueba (transferencia y tarjeta) para una factura existente';

    /**
     * Ejecuta el comando
     */
    public function handle()
    {
        $factura = Factura::with('cliente')->find(12);

        if (!$factura) {
            $this->error("❌ Factura #12 no encontrada.");
            return;
        }

        $this->info("Factura #{$factura->id} - Cliente: {$factura->cliente->nombre}");

        $pago1 = Pago::create([
            'factura_id' => $factura->id,
            'tipo_pago' => 'transferencia',
            'monto' => $factura->total,
            'numero_transaccion' => 'TRF' . rand(100000, 999999),
            'observacion' => 'Pago por transferencia bancaria - Factura #' . $factura->id,
            'estado' => 'pendiente',
            'pagado_por' => $factura->cliente_id,
        ]);

        $pago2 = Pago::create([
            'factura_id' => $factura->id,
            'tipo_pago' => 'tarjeta',
            'monto' => $factura->total,
            'numero_transaccion' => 'CARD' . rand(100000, 999999),
            'observacion' => 'Pago con tarjeta - Factura #' . $factura->id,
            'estado' => 'pendiente',
            'pagado_por' => $factura->cliente_id,
        ]);

        $this->info("✅ Pago transferencia creado: ID #{$pago1->id}, Monto: \${$pago1->monto}");
        $this->info("✅ Pago tarjeta creado: ID #{$pago2->id}, Monto: \${$pago2->monto}");
    }
}
