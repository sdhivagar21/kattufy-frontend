<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Enums\VerificationState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Where a provider sees the review outcome, read from the same records the admin uses. */
class VerificationStatusController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $provider = $request->user()->provider?->load(['credentials.type', 'credentials.document', 'categories', 'locality']);

        if (! $provider || $provider->verificationState() === VerificationState::NotSubmitted) {
            return redirect()->to(lroute('provider.register'));
        }

        return view('provider.verification', [
            'provider' => $provider,
            'state' => $provider->verificationState(),
            'feedback' => $provider->currentFeedback(),
            'submittedAt' => $provider->submittedAt(),
            'history' => $provider->events()->whereNotNull('to_status')->limit(10)->get(),
        ]);
    }
}
