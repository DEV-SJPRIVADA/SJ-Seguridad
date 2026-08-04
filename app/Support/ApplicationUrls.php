<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class ApplicationUrls
{
    /** URL base que deben abrir los usuarios (correos, enlaces firmados). */
    public static function publicRoot(): string
    {
        $url = config('purchase-requests.public_url') ?: config('app.url');

        return rtrim((string) $url, '/');
    }

    /**
     * Genera rutas usando PUBLIC_APP_URL (no el host local de desarrollo).
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function usingPublicRoot(callable $callback): mixed
    {
        $publicRoot = static::publicRoot();
        $scheme = parse_url($publicRoot, PHP_URL_SCHEME) ?: 'http';

        URL::forceRootUrl($publicRoot);
        URL::forceScheme($scheme);

        try {
            return $callback();
        } finally {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme(parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http');
        }
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return static::usingPublicRoot(
            fn () => route($name, $parameters, $absolute)
        );
    }

    public static function temporarySignedRoute(string $name, \DateTimeInterface $expiration, array $parameters = []): string
    {
        return static::usingPublicRoot(
            fn () => URL::temporarySignedRoute($name, $expiration, $parameters)
        );
    }
}
