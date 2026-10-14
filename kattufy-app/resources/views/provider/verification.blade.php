@use('App\Modules\Verification\Enums\VerificationState')
{{--
    Verification status, read from the same lifecycle records the admin works
    on: progress, checklist, review feedback and history. Nothing here can
    approve or change a submission; "Update and resubmit" opens the existing form.
--}}
@php
    $tone = $state->tone();
    $panel = [
        'pending' => ['ring-amber/40', 'clock', 'bg-accent-soft text-amber-700 dark:text-amber'],
        'success' => ['ring-forest/30', 'check-circle', 'bg-success-soft text-forest dark:text-forest-300'],
        'warning' => ['ring-amber/50', 'alert', 'bg-accent-soft text-amber-700 dark:text-amber'],
        'danger' => ['ring-danger/30', 'x-circle', 'bg-danger-soft text-danger dark:text-danger-300'],
        'neutral' => ['ring-line', 'info', 'bg-surface-2 text-fg-subtle'],
    ][$tone];
    $reference = 'KTF-P-'.str_pad($provider->id, 4, '0', STR_PAD_LEFT);
    $canResubmit = $state->canEditSubmission() && $state !== VerificationState::NeedsUpdate;
@endphp
<x-layouts.dashboard :title="__('provider.verification_title')" area="provider">
    <p class="eyebrow">{{ __('provider.verification_title') }}</p>

    <section class="mt-3 overflow-hidden rounded-panel bg-surface shadow-card ring-1 animate-fade-up {{ $panel[0] }}">
        <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-start sm:p-8">
            <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl {{ $panel[2] }}">
                <x-ki :name="$panel[1]" class="size-7" />
            </span>
            <div class="min-w-0 flex-1">
                <x-status-badge :state="$state" size="lg" />
                <h1 class="type-display mt-3 text-[1.6rem] sm:text-[1.95rem] ta:text-[1.4rem] sm:ta:text-[1.6rem]">{{ __('provider.state_'.$state->value.'_title') }}</h1>
                <p class="mt-2 max-w-2xl text-fg-muted">{{ __('provider.state_'.$state->value.'_text', ['business' => $provider->business_name_en]) }}</p>

                @if ($feedback)
                    <div @class(['mt-5 rounded-control border p-4', 'border-amber/50 bg-accent-soft' => $tone === 'warning', 'border-danger/30 bg-danger-soft' => $tone === 'danger', 'border-line bg-surface-2' => ! in_array($tone, ['warning', 'danger'], true)])>
                        <p class="flex items-center gap-2 text-sm font-semibold"><x-ki name="chat" class="size-4" /> {{ __('provider.feedback_title') }}</p>
                        <p class="mt-1.5 text-fg">“{{ $feedback->reason }}”</p>
                        <p class="mt-1 text-sm text-fg-subtle">{{ __('provider.feedback_when', ['date' => ist($feedback->created_at, 'j F Y')]) }}</p>
                    </div>
                @endif

                <dl class="mt-5 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                    <div><dt class="text-fg-subtle">{{ __('provider.reference') }}</dt><dd class="font-mono font-semibold">{{ $reference }}</dd></div>
                    @if ($submittedAt)
                        <div><dt class="text-fg-subtle">{{ __('provider.submitted_on') }}</dt><dd class="font-semibold">{{ ist($submittedAt, 'j F Y, g:i a') }}</dd></div>
                    @endif
                </dl>

                @if ($state->opensDashboard() || $canResubmit)
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        @if ($canResubmit)
                            <a href="{{ lroute('provider.register') }}" class="btn btn-primary"><x-ki name="refresh" class="size-5" /> {{ __('provider.resubmit') }}</a>
                            <p class="w-full text-sm text-fg-muted sm:w-auto sm:flex-1">{{ __('provider.resubmit_text') }}</p>
                        @endif
                        @if ($state->opensDashboard())
                            <a href="{{ lroute('provider.dashboard') }}" class="btn btn-primary">{{ __('provider.open_dashboard') }} <x-ki name="arrow-right" class="size-5" /></a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="border-t border-line bg-sunken px-6 py-5 sm:px-8">
            @include('provider.partials.verification-steps', ['state' => $state, 'class' => 'max-w-2xl'])
        </div>

        @if ($state === VerificationState::PendingReview)
            <div class="border-t border-amber/30 bg-surface px-6 py-4 text-sm text-fg-muted sm:px-8">
                <x-ki name="info" class="mr-1 inline size-4 align-[-3px]" /> {{ __('provider.pending_next') }}
                @if (config('kattufy.demo_mode') && ! app()->isProduction())
                    <span class="mt-1 block text-fg-subtle">{{ __('provider.pending_demo_hint') }}</span>
                @endif
            </div>
        @endif
    </section>

    <div class="mt-8 grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="min-w-0 space-y-8">
            @include('provider.partials.document-checklist')
            @include('provider.partials.submission-summary')
        </div>

        <section class="card card-pad h-fit" aria-labelledby="history-title">
            <h2 id="history-title" class="font-semibold">{{ __('provider.history') }}</h2>
            <ol class="relative mt-4 space-y-5 border-l border-line pl-5">
                @foreach ($history as $event)
                    <li class="relative">
                        <span class="absolute top-1.5 -left-[1.6rem] size-2.5 rounded-full ring-4 ring-surface {{ $event->to_status === 'published' ? 'bg-forest' : ($event->reason ? 'bg-amber' : 'bg-line-strong') }}"></span>
                        <p class="text-sm font-medium">{{ __('provider.history_'.$event->to_status) }}</p>
                        <p class="text-xs text-fg-subtle">{{ ist($event->created_at, 'j M Y, g:i a') }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-layouts.dashboard>
