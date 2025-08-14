<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => \App\Http\Middleware\VerificarRol::class,
            'verificar.estado' => \App\Http\Middleware\VerificarEstadoUsuario::class,
            'multi.auth' => \App\Http\Middleware\MultiModelAuth::class,
            'pagos.validacion' => \App\Http\Middleware\PagosValidacionMiddleware::class,
            'api.role' => \App\Http\Middleware\ApiRoleMiddleware::class,
        ]);

        // Elimina esta línea para evitar el bucle:
        // $middleware->append(\App\Http\Middleware\VerificarEstadoUsuario::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
