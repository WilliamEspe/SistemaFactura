<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '100', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttemptsInt = (int) $maxAttempts;
        $decayMinutesInt = (int) $decayMinutes;

        if ($this->limiter->tooManyAttempts($key, $maxAttemptsInt)) {
            return $this->buildResponse($request, $key, $maxAttemptsInt, $this->limiter->retriesLeft($key, $maxAttemptsInt));
        }

        $this->limiter->hit($key, $decayMinutesInt * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttemptsInt,
            $this->calculateRemainingAttempts($key, $maxAttemptsInt)
        );
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sha1($request->ip() . '|' . $user->getAuthIdentifier());
        }

        return sha1($request->ip());
    }

    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(Request $request, string $key, int $maxAttempts, int $retriesLeft): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'Demasiadas peticiones. Intenta nuevamente en ' . $retryAfter . ' segundos.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter
        ], 429, [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $retriesLeft,
            'X-RateLimit-Reset' => $this->availableAt($retryAfter),
            'Retry-After' => $retryAfter,
        ]);
    }

    /**
     * Add the limit header information to the given response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts, ?int $retryAfter = null): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        if (!is_null($retryAfter)) {
            $response->headers->add([
                'X-RateLimit-Reset' => $this->availableAt($retryAfter),
                'Retry-After' => $retryAfter,
            ]);
        }

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->retriesLeft($key, $maxAttempts);
    }

    /**
     * Get the number of seconds until the next retry.
     */
    protected function availableAt(int $retryAfter): int
    {
        return time() + $retryAfter;
    }
}
