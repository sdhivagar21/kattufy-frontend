{{-- Demo builds only; hidden in production even if demo mode is left on. --}}
@if (config('kattufy.demo_mode') && ! app()->isProduction())
    <div class="relative z-40 bg-amber text-ink">
        <div class="container-page flex flex-wrap items-center justify-center gap-x-3 gap-y-0.5 py-1.5 text-center text-[0.8rem] font-semibold sm:text-sm">
            <span class="inline-flex items-center gap-1.5">
                <x-ki name="info" class="size-4" />
                {{ __('ui.demo_banner') }}
            </span>
            <span class="font-medium opacity-80">{{ __('ui.demo_banner_detail') }}</span>
        </div>
    </div>
@endif
