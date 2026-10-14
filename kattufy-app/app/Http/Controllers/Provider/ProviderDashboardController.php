<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderProfileRequest;
use App\Modules\Enquiries\Models\ContactEvent;
use App\Modules\Geography\Services\LocationResolver;
use App\Modules\Providers\Services\CompletenessCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The approved provider's own area. */
class ProviderDashboardController extends Controller
{
    public function dashboard(Request $request): View
    {
        $provider = $request->user()->provider->load(['credentials.type', 'credentials.document', 'categories', 'locality', 'zones', 'portfolioItems', 'reviewer']);

        return view('provider.dashboard', [
            'provider' => $provider,
            'state' => $provider->verificationState(),
            'feedback' => $provider->latestLifecycleEvent(),
            'currentFeedback' => $provider->currentFeedback(),
            'contactsThisMonth' => ContactEvent::where('provider_id', $provider->id)->where('created_at', '>=', now()->startOfMonth())->count(),
            'documentsOnFile' => $provider->credentials->filter(fn ($c) => $c->document)->count(),
            'documentsTotal' => $provider->credentials->filter(fn ($c) => $c->type?->requires_document)->count(),
        ]);
    }

    public function profile(Request $request): View
    {
        $provider = $request->user()->provider->load(['categories', 'locality', 'services', 'portfolioItems.image', 'portfolioItems.locality']);

        return view('provider.profile', ['provider' => $provider, 'state' => $provider->verificationState()]);
    }

    /** "Choose Your Company Listing" (front end only for now). */
    public function plan(Request $request): View
    {
        return view('provider.plan', ['provider' => $request->user()->provider->load(['categories', 'services', 'portfolioItems.image'])]);
    }

    public function portfolio(Request $request): View
    {
        return view('provider.portfolio', ['provider' => $request->user()->provider->load(['services', 'portfolioItems.locality'])]);
    }

    public function media(Request $request): View
    {
        return view('provider.media', ['provider' => $request->user()->provider->load(['portfolioItems.image'])]);
    }

    public function account(Request $request): View
    {
        return view('provider.account', ['provider' => $request->user()->provider, 'user' => $request->user()]);
    }

    /**
     * Text edits save immediately and are audit-logged; unreviewed_edits_at puts
     * them in the staff review list (Architecture §5). Category and credential
     * changes are not made here.
     */
    public function update(ProviderProfileRequest $request, LocationResolver $locations, CompletenessCalculator $completeness): RedirectResponse
    {
        $provider = $request->user()->provider;

        $provider->fill([
            'description_en' => $request->input('description_en'),
            'description_ta' => $request->input('description_ta'),
            'address' => $request->input('address'),
            'pincode' => $request->input('pincode'),
            'email' => $request->input('business_email'),
            'locality_id' => $locations->fromPincode($request->input('pincode'))?->id ?? $provider->locality_id,
        ]);

        if ($provider->isDirty('description_ta')) {
            // Tamil text is shown only after content staff review it.
            $provider->description_ta_status = filled($provider->description_ta) ? 'pending_review' : 'empty';
        }

        if ($provider->isDirty()) {
            $provider->unreviewed_edits_at = now();
            $provider->save();
            $provider->forceFill(['completeness_score' => $completeness->score($provider->fresh())])->save();
        }

        return redirect()->to(lroute('provider.profile'))->with('success', __('provider.profile_saved'));
    }
}
