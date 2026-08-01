<?php

declare(strict_types=1);

namespace App\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureApiRequestId
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = $request->header('X-Request-ID');

        if (! is_string($traceId) || $traceId === '' || mb_strlen($traceId) > 80) {
            $traceId = (string) Str::uuid();
        }

        $request->attributes->set('api_request_id', $traceId);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $traceId);

        return $response;
    }
}
