<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;

/** Logging out of the admin panel returns to the login page everyone uses. */
class AdminLogoutResponse implements LogoutResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('login')->with('status', __('auth.logged_out'));
    }
}
