<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Models\User;
use App\Modules\Accounts\Enums\OtpCheck;
use App\Modules\Accounts\Enums\OtpPurpose;
use App\Modules\Accounts\Exceptions\OtpThrottled;
use App\Modules\Accounts\Services\OtpService;
use App\Modules\Accounts\Support\PendingVerification;
use App\Support\DemoAccounts;
use App\Support\Mask;
use App\Support\Phone;
use App\Support\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Super Admin sign-in, separate from the User/Provider flow:
 *   1. registered staff mobile number + email address
 *   2. code sent to the mobile number
 *   3. a second, separate code sent to the email address
 * Only after both codes is the session allowed into the Filament panel.
 */
class AdminLoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isStaff() && $request->session()->get('admin_verified_user_id') === $request->user()->id) {
            return redirect('/admin');
        }

        return view('admin.login', [
            'step' => 1,
            'prefill' => OtpService::demo() && $request->query('demo') === 'admin' ? DemoAccounts::find('admin') : null,
            'demo' => OtpService::demo() ? DemoAccounts::find('admin') : null,
        ]);
    }

    public function store(LoginRequest $request, OtpService $otp): RedirectResponse
    {
        if (! Turnstile::passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withInput()->withErrors(['phone' => __('auth.turnstile_failed')]);
        }

        $phone = $request->phoneE164();
        $user = User::where('phone', $phone)->first();

        if (! $user || ! $user->isStaff() || ! $user->isActive() || mb_strtolower((string) $user->email) !== $request->input('email')) {
            activity('security')->event('admin_login_failed')->withProperties(['ip' => $request->ip()])->log('Admin sign-in details did not match a staff account');

            return back()->withInput()->withErrors(['phone' => __('auth.admin_no_match')]);
        }

        try {
            $otp->send($phone, OtpPurpose::AdminLogin, $request->ip());
        } catch (OtpThrottled $e) {
            return back()->withInput()->withErrors(['phone' => $this->throttleMessage($e)]);
        }

        PendingVerification::start(OtpPurpose::AdminLogin, $phone, $user->id);

        return redirect()->to(lroute('admin.verify-mobile'));
    }

    public function showMobile(OtpService $otp): View|RedirectResponse
    {
        $flow = $this->flow(OtpPurpose::AdminLogin);

        if (! $flow) {
            return redirect()->to(lroute('admin.login'));
        }

        return view('admin.verify', [
            'step' => 2,
            'channel' => 'mobile',
            'destination' => Phone::mask($flow['phone']),
            'expiresIn' => $otp->secondsLeft($flow['phone'], OtpPurpose::AdminLogin),
            'resendIn' => $otp->resendWait($flow['phone'], OtpPurpose::AdminLogin),
            'demoCode' => OtpService::demo() ? config('kattufy.demo_codes.mobile') : null,
            'action' => lroute('admin.verify-mobile.store'),
        ]);
    }

    public function verifyMobile(VerifyCodeRequest $request, OtpService $otp): RedirectResponse
    {
        $flow = $this->flow(OtpPurpose::AdminLogin);

        if (! $flow) {
            return redirect()->to(lroute('admin.login'))->with('error', __('auth.verification_expired'));
        }

        $result = $otp->check($flow['phone'], OtpPurpose::AdminLogin, $request->input('code'));

        if ($result !== OtpCheck::Ok) {
            return back()->withErrors(['code' => $this->checkMessage($result, $otp->attemptsLeft($flow['phone'], OtpPurpose::AdminLogin))]);
        }

        $user = User::findOrFail($flow['user_id']);

        try {
            $otp->send($flow['phone'], OtpPurpose::AdminEmail, $request->ip(), $user->email);
        } catch (OtpThrottled $e) {
            PendingVerification::forget();

            return redirect()->to(lroute('admin.login'))->withErrors(['phone' => $this->throttleMessage($e)]);
        }

        PendingVerification::start(OtpPurpose::AdminEmail, $flow['phone'], $user->id, ['mobile_verified_at' => now()->timestamp]);

        return redirect()->to(lroute('admin.verify-email'))->with('success', __('auth.admin_mobile_ok'));
    }

    public function showEmail(OtpService $otp): View|RedirectResponse
    {
        $flow = $this->flow(OtpPurpose::AdminEmail);

        if (! $flow) {
            return redirect()->to(lroute('admin.login'));
        }

        $user = User::find($flow['user_id']);

        return view('admin.verify', [
            'step' => 3,
            'channel' => 'email',
            'destination' => Mask::email($user?->email),
            'expiresIn' => $otp->secondsLeft($flow['phone'], OtpPurpose::AdminEmail),
            'resendIn' => $otp->resendWait($flow['phone'], OtpPurpose::AdminEmail),
            'demoCode' => OtpService::demo() ? config('kattufy.demo_codes.email') : null,
            'action' => lroute('admin.verify-email.store'),
        ]);
    }

    public function verifyEmail(VerifyCodeRequest $request, OtpService $otp): RedirectResponse
    {
        $flow = $this->flow(OtpPurpose::AdminEmail);

        if (! $flow || empty($flow['mobile_verified_at'])) {
            return redirect()->to(lroute('admin.login'))->with('error', __('auth.verification_expired'));
        }

        $result = $otp->check($flow['phone'], OtpPurpose::AdminEmail, $request->input('code'));

        if ($result !== OtpCheck::Ok) {
            return back()->withErrors(['code' => $this->checkMessage($result, $otp->attemptsLeft($flow['phone'], OtpPurpose::AdminEmail))]);
        }

        $user = User::find($flow['user_id']);

        if (! $user || ! $user->isStaff() || ! $user->isActive()) {
            PendingVerification::forget();

            return redirect()->to(lroute('admin.login'))->withErrors(['phone' => __('auth.admin_no_match')]);
        }

        PendingVerification::forget();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put([
            'admin_verified_user_id' => $user->id,
            'admin_verified_at' => now()->timestamp,
        ]);

        $user->forceFill(['last_login_at' => now(), 'phone_verified_at' => $user->phone_verified_at ?? now()])->save();

        activity('security')->causedBy($user)->performedOn($user)->event('admin_login')
            ->withProperties(['ip' => $request->ip()])->log('Staff signed in with mobile and email codes');

        return redirect('/admin');
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $purpose = PendingVerification::purpose();

        if (! in_array($purpose, [OtpPurpose::AdminLogin, OtpPurpose::AdminEmail], true)) {
            return redirect()->to(lroute('admin.login'));
        }

        $flow = PendingVerification::get();
        $user = User::find($flow['user_id']);

        try {
            $otp->send($flow['phone'], $purpose, $request->ip(), $user?->email);
        } catch (OtpThrottled $e) {
            return back()->withErrors(['code' => $this->throttleMessage($e)]);
        }

        PendingVerification::start($purpose, $flow['phone'], $flow['user_id'], array_diff_key($flow, array_flip(['purpose', 'phone', 'user_id', 'started_at'])));

        return back()->with('status', OtpService::demo() ? __('auth.code_resent_demo') : __('auth.code_resent'));
    }

    private function flow(OtpPurpose $purpose): ?array
    {
        return PendingVerification::is($purpose) ? PendingVerification::get() : null;
    }

    private function throttleMessage(OtpThrottled $e): string
    {
        return __('auth.throttle.'.$e->reason, ['seconds' => $e->retryAfterSeconds, 'minutes' => (int) ceil($e->retryAfterSeconds / 60)]);
    }

    private function checkMessage(OtpCheck $result, int $attemptsLeft): string
    {
        return match ($result) {
            OtpCheck::Wrong => trans_choice('auth.code_wrong', $attemptsLeft, ['count' => $attemptsLeft]),
            OtpCheck::Expired => __('auth.code_expired'),
            OtpCheck::Locked => __('auth.code_locked'),
            default => __('auth.code_missing'),
        };
    }
}
