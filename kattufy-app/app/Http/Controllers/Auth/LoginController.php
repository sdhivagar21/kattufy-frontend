<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Modules\Accounts\Enums\OtpPurpose;
use App\Modules\Accounts\Exceptions\OtpThrottled;
use App\Modules\Accounts\Services\OtpService;
use App\Modules\Accounts\Support\PendingVerification;
use App\Support\DemoAccounts;
use App\Support\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Existing accounts sign in with their registered mobile number AND email.
 * Submitting the form only sends a code; the account is signed in after the
 * code is verified. No account is ever created here. Staff use the same form
 * and then continue to the Super Admin mobile and email codes.
 */
class LoginController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.login', [
            'demoAccounts' => OtpService::demo() ? DemoAccounts::forLogin() : [],
            'prefill' => OtpService::demo() ? DemoAccounts::find($request->query('demo')) : null,
        ]);
    }

    public function store(LoginRequest $request, OtpService $otp): RedirectResponse
    {
        if (! Turnstile::passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withInput()->withErrors(['phone' => __('auth.turnstile_failed')]);
        }

        $phone = $request->phoneE164();
        $user = User::where('phone', $phone)->first();

        // One message for "no such number" and "email does not match", so the
        // form cannot be used to find out which numbers are registered.
        if (! $user || mb_strtolower((string) $user->email) !== $request->input('email')) {
            return back()->withInput()->withErrors(['phone' => __('auth.no_matching_account')]);
        }

        if (! $user->isActive()) {
            return back()->withInput()->withErrors(['phone' => __('auth.account_unavailable')]);
        }

        // Staff still need both the mobile and the email code (AdminLoginController).
        $purpose = $user->isStaff() ? OtpPurpose::AdminLogin : OtpPurpose::Login;

        try {
            $otp->send($phone, $purpose, $request->ip());
        } catch (OtpThrottled $e) {
            return back()->withInput()->withErrors(['phone' => __('auth.throttle.'.$e->reason, ['seconds' => $e->retryAfterSeconds, 'minutes' => (int) ceil($e->retryAfterSeconds / 60)])]);
        }

        PendingVerification::start($purpose, $phone, $user->id);

        return redirect()->to(lroute($user->isStaff() ? 'admin.verify-mobile' : 'verify'));
    }
}
