<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/** Language comes from the URL: /ta/… is Tamil, everything else English. */
class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale = 'en'): Response
    {
        $locale = in_array($locale, config('kattufy.locales'), true) ? $locale : 'en';

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        // Remember the choice for messages sent to this account.
        $user = $request->user();
        if ($user && $user->locale !== $locale && $request->isMethod('GET')) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }

        return $next($request);
    }
}
