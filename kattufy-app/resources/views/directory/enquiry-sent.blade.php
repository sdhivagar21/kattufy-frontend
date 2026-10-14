<x-layouts.site :title="__('directory.enquiry_sent_title')" noindex>
    <div class="container-page py-14 sm:py-20">
        <div class="mx-auto max-w-xl text-center">
            <span class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-success-soft text-success-text"><x-ki name="check-circle" class="size-8" /></span>
            <h1 class="type-display mt-6 text-[1.9rem] ta:text-[1.55rem]">{{ __('directory.enquiry_sent_title') }}</h1>
            <p class="mt-3 text-fg-muted">{{ __('directory.enquiry_sent_text') }}</p>
            <p class="mt-6 inline-flex items-center gap-2 rounded-control bg-surface-2 px-4 py-2 text-sm">{{ __('provider.reference') }} <span class="font-mono font-bold">{{ $enquiry->reference_no }}</span></p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ \App\Modules\Accounts\Services\AccountHome::url(auth()->user()) }}" class="btn btn-primary">{{ __('ui.my_dashboard') }}</a>
                <a href="{{ lroute('search') }}" class="btn btn-secondary">{{ __('ui.search') }}</a>
            </div>
        </div>
    </div>
</x-layouts.site>
