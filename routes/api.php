<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\FacturaApiController;
use App\Http\Controllers\Api\ClienteApiController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\Api\PagoApiController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Facturación Segura
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas de la API REST con autenticación multi-modelo
| y control de acceso basado en roles. Todas las rutas están protegidas
| con middleware de autenticación y autorización.
|
*/

// Ruta pública para obtener información del usuario autenticado
Route::get('/user', function (Request $request) {
    return response()->json([
        'success' => true,
        'data' => $request->user(),
        'roles' => $request->user()->roles,
        'permissions' => $request->user()->roles->pluck('nombre'),
        'message' => 'Usuario autenticado obtenido exitosamente'
    ]);
})->middleware('multi.auth');

// Rutas de estado de la API
Route::get('/status', function () {
    return response()->json([
        'success' => true,
        'status' => 'online',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
        'message' => 'API Sistema de Facturación Segura funcionando correctamente'
    ]);
});

// Rutas protegidas con autenticación personalizada
Route::middleware('multi.auth')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | RUTAS DE CLIENTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('clientes')->name('api.clientes.')->group(function () {
        Route::get('/', [ClienteApiController::class, 'index'])->name('index');
        Route::post('/', [ClienteApiController::class, 'store'])->name('store');
        Route::get('/{id}', [ClienteApiController::class, 'show'])->name('show');
        Route::put('/{id}', [ClienteApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [ClienteApiController::class, 'destroy'])->name('destroy');
        
        // Rutas específicas de clientes
        Route::get('/{cliente}/facturas', [FacturaApiController::class, 'facturasPorCliente'])->name('facturas');
        Route::get('/{cliente}/pagos', [PagoApiController::class, 'pagosPorCliente'])->name('pagos');
    });

    /*
    |--------------------------------------------------------------------------
    | RUTAS DE PRODUCTOS
    |--------------------------------------------------------------------------
    */
    Route::prefix('productos')->name('api.productos.')->group(function () {
        Route::get('/', [ProductoController::class, 'index'])->name('index');
        Route::post('/', [ProductoController::class, 'store'])->name('store');
        Route::get('/{id}', [ProductoController::class, 'show'])->name('show');
        Route::put('/{id}', [ProductoController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductoController::class, 'destroy'])->name('destroy');
        
        // Rutas específicas de productos
        Route::post('/stock/update', [ProductoController::class, 'updateStock'])->name('update-stock');
        Route::get('/stock/bajo', [ProductoController::class, 'stockBajo'])->name('stock-bajo');
    });

    /*
    |--------------------------------------------------------------------------
    | RUTAS DE FACTURAS
    |--------------------------------------------------------------------------
    */
    Route::prefix('facturas')->name('api.facturas.')->group(function () {
        Route::get('/', [FacturaApiController::class, 'index'])->name('index');
        Route::post('/', [FacturaApiController::class, 'store'])->name('store');
        Route::get('/{id}', [FacturaApiController::class, 'show'])->name('show');
        Route::put('/{id}/anular', [FacturaApiController::class, 'anular'])->name('anular');
        
        // Rutas específicas de facturas
        Route::get('/{factura}/pdf', [FacturaApiController::class, 'generarPdf'])->name('pdf');
        Route::get('/{factura}/pagos', [PagoApiController::class, 'pagosPorFactura'])->name('pagos');
        Route::post('/{factura}/enviar-email', [FacturaApiController::class, 'enviarEmail'])->name('enviar-email');
        
        // Estadísticas de facturas
        Route::get('/stats/resumen', [FacturaApiController::class, 'resumenEstadisticas'])->name('stats.resumen');
        Route::get('/stats/por-periodo', [FacturaApiController::class, 'estadisticasPorPeriodo'])->name('stats.periodo');
    });

    /*
    |--------------------------------------------------------------------------
    | RUTAS DE PAGOS
    |--------------------------------------------------------------------------
    */
    Route::prefix('pagos')->name('api.pagos.')->group(function () {
        Route::get('/', [PagoApiController::class, 'index'])->name('index');
        Route::post('/', [PagoApiController::class, 'store'])->name('store');
        Route::get('/{id}', [PagoApiController::class, 'show'])->name('show');
        
        // Rutas de validación de pagos (solo para roles con permisos)
        Route::put('/{id}/aprobar', [PagoApiController::class, 'aprobar'])->name('aprobar');
        Route::put('/{id}/rechazar', [PagoApiController::class, 'rechazar'])->name('rechazar');
        
        // Estadísticas de pagos
        Route::get('/stats/resumen', [PagoApiController::class, 'resumenEstadisticas'])->name('stats.resumen');
        Route::get('/pendientes', [PagoApiController::class, 'pagosPendientes'])->name('pendientes');
    });

    /*
    |--------------------------------------------------------------------------
    | RUTAS ESPECÍFICAS PARA CLIENTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('mi-cuenta')->name('api.cliente.')->group(function () {
        Route::get('/facturas', [FacturaApiController::class, 'misFacturas'])->name('mis-facturas');
        Route::get('/pagos', [PagoApiController::class, 'misPagos'])->name('mis-pagos');
        Route::get('/perfil', [ClienteApiController::class, 'miPerfil'])->name('mi-perfil');
        Route::put('/perfil', [ClienteApiController::class, 'actualizarPerfil'])->name('actualizar-perfil');
    });

    /*
    |--------------------------------------------------------------------------
    | RUTAS DE REPORTES Y DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
        Route::get('/resumen', function (Request $request) {
            // Verificar permisos
            if (!$request->user()->roles->whereIn('nombre', ['Administrador', 'Ventas'])->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver el dashboard',
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            $stats = [
                'facturas' => [
                    'total' => \App\Models\Factura::count(),
                    'pagadas' => \App\Models\Factura::where('estado', 'pagada')->count(),
                    'pendientes' => \App\Models\Factura::where('estado', 'pendiente')->count(),
                    'anuladas' => \App\Models\Factura::where('anulada', true)->count(),
                ],
                'pagos' => [
                    'total' => \App\Models\Pago::count(),
                    'aprobados' => \App\Models\Pago::where('estado', 'aprobado')->count(),
                    'pendientes' => \App\Models\Pago::where('estado', 'pendiente')->count(),
                    'rechazados' => \App\Models\Pago::where('estado', 'rechazado')->count(),
                ],
                'clientes' => [
                    'total' => \App\Models\Cliente::count(),
                    'activos' => \App\Models\Cliente::whereHas('facturas', function($q) {
                        $q->where('created_at', '>=', now()->subMonths(6));
                    })->count(),
                ],
                'productos' => [
                    'total' => \App\Models\Producto::count(),
                    'stock_bajo' => \App\Models\Producto::where('stock', '<=', 10)->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas del dashboard obtenidas exitosamente'
            ]);
        })->name('resumen');
    });

    /*
    |--------------------------------------------------------------------------
    | RUTAS DE PRUEBA Y DEBUGGING
    |--------------------------------------------------------------------------
    */
    Route::prefix('test')->name('api.test.')->group(function () {
        // Ruta de prueba para verificar autenticación
        Route::get('/auth', function (Request $request) {
            $user = $request->user();
            return response()->json([
                'success' => true,
                'authenticated' => true,
                'user_type' => get_class($user),
                'user_data' => $user->only(['id', 'name', 'email']),
                'roles' => $user->roles->pluck('nombre'),
                'message' => 'Token válido'
            ]);
        })->name('auth');

        // Ruta de prueba de conexión a base de datos
        Route::get('/db', function () {
            try {
                DB::connection()->getPdo();
                $status = 'conectado';
                $error = null;
            } catch (\Exception $e) {
                $status = 'error';
                $error = $e->getMessage();
            }

            return response()->json([
                'success' => $status === 'conectado',
                'database_status' => $status,
                'error' => $error,
                'message' => $status === 'conectado' ? 'Base de datos conectada' : 'Error de conexión'
            ]);
        })->name('db');
    });

});
