<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyCodeRequest;
use App\Models\User;
use App\Modules\Accounts\Enums\OtpCheck;
use App\Modules\Accounts\Enums\OtpPurpose;
use App\Modules\Accounts\Exceptions\OtpThrottled;
use App\Modules\Accounts\Services\AccountHome;
use App\Modules\Accounts\Services\OtpService;
use App\Modules\Accounts\Support\PendingVerification;
use App\Modules\Providers\Services\ProviderSubmissionService;
use App\Modules\Verification\Exceptions\TransitionNotAllowed;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The code-entry step for login, registration and a provider's business
 * phone. Which step is in progress comes from the session, not the form.
 */
class OtpVerificationController extends Controller
{
    private const PURPOSES = [OtpPurpose::Login, OtpPurpose::Register, OtpPurpose::ProviderPhone];

    public function show(Request $request, OtpService $otp): View|RedirectResponse
    {
        $flow = $this->flow($request);

        if (! $flow) {
            return redirect()->to(Auth::check() ? AccountHome::url($request->user()) : lroute('login'));
        }

        $purpose = OtpPurpose::from($flow['purpose']);

        return view('auth.verify', [
            'purpose' => $purpose,
            'maskedPhone' => Phone::mask($flow['phone']),
            'expiresIn' => $otp->secondsLeft($flow['phone'], $purpose),
            'resendIn' => $otp->resendWait($flow['phone'], $purpose),
            'attemptsLeft' => $otp->attemptsLeft($flow['phone'], $purpose),
            'demoCode' => OtpService::demo() ? config('kattufy.demo_codes.mobile') : null,
        ]);
    }

    public function store(VerifyCodeRequest $request, OtpService $otp, ProviderSubmissionService $submissions): RedirectResponse
    {
        $flow = $this->flow($request);

        if (! $flow) {
            return redirect()->to(lroute('login'))->with('error', __('auth.verification_expired'));
        }

        $purpose = OtpPurpose::from($flow['purpose']);
        $result = $otp->check($flow['phone'], $purpose, $request->input('code'));

        if ($result !== OtpCheck::Ok) {
            return back()->withErrors(['code' => $this->message($result, $otp->attemptsLeft($flow['phone'], $purpose))]);
        }

        PendingVerification::forget();

        if ($purpose === OtpPurpose::ProviderPhone) {
            return $this->finishProviderPhone($request, $submissions);
        }

        $user = User::find($flow['user_id']);

        if (! $user || $user->phone !== $flow['phone'] || ! $user->isActive()) {
            return redirect()->to(lroute('login'))->with('error', __('auth.account_unavailable'));
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $user->forceFill([
            'phone_verified_at' => $user->phone_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        if ($purpose === OtpPurpose::Register) {
            return redirect()->to(lroute('role.select'))->with('success', __('auth.registered'));
        }

        return redirect()->intended(AccountHome::url($user))->with('success', __('auth.welcome_back', ['name' => $user->name]));
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $flow = $this->flow($request);

        if (! $flow) {
            return redirect()->to(lroute('login'));
        }

        try {
            $otp->send($flow['phone'], OtpPurpose::from($flow['purpose']), $request->ip());
        } catch (OtpThrottled $e) {
            return back()->withErrors(['code' => __('auth.throttle.'.$e->reason, ['seconds' => $e->retryAfterSeconds, 'minutes' => (int) ceil($e->retryAfterSeconds / 60)])]);
        }

        PendingVerification::start(OtpPurpose::from($flow['purpose']), $flow['phone'], $flow['user_id'], array_diff_key($flow, array_flip(['purpose', 'phone', 'user_id', 'started_at'])));

        return back()->with('status', OtpService::demo() ? __('auth.code_resent_demo') : __('auth.code_resent'));
    }

    private function finishProviderPhone(Request $request, ProviderSubmissionService $submissions): RedirectResponse
    {
        $provider = $request->user()->provider;

        $submissions->markPhoneVerified($provider);

        try {
            $submissions->submit($provider->fresh());
        } catch (TransitionNotAllowed $e) {
            return redirect()->to(lroute('provider.register'))->with('error', $e->getMessage());
        }

        return redirect()->to(lroute('provider.verification'))->with('success', __('provider.submitted'));
    }

    /** The pending step, if it belongs to this kind of visitor. */
    private function flow(Request $request): ?array
    {
        $flow = PendingVerification::get();

        if (! $flow || ! in_array(OtpPurpose::tryFrom($flow['purpose']), self::PURPOSES, true)) {
            return null;
        }

        $needsSignedIn = $flow['purpose'] === OtpPurpose::ProviderPhone->value;

        if ($needsSignedIn !== Auth::check() || ($needsSignedIn && $flow['user_id'] !== $request->user()->id)) {
            return null;
        }

        return $flow;
    }

    private function message(OtpCheck $result, int $attemptsLeft): string
    {
        return match ($result) {
            OtpCheck::Wrong => trans_choice('auth.code_wrong', $attemptsLeft, ['count' => $attemptsLeft]),
            OtpCheck::Expired => __('auth.code_expired'),
            OtpCheck::Locked => __('auth.code_locked'),
            default => __('auth.code_missing'),
        };
    }
}
