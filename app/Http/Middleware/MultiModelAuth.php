<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cliente;

/**
 * Middleware para autenticación con múltiples modelos
 */
class MultiModelAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token no proporcionado'
            ], 401);
        }
        
        // Debug: Mostrar información del token
        $hashedToken = hash('sha256', $token);
        
        // Buscar el token primero en la tabla de clientes
        $clienteToken = DB::table('cliente_access_tokens')
            ->where('token', $hashedToken)
            ->first();
            
        if ($clienteToken) {
            // Token encontrado en tabla de clientes
            $cliente = Cliente::find($clienteToken->cliente_id);
            
            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente asociado al token no encontrado',
                    'debug' => [
                        'cliente_id' => $clienteToken->cliente_id
                    ]
                ], 401);
            }
            
            // Establecer el cliente en el request
            $request->setUserResolver(function () use ($cliente) {
                return $cliente;
            });
            
            // Actualizar last_used_at
            DB::table('cliente_access_tokens')
                ->where('id', $clienteToken->id)
                ->update(['last_used_at' => now()]);
                
            return $next($request);
        }
        
        // Si no se encuentra en clientes, buscar en la tabla de usuarios (personal_access_tokens)
        $accessToken = DB::table('personal_access_tokens')
            ->where('token', $hashedToken)
            ->first();
        
        if (!$accessToken) {
            // Debug: Mostrar tokens disponibles
            $tokensCount = DB::table('personal_access_tokens')->count();
            $firstTokenHash = DB::table('personal_access_tokens')
                ->first(['token', 'tokenable_type', 'tokenable_id']);
            
            return response()->json([
                'success' => false,
                'message' => 'Token inválido',
                'debug' => [
                    'token_recibido' => substr($token, 0, 10) . '...',
                    'token_hash' => substr($hashedToken, 0, 10) . '...',
                    'tokens_en_bd' => $tokensCount,
                    'primer_token_bd' => $firstTokenHash ? [
                        'hash' => substr($firstTokenHash->token, 0, 10) . '...',
                        'type' => $firstTokenHash->tokenable_type,
                        'id' => $firstTokenHash->tokenable_id
                    ] : null
                ]
            ], 401);
        }
        
        // Obtener el modelo tokenable
        $tokenableType = $accessToken->tokenable_type;
        $tokenableId = $accessToken->tokenable_id;
        
        $user = null;
        if ($tokenableType === 'App\\Models\\User') {
            $user = User::find($tokenableId);
        } elseif ($tokenableType === 'App\\Models\\Cliente') {
            $user = Cliente::find($tokenableId);
        }
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario asociado al token no encontrado',
                'debug' => [
                    'tokenable_type' => $tokenableType,
                    'tokenable_id' => $tokenableId
                ]
            ], 401);
        }
        
        // Establecer el usuario en el request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        // Actualizar last_used_at
        DB::table('personal_access_tokens')
            ->where('id', $accessToken->id)
            ->update(['last_used_at' => now()]);
        
        return $next($request);
    }
}
