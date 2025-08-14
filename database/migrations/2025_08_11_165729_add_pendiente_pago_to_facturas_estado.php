<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enfoque directo: eliminar y recrear la columna
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
        
        Schema::table('facturas', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'pendiente_pago', 'pagada', 'anulada'])
                  ->default('pendiente')
                  ->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
        
        Schema::table('facturas', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'pagada', 'anulada'])
                  ->default('pendiente')
                  ->after('total');
        });
    }
};
