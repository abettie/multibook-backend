<?php

use App\Exceptions\DataException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        if ($_SERVER['HTTP_HOST'] ?? '' === 'localhost:8000') {
            // swaggerのためのCSRFトークンチェックを無効化
            $middleware->validateCsrfTokens(except: ['*']);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(fn (DataException $e) => response()->json(['message' => $e->getMessage()], 400));
        $exceptions->report(fn (DataException $e) => Log::warning($e->getMessage()))->stop();
    })->create();
