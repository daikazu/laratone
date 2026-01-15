<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default pass-through middleware for Laratone routes.
 *
 * This middleware does nothing by default. Users can replace it with their
 * own middleware to add rate limiting, authentication, or other functionality.
 *
 * @see https://github.com/daikazu/laratone#rate-limiting
 */
final class LaratoneMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
