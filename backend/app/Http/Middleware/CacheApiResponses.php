<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Caches successful JSON GET responses per user and URL, so repeated page loads skip the queries.
 *
 * Every key includes a version; any successful write (POST/PUT/PATCH/DELETE) through the API changes it, which makes
 * every cached response stale at once. Responses depend on who asks (visibility rules), hence the user id in the key.
 * Changes made outside the API (tinker, seeders) show up once the TTL runs out, or after `php artisan cache:clear`.
 */
class CacheApiResponses
{
    public const VERSION_KEY = 'api-responses:version';

    private const TTL_SECONDS = 600;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            $response = $next($request);

            if ($response->isSuccessful()) {
                Cache::forever(self::VERSION_KEY, (string) Str::ulid());
            }

            return $response;
        }

        $key = $this->key($request);
        $cached = Cache::get($key);

        if ($cached !== null) {
            return response($cached, 200, ['Content-Type' => 'application/json', 'X-Cache' => 'HIT']);
        }

        $response = $next($request);

        // Only JSON bodies: the invoice PDF and error responses are never stored.
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            Cache::put($key, $response->getContent(), self::TTL_SECONDS);
            $response->headers->set('X-Cache', 'MISS');
        }

        return $response;
    }

    private function key(Request $request): string
    {
        $version = Cache::get(self::VERSION_KEY, 'initial');

        return 'api-responses:'.$version.':'.$request->user()?->getAuthIdentifier().':'.sha1($request->fullUrl());
    }
}
