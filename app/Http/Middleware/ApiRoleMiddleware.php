<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ApiRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        // Si no hay usuario autenticado
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
                'error_code' => 'UNAUTHENTICATED'
            ], 401);
        }

        // Verificar si el usuario tiene alguno de los roles permitidos
        $hasRole = false;
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para acceder a esta funcionalidad',
                'required_roles' => $roles,
                'user_roles' => $user->roles->pluck('nombre')->toArray(),
                'error_code' => 'INSUFFICIENT_PERMISSIONS'
            ], 403);
        }

        return $next($request);
    }
}
