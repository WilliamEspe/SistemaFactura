<?php

namespace App\Guards;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;
use App\Models\Cliente;

/**
 * Guard personalizado para manejar autenticación con múltiples modelos usando Sanctum
 */
class MultiModelSanctumGuard implements Guard
{
    use GuardHelpers;

    protected Request $request;
    protected string $inputKey;

    public function __construct(UserProvider $provider, Request $request, string $inputKey = 'api_token')
    {
        $this->provider = $provider;
        $this->request = $request;
        $this->inputKey = $inputKey;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();

        if (!$token) {
            return null;
        }

        // Buscar el token en la base de datos
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return null;
        }

        // Obtener el modelo asociado al token
        $tokenable = $accessToken->tokenable;

        if ($tokenable instanceof User || $tokenable instanceof Cliente) {
            // Actualizar last_used_at
            $accessToken->update(['last_used_at' => now()]);
            
            return $this->user = $tokenable;
        }

        return null;
    }

    /**
     * Get the token from the request.
     *
     * @return string|null
     */
    protected function getTokenFromRequest(): ?string
    {
        return $this->request->bearerToken() ?: $this->request->input($this->inputKey);
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (empty($credentials['token'])) {
            return false;
        }

        $accessToken = PersonalAccessToken::findToken($credentials['token']);
        
        return $accessToken && ($accessToken->tokenable instanceof User || $accessToken->tokenable instanceof Cliente);
    }
}
