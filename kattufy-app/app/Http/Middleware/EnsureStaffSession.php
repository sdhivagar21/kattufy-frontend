<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;

/**
 * Guards every Filament route. Staff reach the panel only through the
 * two-factor admin sign-in (credentials, mobile OTP, email OTP), which marks
 * this session. The mark expires after kattufy.staff_session_hours.
 */
class EnsureStaffSession extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $user = $request->user();
        $verifiedAt = (int) $request->session()->get('admin_verified_at', 0);
        $fresh = $verifiedAt > now()->subHours(config('kattufy.staff_session_hours'))->timestamp;

        if ($user && (! $fresh || $request->session()->get('admin_verified_user_id') !== $user->getKey())) {
            $request->session()->forget(['admin_verified_user_id', 'admin_verified_at']);
            $this->unauthenticated($request, $guards);
        }

        parent::authenticate($request, $guards);
    }

    protected function redirectTo($request): ?string
    {
        return route('admin.login');
    }
}
