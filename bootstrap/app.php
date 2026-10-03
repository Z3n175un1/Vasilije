<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CapacidadMiddleware;
use App\Http\Middleware\NivelMiddleware;
use App\Http\Middleware\RolMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'rol' => RolMiddleware::class,
            'nivel' => NivelMiddleware::class,
            'lectura' => CapacidadMiddleware::class . ':lectura',
            'escritura' => CapacidadMiddleware::class . ':escritura',
            'borrado' => CapacidadMiddleware::class . ':borrado',
            'anular' => CapacidadMiddleware::class . ':anular',
            'administrar' => CapacidadMiddleware::class . ':administrar',
        ]);

        // Endurecimiento basico: cabeceras de seguridad en todas las respuestas.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Nunca filtrar detalles de excepcion al cliente en produccion.
        $exceptions->dontReportDuplicates();
    })->create();
