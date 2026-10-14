{{-- One Premium plan section on its own page, with Save / Cancel. Inside a listingPlanner (mode "section"). --}}
<header class="mb-6 max-w-2xl sm:mb-8">
    <p class="eyebrow">{{ __('plans.premium.name') }}</p>
    <h1 class="type-display mt-2 text-[1.75rem] sm:text-[2.1rem] ta:text-[1.45rem] sm:ta:text-[1.7rem]">{{ $heading }}</h1>
    <p class="mt-2 text-fg-muted">{{ $intro }}</p>
</header>

{{-- Not Premium: explain instead of showing the tools --}}
<div x-show="plan !== 'premium'">
    <x-empty-state icon="sparkle" :title="__('plans.premium_only')" :text="__('plans.premium_only_text')">
        <x-slot:actions><a href="{{ lroute('provider.plan') }}" class="btn btn-primary">{{ __('plans.choose_plan') }}</a></x-slot:actions>
    </x-empty-state>
</div>

<div x-cloak x-show="plan === 'premium'">
    <p x-show="notice" x-text="notice" class="mb-5 flex items-center gap-2 rounded-control bg-success-soft px-4 py-3 text-sm font-medium text-success-text" role="status"></p>

    <form @submit.prevent="save()" novalidate>
        @include($partial)

        <x-alert tone="info" class="mt-6">{{ __('plans.device_note') }}</x-alert>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center">
            <p class="text-sm text-fg-subtle sm:mr-auto" x-show="savedAtText">{{ __('plans.last_saved') }}: <span x-text="savedAtText"></span></p>
            <button type="button" class="btn btn-ghost" @click="cancel()">{{ __('plans.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-lg"><x-ki name="check-circle" class="size-5" /> {{ __('plans.save_changes') }}</button>
        </div>
    </form>
</div>

{{-- Discard confirmation --}}
<dialog x-effect="if (confirmDiscard && !$el.open) $el.showModal(); if (!confirmDiscard && $el.open) $el.close()" @close="confirmDiscard = false"
        aria-labelledby="discard-title" class="m-auto w-[min(26rem,calc(100vw-2rem))] rounded-panel bg-surface p-0 text-fg shadow-raised backdrop:bg-ink/60">
    <div class="p-6">
        <h2 id="discard-title" class="type-heading text-lg">{{ __('plans.discard_title') }}</h2>
        <p class="mt-2 text-fg-muted">{{ __('plans.discard_text') }}</p>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" x-ref="keepEditing" class="btn btn-secondary" @click="confirmDiscard = false">{{ __('plans.keep_editing') }}</button>
            <button type="button" class="btn btn-danger" @click="discard()">{{ __('plans.discard') }}</button>
        </div>
    </div>
</dialog>
