@use('App\Support\Phone')
@use('App\Support\Mask')
{{-- What the provider submitted, with sensitive values masked. Shared by the status page and dashboard. --}}
@php
    $id = $provider->idCredential();
    $documents = $provider->credentials->filter(fn ($c) => $c->type?->requires_document);
@endphp
<section class="card overflow-hidden" aria-labelledby="summary-title">
    <div class="border-b border-line px-5 py-4 sm:px-6">
        <h2 id="summary-title" class="type-heading text-lg">{{ __('provider.submitted_information') }}</h2>
    </div>
    <div class="grid gap-x-8 gap-y-0 px-5 sm:px-6 md:grid-cols-2">
        <dl class="divide-y divide-line text-[0.95rem]">
            <div class="py-3"><dt class="text-sm text-fg-subtle">{{ __('provider.business_name') }}</dt><dd class="mt-0.5 font-medium">{{ $provider->business_name_en }}</dd></div>
            <div class="py-3"><dt class="text-sm text-fg-subtle">{{ __('provider.category') }}</dt><dd class="mt-0.5 font-medium">{{ $provider->categories->map(fn ($c) => tr_field($c, 'name'))->join(', ') ?: '—' }}</dd></div>
            <div class="py-3"><dt class="text-sm text-fg-subtle">{{ __('provider.business_address') }}</dt><dd class="mt-0.5 font-medium">{{ $provider->address ?: '—' }}</dd></div>
            <div class="py-3"><dt class="text-sm text-fg-subtle">{{ __('provider.pincode') }}</dt>
                <dd class="mt-0.5 font-medium">{{ $provider->pincode ?: '—' }} @if ($provider->locality)<span class="font-normal text-fg-subtle">· {{ tr_field($provider->locality, 'name') }}</span>@endif</dd></div>
        </dl>
        <dl class="divide-y divide-line border-t border-line text-[0.95rem] md:border-t-0">
            <div class="py-3"><dt class="text-sm text-fg-subtle">{{ __('provider.business_phone') }}</dt>
                <dd class="mt-0.5 flex flex-wrap items-center gap-2 font-medium"><span lang="en">{{ Phone::mask($provider->phone) }}</span>
                    @if ($provider->phone_verified_at)<span class="inline-flex items-center gap-1 text-xs font-semibold text-success-text"><x-ki name="check-circle" class="size-3.5" />{{ __('user.verified') }}</span>@endif</dd></div>
            <div class="py-3"><dt class="text-sm text-fg-subtle">{{ __('provider.business_email') }}</dt><dd class="mt-0.5 font-medium" lang="en">{{ Mask::email($provider->email) }}</dd></div>
            <div class="py-3"><dt class="text-sm text-fg-subtle">{{ __('provider.id_type') }}</dt><dd class="mt-0.5 font-medium">{{ $id ? tr_field($id->type, 'name') : '—' }}</dd></div>
            <div class="py-3"><dt class="text-sm text-fg-subtle">{{ __('provider.id_number') }}</dt>
                <dd class="mt-0.5 font-mono font-medium tracking-wider">{{ $id?->maskedNumber() ?: '—' }}</dd>
                <dd class="mt-0.5 text-xs text-fg-subtle">{{ __('provider.id_masked_note') }}</dd></div>
        </dl>
    </div>

    <div class="border-t border-line px-5 py-4 sm:px-6">
        <h3 class="text-sm font-semibold">{{ __('provider.document_status') }}</h3>
        <ul class="mt-3 space-y-2.5">
            @forelse ($documents as $credential)
                <li class="flex flex-wrap items-center gap-x-3 gap-y-2 rounded-control border border-line bg-sunken px-3.5 py-3">
                    <x-ki name="document" class="size-5 text-fg-subtle" />
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ tr_field($credential->type, 'name') }}</p>
                        @if ($credential->document)
                            <a href="{{ $credential->document->temporaryUrl() }}" target="_blank" rel="noopener" class="text-sm text-fg-muted underline-offset-4 hover:underline">{{ $credential->document->original_filename }} · {{ $credential->document->humanSize() }}</a>
                        @else
                            <p class="text-sm text-fg-subtle">{{ __('provider.no_file') }}</p>
                        @endif
                        @if ($credential->rejection_reason)
                            <p class="mt-1 text-sm text-danger-text">{{ $credential->rejection_reason }}</p>
                        @endif
                    </div>
                    <x-status-badge size="sm"
                        :tone="match ($credential->status->value) { 'verified' => 'success', 'rejected', 'expired' => 'danger', default => 'pending' }"
                        :label="$credential->status->label()" />
                </li>
            @empty
                <li class="text-sm text-fg-subtle">{{ __('provider.no_documents') }}</li>
            @endforelse
        </ul>
        <p class="mt-3 text-xs text-fg-subtle">{{ __('provider.documents_private') }}</p>
    </div>
</section>
