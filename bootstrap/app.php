<?php

use App\Http\Middleware\EnsureOwnerIsVerified;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register the custom RBAC middleware aliases used throughout the app,
        // e.g. Route::middleware('role:owner') or ->middleware('permission:properties.verify')
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'permission' => EnsureUserHasPermission::class,
            'owner.verified' => EnsureOwnerIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Every API error — auth, validation, not-found, forbidden — uses the
        // same {success, message, data, meta} envelope as every successful
        // response, so a mobile client never has to special-case error shapes.
        $envelope = fn (string $message, int $status, array $errors = []) => response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => (object) ['errors' => $errors],
        ], $status);

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($envelope) {
            if ($request->is('api/*')) {
                return $envelope('Unauthenticated. A valid Bearer token is required.', 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($envelope) {
            if ($request->is('api/*')) {
                return $envelope('The given data was invalid.', 422, $e->errors());
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($envelope) {
            if ($request->is('api/*')) {
                return $envelope('You are not authorized to perform this action.', 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($envelope) {
            if ($request->is('api/*')) {
                return $envelope('The requested resource was not found.', 404);
            }
        });
    })->create();
