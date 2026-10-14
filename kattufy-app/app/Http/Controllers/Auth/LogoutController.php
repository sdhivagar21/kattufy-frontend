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
        $wasStaff = $request->session()->has('admin_verified_user_id');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        app()->setLocale($locale);

        $target = $wasStaff ? 'admin.login' : 'login';

        return redirect()->route(Localized::routeName($target, $locale))->with('status', __('auth.logged_out'));
    }
}
