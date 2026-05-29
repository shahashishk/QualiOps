<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are not authorized to access this resource.'], 403);
            }

            return response()->view('errors.403', [], 403);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'The requested resource could not be found.'], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has expired. Please refresh the page and try again.'], 419);
            }

            return response()->view('errors.419', [], 419);
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (app()->environment('local') || config('app.debug')) {
                return null;
            }

            $statusCode = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            if ($statusCode !== 500) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'An unexpected error occurred.'], 500);
            }

            return response()->view('errors.500', [], 500);
        });

        $exceptions->report(function (\Throwable $exception) {
            if (app()->environment('local')) {
                return;
            }

            Log::error('Unhandled application exception.', [
                'type' => $exception::class,
                'message' => str($exception->getMessage())
                    ->replaceMatches('/(token|password|secret)=([^\\s&]+)/i', '$1=***')
                    ->limit(200)
                    ->toString(),
            ]);
        });
    })->create();
