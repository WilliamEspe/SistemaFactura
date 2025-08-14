<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\PagoValidacionController;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use App\Models\User;

// Rutas de validación de pagos (web interface)
Route::middleware(['auth', 'pagos.validacion'])->prefix('pagos/validacion')->name('pagos.validacion.')->group(function () {
    Route::get('/', [PagoValidacionController::class, 'index'])->name('index');
    Route::get('/historial', [PagoValidacionController::class, 'historial'])->name('historial');
    Route::get('/{pago}', [PagoValidacionController::class, 'show'])->name('show');
    Route::post('/{pago}/aprobar', [PagoValidacionController::class, 'aprobar'])->name('aprobar');
    Route::post('/{pago}/rechazar', [PagoValidacionController::class, 'rechazar'])->name('rechazar');
});

Route::middleware(['auth', 'verificar.estado'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // otras rutas protegidas
});

// Redirección desde raíz
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Rutas de perfil (opcional Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Módulo: Usuarios (solo Admin)
Route::middleware(['auth', 'rol:Administrador'])->group(function () {
    Route::resource('usuarios', UsuarioController::class);
    Route::post('/usuarios/{usuario}/asignar-rol', [UsuarioController::class, 'asignarRol'])->name('usuarios.asignarRol');
    Route::post('/usuarios/{usuario}/inactivar', [UsuarioController::class, 'inactivar'])->name('usuarios.inactivar');
    Route::patch('usuarios/{usuario}/eliminar', [UsuarioController::class, 'eliminar'])->name('usuarios.eliminar');
    Route::delete('/usuarios/{usuario}/eliminar-definitivo', [UsuarioController::class, 'eliminarDefinitivo'])->name('usuarios.eliminarDefinitivo');
    Route::get('/usuario-papelera', [UsuarioController::class, 'papelera'])->name('usuarios.papelera');
    Route::post('/usuarios/{id}/restaurar', [UsuarioController::class, 'restaurar'])->name('usuarios.restaurar');
    Route::get('/auditoria', [UsuarioController::class, 'auditoria'])->name('auditoria.index');
    
    // Rutas de tokens - SOLO ADMINISTRADOR
    Route::post('/usuarios/crear-token', [UsuarioController::class, 'crearTokenAccesso'])->name('usuarios.crearToken');
    Route::delete('/usuarios/token/{token}/revocar', [UsuarioController::class, 'revocarToken'])->name('usuarios.revocarToken');
    Route::post('/clientes/crear-token', [ClienteController::class, 'crearTokenCliente'])->name('clientes.crearToken');
}); 

// Módulo: Clientes (Secretario o Admin)
Route::middleware(['auth', 'rol:Secretario,Administrador'])->group(function () {
    Route::resource('clientes', ClienteController::class);
    Route::get('/clientes/verificar/{cliente}/{hash}', [ClienteController::class, 'verificarCorreo'])->name('clientes.verificar');
    Route::patch('clientes/{cliente}/eliminar', [ClienteController::class, 'eliminar'])->name('clientes.eliminar');
    Route::delete('/clientes/{cliente}/eliminar-definitivo', [ClienteController::class, 'eliminarDefinitivo'])->name('clientes.eliminarDefinitivo');
    Route::get('/cliente-papelera', [ClienteController::class, 'papelera'])->name('clientes.papelera');
    Route::post('/clientes/{id}/restaurar', [ClienteController::class, 'restaurar'])->name('clientes.restaurar');
});

// Módulo: Productos (Bodega o Admin)
Route::middleware(['auth', 'rol:Bodega,Administrador'])->group(function () {
    Route::resource('productos', ProductoController::class);
    Route::patch('productos/{producto}/eliminar', [ProductoController::class, 'eliminar'])->name('productos.eliminar');
    Route::delete('/productos/{producto}/eliminar-definitivo', [ProductoController::class, 'eliminarDefinitivo'])->name('productos.eliminarDefinitivo');
    Route::get('/productos-papelera', [ProductoController::class, 'papelera'])->name('productos.papelera');
    Route::post('/productos/{id}/restaurar', [ProductoController::class, 'restaurar'])->name('productos.restaurar');
});

// Módulo: Facturas (Admin, Secretario, Vendedor)
Route::middleware(['auth', 'rol:Administrador,Secretario,Ventas'])->group(function () {
    Route::resource('facturas', FacturaController::class);
    Route::post('/facturas/{factura}/anular', [FacturaController::class, 'anular'])->name('facturas.anular');
    Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'descargarPDF'])->name('facturas.pdf');
    Route::post('/facturas/{factura}/enviar-pdf', [FacturaController::class, 'enviarPDF'])->name('facturas.enviarPDF');
    Route::post('/facturas/{factura}/notificar', [FacturaController::class, 'notificar'])->name('facturas.notificar');
});

// Módulo: Cliente - Ver sus propias facturas (corregido para User-Cliente)
Route::middleware(['auth'])->group(function () {
    Route::get('/mis-facturas', [ClienteController::class, 'misFacturas'])->name('cliente.facturas');
    Route::get('/pagar-factura/{factura}', [ClienteController::class, 'pagarFactura'])->name('cliente.pagar-factura');
    Route::post('/procesar-pago', [ClienteController::class, 'procesarPagoWeb'])->name('cliente.procesar-pago');
});

Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'toggleEstado'])->name('usuarios.toggleEstado');

// Ruta para crear tokens - SOLO ADMINISTRADOR
Route::middleware(['auth', 'rol:Administrador'])->group(function () {
    Route::post('/tokens/create', function (Request $request) {
        $token = $request->user()->createToken($request->token_name);
     
        return ['token' => $token->plainTextToken];
    });
});

// Documentación pública de API (sin autenticación requerida)
Route::get('/api/documentation', function () {
    return view('api.documentation');
})->name('api.documentation');

// Rutas para perfil de cliente
Route::middleware(['auth', 'rol:Cliente'])->group(function () {
    Route::get('/cliente/perfil', [App\Http\Controllers\ClienteProfileController::class, 'index'])->name('cliente.perfil');
    Route::post('/cliente/actualizar-password', [App\Http\Controllers\ClienteProfileController::class, 'updatePassword'])->name('cliente.actualizar.password');
});

// Ruta temporal de debug
Route::get('/debug/auth-status', function () {
    return view('debug.auth-status');
})->name('debug.auth-status');

// Rutas de autenticación generadas por Breeze
require __DIR__ . '/auth.php';
