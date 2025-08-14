<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiInputSanitizer
{
    /**
     * Patrones peligrosos que podrían indicar intentos de inyección
     */
    protected array $dangerousPatterns = [
        // SQL Injection patterns
        '/(\bunion\b.*\bselect\b)/i',
        '/(\bdrop\b.*\btable\b)/i',
        '/(\bdelete\b.*\bfrom\b)/i',
        '/(\binsert\b.*\binto\b)/i',
        '/(\bupdate\b.*\bset\b)/i',
        '/(\bexec\b.*\()/i',
        '/(\bexecute\b.*\()/i',
        
        // XSS patterns
        '/<script[^>]*>.*?<\/script>/is',
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/javascript:[^"\']*["\']?/i',
        '/on\w+\s*=/i',
        '/<object[^>]*>.*?<\/object>/is',
        '/<embed[^>]*>/i',
        
        // Command injection patterns
        '/(\bwget\b|\bcurl\b|\bping\b|\bnc\b|\bnetcat\b|\btelnet\b)/i',
        '/(\bcat\b|\bls\b|\bps\b|\btop\b|\bkill\b)/i',
        '/(\\\\|\/)(etc|bin|usr|var|tmp)\\\\?\//i',
        
        // Path traversal
        '/\.\.[\/\\\\]/i',
        '/\.\.%2[fF]/i',
        '/\.\.%5[cC]/i',
    ];

    /**
     * Caracteres que serán removidos/sanitizados
     */
    protected array $dangerousChars = [
        '\0', // NULL byte
        '\x1A', // EOF character
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar a rutas API
        if (!$request->is('api/*')) {
            return $next($request);
        }

        // Validar y sanitizar todos los datos de entrada
        $this->validateAndSanitizeInput($request);

        return $next($request);
    }

    /**
     * Valida y sanitiza todos los datos de entrada
     */
    protected function validateAndSanitizeInput(Request $request): void
    {
        $allInput = $request->all();
        $sanitizedInput = [];
        
        foreach ($allInput as $key => $value) {
            $sanitizedInput[$key] = $this->sanitizeValue($key, $value, $request);
        }
        
        // Reemplazar el input del request con el sanitizado
        $request->replace($sanitizedInput);
    }

    /**
     * Sanitiza un valor individual
     */
    protected function sanitizeValue(string $key, $value, Request $request)
    {
        if (is_array($value)) {
            $sanitizedArray = [];
            foreach ($value as $subKey => $subValue) {
                $sanitizedArray[$subKey] = $this->sanitizeValue("{$key}.{$subKey}", $subValue, $request);
            }
            return $sanitizedArray;
        }

        if (!is_string($value)) {
            return $value;
        }

        // Log intentos sospechosos antes de sanitizar
        $this->detectAndLogSuspiciousPatterns($key, $value, $request);

        // Remover caracteres peligrosos
        $sanitized = str_replace($this->dangerousChars, '', $value);
        
        // Para campos específicos, aplicar sanitización adicional
        $sanitized = $this->applySanitizationByField($key, $sanitized);
        
        return $sanitized;
    }

    /**
     * Detecta y registra patrones sospechosos
     */
    protected function detectAndLogSuspiciousPatterns(string $key, string $value, Request $request): void
    {
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                Log::warning('Posible intento de inyección detectado', [
                    'field' => $key,
                    'value' => $value,
                    'pattern' => $pattern,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'user_id' => $request->user()?->id,
                    'url' => $request->fullUrl(),
                    'method' => $request->method()
                ]);
                
                // Opcionalmente, lanzar excepción para detener la request
                if ($this->isHighRiskPattern($pattern)) {
                    throw new \InvalidArgumentException("Entrada no válida detectada en campo: {$key}");
                }
                break;
            }
        }
    }

    /**
     * Determina si un patrón es de alto riesgo
     */
    protected function isHighRiskPattern(string $pattern): bool
    {
        $highRiskPatterns = [
            '/(\bdrop\b.*\btable\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bexec\b.*\()/i',
            '/(\bexecute\b.*\()/i',
            '/<script[^>]*>.*?<\/script>/is',
        ];
        
        return in_array($pattern, $highRiskPatterns);
    }

    /**
     * Aplica sanitización específica por campo
     */
    protected function applySanitizationByField(string $key, string $value): string
    {
        switch (true) {
            case str_contains($key, 'email'):
                return filter_var($value, FILTER_SANITIZE_EMAIL);
                
            case str_contains($key, 'url'):
                return filter_var($value, FILTER_SANITIZE_URL);
                
            case str_contains($key, 'phone') || str_contains($key, 'telefono'):
                return preg_replace('/[^0-9\+\-\(\)\s]/', '', $value);
                
            case str_contains($key, 'nombre') || str_contains($key, 'name'):
                return preg_replace('/[<>"\']/', '', $value);
                
            case str_contains($key, 'search') || str_contains($key, 'query'):
                // Para búsquedas, ser más restrictivo
                return preg_replace('/[<>"\'\(\);]/', '', $value);
                
            default:
                // Sanitización general: remover caracteres potencialmente peligrosos
                return preg_replace('/[<>"\']/', '', $value);
        }
    }
}
