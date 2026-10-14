@use('App\Modules\Verification\Enums\CredentialStatus')
@use('App\Support\Phone')
{{--
    What the verification team checks and where each item stands, read from
    the provider's own records (credential status and rejection reasons come
    from the admin review). No invented results: missing items say "Missing".
--}}
@php
    $id = $provider->idCredential();
    $proof = $provider->addressProof();
    $certificate = $provider->certificate();
    $requirements = \App\Modules\Verification\Models\VerificationRequirement::with('credentialType')
        ->whereIn('category_id', $provider->categories->pluck('id'))
        ->where('is_required', true)->where('is_active', true)->get()
        ->unique('credential_type_id');

    $fromCredential = function ($credential, bool $required) {
        if (! $credential) {
            return $required ? ['missing', __('provider.item_missing')] : ['none', __('provider.item_not_added')];
        }

        return match ($credential->status) {
            CredentialStatus::Verified => ['ok', __('provider.item_verified')],
            CredentialStatus::Rejected, CredentialStatus::Expired => ['action', __('provider.item_action')],
            default => ['pending', __('provider.item_submitted')],
        };
    };

    $detailsComplete = filled($provider->business_name_en) && $provider->categories->isNotEmpty()
        && filled($provider->address) && filled($provider->pincode) && filled($provider->email);

    $items = [
        [__('provider.check_details'), __('provider.check_details_text'), true,
            $detailsComplete ? ['ok', __('provider.item_complete')] : ['missing', __('provider.item_missing')], null],
        [__('provider.check_phone'), Phone::mask($provider->phone), true,
            $provider->phone_verified_at ? ['ok', __('provider.item_verified')] : ['missing', __('provider.item_unverified')], null],
        [__('provider.check_identity'), $id ? tr_field($id->type, 'name').' · '.$id->maskedNumber() : __('provider.section_identity_text'), true,
            $fromCredential($id, true), $id?->rejection_reason],
        [__('provider.check_address_proof'), $proof?->document?->original_filename ?? __('provider.address_proof_hint'), true,
            $fromCredential($proof?->document ? $proof : null, true), $proof?->rejection_reason],
    ];

    $coveredByCertificate = false;
    foreach ($requirements as $requirement) {
        $match = $provider->credentials->first(fn ($c) => $c->credential_type_id === $requirement->credential_type_id);
        $coveredByCertificate = $coveredByCertificate || ($match && $certificate && $match->is($certificate));
        $items[] = [tr_field($requirement->credentialType, 'name'), __('provider.check_category'), true,
            $fromCredential($match?->document ? $match : null, true), $match?->rejection_reason];
    }

    if (! $coveredByCertificate) {
        $items[] = [__('provider.check_certificate'), $certificate ? tr_field($certificate->type, 'name') : __('provider.check_certificate_text'), false,
            $fromCredential($certificate?->document ? $certificate : null, false), $certificate?->rejection_reason];
    }

    $look = [
        'ok' => ['check-circle', 'bg-success-soft text-forest dark:text-forest-300', 'success'],
        'pending' => ['clock', 'bg-accent-soft text-amber-700 dark:text-amber', 'pending'],
        'action' => ['alert', 'bg-danger-soft text-danger dark:text-danger-300', 'danger'],
        'missing' => ['alert', 'bg-accent-soft text-amber-700 dark:text-amber', 'warning'],
        'none' => ['document', 'bg-surface-2 text-fg-subtle', 'neutral'],
    ];
@endphp
<section class="card overflow-hidden" aria-labelledby="checklist-title">
    <div class="border-b border-line px-5 py-4 sm:px-6">
        <h2 id="checklist-title" class="type-heading text-lg">{{ __('provider.checklist_title') }}</h2>
        <p class="mt-0.5 text-sm text-fg-subtle">{{ __('provider.checklist_intro') }}</p>
    </div>
    <ul class="divide-y divide-line">
        @foreach ($items as [$name, $detail, $required, [$status, $statusLabel], $reason])
            <li class="flex items-start gap-3 px-5 py-4 sm:px-6">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $look[$status][1] }}"><x-ki :name="$look[$status][0]" class="size-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 font-medium">
                        {{ $name }}
                        <span class="rounded-full px-2 py-0.5 text-[0.7rem] font-semibold {{ $required ? 'bg-surface-2 text-fg-muted' : 'bg-surface-2 text-fg-subtle' }}">{{ $required ? __('provider.item_required') : __('provider.item_optional') }}</span>
                    </p>
                    <p class="mt-0.5 text-sm text-fg-subtle" style="overflow-wrap:anywhere">{{ $detail }}</p>
                    @if ($reason && $status === 'action')
                        <p class="mt-1.5 text-sm font-medium text-danger-text">{{ __('provider.doc_reason', ['reason' => $reason]) }}</p>
                    @endif
                </div>
                <x-status-badge size="sm" :tone="$look[$status][2]" :label="$statusLabel" class="shrink-0" />
            </li>
        @endforeach
    </ul>
</section>
