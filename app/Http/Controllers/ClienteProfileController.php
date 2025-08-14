<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Auditoria;

class ClienteProfileController extends Controller
{
    /**
     * Mostrar el perfil del cliente
     */
    public function index()
    {
        $user = Auth::user();
        
        // Verificar que el usuario tiene rol de cliente
        if (!$user->hasRole('Cliente')) {
            abort(403, 'Acceso denegado');
        }
        
        $cliente = $user->cliente;
        
        if (!$cliente) {
            abort(404, 'Cliente no encontrado');
        }
        
        return view('cliente.profile', compact('user', 'cliente'));
    }
    
    /**
     * Actualizar contraseña del cliente
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        
        // Verificar que el usuario tiene rol de cliente
        if (!$user->hasRole('Cliente')) {
            abort(403, 'Acceso denegado');
        }
        
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        
        // Verificar la contraseña actual
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta']);
        }
        
        // Actualizar la contraseña
        $user->update([
            'password' => Hash::make($request->password)
        ]);
        
        // Registrar en auditoría
        Auditoria::create([
            'user_id' => $user->id,
            'accion' => 'Cambio de Contraseña',
            'descripcion' => "El cliente {$user->name} cambió su contraseña",
            'modulo' => 'Cliente Profile',
        ]);
        
        return back()->with('success', 'Contraseña actualizada correctamente');
    }
}
