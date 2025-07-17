<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class VerificarRol
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->activo) {
            Auth::logout();
            return redirect('/login')->withErrors(['msg' => 'Su sesión ha finalizado, contacte con el administrador.']);
        }

        // Verificar si el usuario tiene alguno de los roles permitidos
        if (!$usuario->roles->pluck('nombre')->intersect($roles)->isNotEmpty()) {
            abort(403, 'Acceso no autorizado.');
        }

        return $next($request);
    }
}
