<?php

use App\Domain\Exception\InvalidTripPeriod;
use App\Domain\Exception\QuoteAlreadyContracted;
use App\Domain\Exception\QuoteNotFound;
use App\Domain\Exception\UnknownDestination;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (UnknownDestination $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'El país de destino no está disponible.',
                    'errors' => [
                        'destination_code' => ['El país de destino no está disponible.'],
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (QuoteAlreadyContracted $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 409);
            }
        });

        $exceptions->render(function (QuoteNotFound $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'No se encontró el registro solicitado.',
                ], 404);
            }
        });

        $exceptions->render(function (InvalidTripPeriod $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => [
                        'return_date' => [$exception->getMessage()],
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'No se encontró el registro solicitado.',
                ], 404);
            }
        });
    })->create();
