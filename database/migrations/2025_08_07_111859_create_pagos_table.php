<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->enum('tipo_pago', ['efectivo', 'tarjeta', 'transferencia', 'cheque']);
            $table->decimal('monto', 10, 2);
            $table->string('numero_transaccion')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->foreignId('pagado_por')->constrained('users')->onDelete('cascade'); // Cliente que pagó
            $table->foreignId('validado_por')->nullable()->constrained('users')->onDelete('set null'); // Usuario que validó
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
