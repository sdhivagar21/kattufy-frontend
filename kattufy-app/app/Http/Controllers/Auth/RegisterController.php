<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Modules\Accounts\Enums\OtpPurpose;
use App\Modules\Accounts\Exceptions\OtpThrottled;
use App\Modules\Accounts\Services\OtpService;
use App\Modules\Accounts\Support\PendingVerification;
use App\Support\Turnstile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * New accounts. The details are saved first (phone_verified_at stays NULL),
 * then the mobile number is confirmed by OTP. Submitting twice or refreshing
 * reuses the same unverified account instead of creating another.
 */
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, OtpService $otp): RedirectResponse
    {
        if (! Turnstile::passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withInput()->withErrors(['phone' => __('auth.turnstile_failed')]);
        }

        $phone = $request->phoneE164();
        $email = $request->input('email');

        try {
            $user = DB::transaction(fn () => $this->saveRegistration($phone, $email, $request->input('name')));
        } catch (UniqueConstraintViolationException) {
            // Two submissions raced; the first one won. Treat it like a refresh.
            $user = User::where('phone', $phone)->first();
        }

        if (is_array($user)) {
            return back()->withInput()->withErrors($user);
        }

        try {
            $otp->send($phone, OtpPurpose::Register, $request->ip());
        } catch (OtpThrottled $e) {
            return back()->withInput()->withErrors(['phone' => __('auth.throttle.'.$e->reason, ['seconds' => $e->retryAfterSeconds, 'minutes' => (int) ceil($e->retryAfterSeconds / 60)])]);
        }

        PendingVerification::start(OtpPurpose::Register, $phone, $user->id);

        return redirect()->to(lroute('verify'));
    }

    /** @return User|array<string, string> the account, or validation errors */
    private function saveRegistration(string $phone, string $email, string $name): User|array
    {
        $byPhone = User::where('phone', $phone)->lockForUpdate()->first();
        $byEmail = User::where('email', $email)->lockForUpdate()->first();

        if ($byPhone?->isVerified()) {
            return ['phone' => __('auth.phone_taken')];
        }

        if ($byEmail && $byEmail->id !== $byPhone?->id) {
            return ['email' => __('auth.email_taken')];
        }

        if ($byPhone) {
            // Same person finishing an earlier, unverified registration.
            $byPhone->fill(['name' => $name, 'email' => $email, 'locale' => app()->getLocale()])->save();

            return $byPhone;
        }

        return User::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'locale' => app()->getLocale(),
            'status' => 'active',
        ]);
    }
}
