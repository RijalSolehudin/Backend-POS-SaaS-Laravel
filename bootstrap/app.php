<?php

use App\Http\ApiProblemDetails;
use App\Http\EnsureActiveApiTenantUser;
use App\Http\EnsureApiRequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', EnsureApiRequestId::class);
        $middleware->alias([
            'api.tenant-user-active' => EnsureActiveApiTenantUser::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('platform', 'platform/*')
                ? route('platform.login')
                : route('tenant.login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if ($request->is('api/*')) {
                return app(ApiProblemDetails::class)->render($response, $exception, $request);
            }

            if (
                ! $request->is('platform', 'platform/*')
                || $request->expectsJson()
                || ! in_array($response->getStatusCode(), [403, 404, 419, 429, 500, 503], true)
            ) {
                return $response;
            }

            $status = $response->getStatusCode();
            $content = match ($status) {
                403 => ['Access denied', 'Your platform account is not authorized to perform this action.'],
                404 => ['Page not found', 'The platform page you requested does not exist or is no longer available.'],
                419 => ['Page expired', 'Your security token expired. Return to the previous page and submit the action again.'],
                429 => ['Too many requests', 'This action has been temporarily limited. Wait a moment before trying again.'],
                503 => ['Platform temporarily unavailable', 'Scheduled maintenance or a temporary service interruption is in progress.'],
                default => ['Something went wrong', 'The request could not be completed. No changes should be assumed.'],
            };

            $platformResponse = response()->view('errors.platform', [
                'status' => $status,
                'heading' => $content[0],
                'description' => $content[1],
            ], $status);

            if ($response->headers->has('Retry-After')) {
                $platformResponse->headers->set('Retry-After', (string) $response->headers->get('Retry-After'));
            }

            return $platformResponse;
        });
    })->create();
