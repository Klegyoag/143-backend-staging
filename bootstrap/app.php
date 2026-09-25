<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(App\Http\Middleware\EnsureRequestId::class);
        $middleware->append(App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(App\Http\Middleware\AuditApiRequest::class);
        $middleware->alias([
            'legal.accepted' => App\Http\Middleware\RequireLegalAcceptance::class,
            'idempotency' => App\Http\Middleware\IdempotencyMiddleware::class,
            'recovery.confirmed' => App\Http\Middleware\RequireRecoveryConfirmed::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (Illuminate\Validation\ValidationException $e, Request $request) {
            if (! $request->is('api/*')) return null;
            $firstError = collect($e->errors())->flatten()->first();
            return response()->json([
                'type' => 'https://143.app/problems/validation-error',
                'title' => 'Validation failed',
                'status' => 422,
                'detail' => is_string($firstError) ? $firstError : 'One or more fields are invalid.',
                'errors' => $e->errors(),
                'trace_id' => $request->header('X-Request-ID'),
            ], 422, ['Content-Type' => 'application/problem+json']);
        });
    })->create();
