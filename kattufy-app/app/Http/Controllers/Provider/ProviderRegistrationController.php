<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderRegistrationRequest;
use App\Modules\Accounts\Enums\OtpPurpose;
use App\Modules\Accounts\Exceptions\OtpThrottled;
use App\Modules\Accounts\Services\AccountHome;
use App\Modules\Accounts\Services\OtpService;
use App\Modules\Accounts\Support\PendingVerification;
use App\Modules\Directory\Models\CategoryGroup;
use App\Modules\Providers\Services\ProviderSubmissionService;
use App\Modules\Verification\Exceptions\TransitionNotAllowed;
use App\Modules\Verification\Models\CredentialType;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Business details and documents for verification (/provider/register/). */
class ProviderRegistrationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $provider = $user->provider?->load(['credentials.type', 'credentials.document', 'categories']);

        if ($provider && ! $provider->verificationState()->canEditSubmission()) {
            return redirect()->to(AccountHome::providerUrl($user));
        }

        return view('provider.register', [
            'provider' => $provider,
            'state' => $provider?->verificationState(),
            'feedback' => $provider?->currentFeedback(),
            'groups' => CategoryGroup::with('categories')->where('is_active', true)->orderBy('sort_order')->get(),
            'idTypes' => CredentialType::whereIn('slug', CredentialType::slugsIn('identity'))->where('is_active', true)->orderBy('sort_order')->get(),
            'certificateTypes' => CredentialType::whereIn('slug', CredentialType::optionalCertificateSlugs())->where('is_active', true)->orderBy('sort_order')->get(),
            'defaults' => [
                'business_phone' => Phone::national($provider?->phone ?? $user->phone),
                'business_email' => $provider?->email ?? $user->email,
            ],
        ]);
    }

    public function store(ProviderRegistrationRequest $request, ProviderSubmissionService $submissions, OtpService $otp): RedirectResponse
    {
        $user = $request->user();
        $current = $user->provider;

        if ($current && ! $current->verificationState()->canEditSubmission()) {
            return redirect()->to(AccountHome::providerUrl($user))->with('error', __('provider.not_editable'));
        }

        $provider = $submissions->save($user, $request->validated(), $request->file('address_proof'), $request->file('certificate'));

        if ($request->input('action') === 'save') {
            return redirect()->to(lroute('provider.register'))->with('success', __('provider.saved'));
        }

        // A business number other than the account's verified number gets its own code first.
        if (! $provider->phone_verified_at) {
            try {
                $otp->send($provider->phone, OtpPurpose::ProviderPhone, $request->ip());
            } catch (OtpThrottled $e) {
                return redirect()->to(lroute('provider.register'))->withErrors(['business_phone' => __('auth.throttle.'.$e->reason, ['seconds' => $e->retryAfterSeconds, 'minutes' => (int) ceil($e->retryAfterSeconds / 60)])]);
            }

            PendingVerification::start(OtpPurpose::ProviderPhone, $provider->phone, $user->id, ['provider_id' => $provider->id]);

            return redirect()->to(lroute('verify'))->with('status', __('provider.verify_business_phone'));
        }

        try {
            $submissions->submit($provider);
        } catch (TransitionNotAllowed $e) {
            return redirect()->to(lroute('provider.register'))->with('error', $e->getMessage());
        }

        return redirect()->to(lroute('provider.verification'))->with('success', __('provider.submitted'));
    }
}
