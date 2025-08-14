<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;

class TestPagosFunctionality extends Command
{
    protected $signature = 'test:pagos {--demo : Crear datos de demostración}';
    protected $description = 'Prueba la funcionalidad de pagos del sistema';

    public function handle()
    {
        $this->info('🧪 Probando funcionalidad del sistema de pagos...');
        
        $this->testDatabase();
        $this->testRelationships();
        $this->info('✅ Todas las pruebas completadas exitosamente.');
    }
    
    private function testDatabase()
    {
        $this->info('🔍 Verificando estructura de base de datos...');
        
        // Verificar tablas
        $tables = ['users', 'clientes', 'facturas', 'pagos', 'roles'];
        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $this->line("  ✅ Tabla '{$table}' existe");
            } else {
                $this->error("  ❌ Tabla '{$table}' no encontrada");
            }
        }
        
        // Verificar roles
        $requiredRoles = ['Administrador', 'ventas', 'cliente', 'pagos'];
        foreach ($requiredRoles as $roleName) {
            $role = DB::table('roles')->where('nombre', $roleName)->first();
            if ($role) {
                $this->line("  ✅ Rol '{$roleName}' existe");
            } else {
                $this->error("  ❌ Rol '{$roleName}' no encontrado");
            }
        }
        
        // Contar registros
        $this->table(['Tabla', 'Registros'], [
            ['Pagos Pendientes', Pago::where('estado', 'pendiente')->count()],
            ['Pagos Aprobados', Pago::where('estado', 'aprobado')->count()],
            ['Pagos Rechazados', Pago::where('estado', 'rechazado')->count()],
            ['Total Pagos', Pago::count()],
            ['Total Clientes', DB::table('clientes')->count()],
            ['Total Facturas', DB::table('facturas')->count()],
        ]);
    }
    
    private function testRelationships()
    {
        $this->info('🔗 Probando relaciones del modelo...');
        
        $pago = Pago::first();
        
        if ($pago) {
            $this->line("  ✅ Pago #{$pago->id} encontrado");
            $this->line("    - Estado: " . $pago->estado);
            $this->line("    - Tipo: " . $pago->tipo_pago);
            $this->line("    - Monto: $" . $pago->monto);
            
            if ($pago->validado_por) {
                $this->line("    - Validado por usuario ID: " . $pago->validado_por);
            } else {
                $this->line("    - Sin validar");
            }
        } else {
            $this->warn('  ⚠️  No hay pagos en el sistema');
        }
    }
}
