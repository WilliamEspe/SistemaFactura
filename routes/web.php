<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\FacturaController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;

Route::middleware(['auth', 'verificar.estado'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // otras rutas protegidas
});


// Redirección desde raíz al dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});


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

// Módulo: Facturas (Ventas o Admin)
Route::middleware(['auth', 'rol:Ventas,Administrador'])->group(function () {
    Route::resource('facturas', FacturaController::class);
    Route::post('/facturas/{factura}/anular', [FacturaController::class, 'anular'])->name('facturas.anular');
    Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'descargarPDF'])->name('facturas.pdf');
    Route::post('/facturas/{factura}/enviar-pdf', [FacturaController::class, 'enviarPDF'])->name('facturas.enviarPDF');
    Route::post('/facturas/{factura}/notificar', [FacturaController::class, 'notificar'])->name('facturas.notificar');


});

// Route::get('/auditoria', function () {
//     return view('auditoria.index', ['logs' => \App\Models\Auditoria::latest()->paginate(10)]);
// })->middleware(['auth', 'rol:Administrador']);

Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'toggleEstado'])->name('usuarios.toggleEstado');


// Rutas de autenticación generadas por Breeze
require __DIR__ . '/auth.php';
