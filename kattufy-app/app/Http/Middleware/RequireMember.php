<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages behind sign-in. A session is accepted only for an active account
 * whose mobile number was verified by OTP. When kattufy.require_login is off,
 * guests may browse but still need to sign in for account pages.
 */
class RequireMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if (! config('kattufy.require_login') && ! $this->isAccountPage($request)) {
                return $next($request);
            }

            return $this->toLogin($request);
        }

        if (! $user->isVerified() || ! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(lroute('login'))->with('error', __('auth.account_unavailable'));
        }

        return $next($request);
    }

    private function toLogin(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('auth.login_required')], 401);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->to(lroute('login'));
    }

    private function isAccountPage(Request $request): bool
    {
        $name = (string) $request->route()?->getName();
        $name = str_starts_with($name, 'ta.') ? substr($name, 3) : $name;

        return str_starts_with($name, 'user.') || str_starts_with($name, 'provider.')
            || in_array($name, ['role.select', 'role.store', 'account', 'enquiry', 'enquiry.store', 'enquiry.sent', 'contact.reveal'], true);
    }
}
