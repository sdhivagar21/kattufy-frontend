<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public pages use one canonical URL ending in a slash (Architecture §10):
 * /login → 301 → /login/. Admin, Livewire and signed links are left alone.
 */
class TrailingSlash
{
    public static function applies(?\Illuminate\Routing\Route $route): bool
    {
        if (! $route || ! in_array('GET', $route->methods(), true)) {
            return false;
        }

        $name = (string) $route->getName();

        return $name !== '' && ! str_starts_with($name, 'filament.') && ! str_starts_with($name, 'livewire')
            && ! str_starts_with($name, 'documents.') && ! str_starts_with($name, 'storage.');
    }

    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if (config('kattufy.trailing_slash_redirect') && $request->isMethod('GET') && $path !== '/' && ! str_ends_with($path, '/') && self::applies($request->route())) {
            $query = $request->getQueryString();

            return redirect()->to($request->getSchemeAndHttpHost().$request->getBaseUrl().$path.'/'.($query ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}
