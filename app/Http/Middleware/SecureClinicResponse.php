<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureClinicResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $production = config('app.env') === 'production';
        $canonicalUrl = rtrim((string) config('app.canonical_url'), '/');

        if ($production) {
            $parts = $canonicalUrl !== '' ? parse_url($canonicalUrl) : false;
            if (! is_array($parts)
                || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
                || empty($parts['host'])
                || isset($parts['user'])
                || isset($parts['pass'])
                || (isset($parts['path']) && ! in_array($parts['path'], ['', '/'], true))) {
                abort(500, 'Server configuration error.');
            }

            if (! $request->isSecure()) {
                $uri = $request->getRequestUri();
                if ($uri === '' || $uri[0] !== '/' || str_starts_with($uri, '//')) {
                    $uri = '/';
                }

                return redirect()->away($canonicalUrl.$uri, 308);
            }
        }

        $response = $next($request);

        if ($production) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; img-src 'self' data: https://images.unsplash.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net; font-src 'self' data: https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; upgrade-insecure-requests"
            );
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        }

        return $response;
    }
}
