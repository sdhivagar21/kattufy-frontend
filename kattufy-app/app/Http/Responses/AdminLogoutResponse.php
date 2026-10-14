<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;

/** Logging out of the admin panel returns to the Super Admin sign-in page. */
class AdminLogoutResponse implements LogoutResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('admin.login')->with('status', __('auth.logged_out'));
    }
}
