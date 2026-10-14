<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Localized;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->input('locale') === 'ta' ? 'ta' : 'en';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        app()->setLocale($locale);

        return redirect()->route(Localized::routeName('login', $locale))->with('status', __('auth.logged_out'));
    }
}
