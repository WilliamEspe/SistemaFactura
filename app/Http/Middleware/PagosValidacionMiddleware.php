<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class PagosValidacionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para acceder a esta página.');
        }

        $user = Auth::user();

        // Verificar que el usuario tenga el rol necesario
        if (!$user->hasRole(['Administrador', 'Pagos'])) {
            abort(403, 'No tiene permisos para acceder a la validación de pagos.');
        }

        return $next($request);
    }
}
