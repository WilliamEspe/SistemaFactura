<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificarEstadoUsuario
{
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario no ha iniciado sesión, permitir continuar (para login, register, etc.)
        if (!Auth::check()) {
            return $next($request);
        }

        $usuario = Auth::user();

        // Verificar si el usuario está inactivo o eliminado
        if (!$usuario->activo) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Su sesión ha finalizado, contacte con el administrador.',
            ]);
        }

        return $next($request);
    }
}

