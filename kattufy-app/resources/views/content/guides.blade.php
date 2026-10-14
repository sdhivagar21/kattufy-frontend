<x-layouts.site :title="__('ui.guides')">
    <div class="container-page py-10 sm:py-14">
        <p class="eyebrow">{{ __('ui.guides') }}</p>
        <h1 class="type-display mt-2 text-[2rem] sm:text-[2.5rem] ta:text-[1.6rem]">{{ __('content.guides_heading') }}</h1>
        <p class="mt-3 max-w-2xl text-fg-muted">{{ __('content.guides_intro') }}</p>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($guides as $guide)
                @php [$title, $lang] = $guide->titleFor(app()->getLocale()); @endphp
                <a href="{{ lroute('guides.show', $guide->slug) }}" class="card group flex flex-col p-6 transition-[box-shadow,transform] hover:-translate-y-0.5 hover:shadow-raised">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-accent-soft text-amber-700 dark:text-amber"><x-ki name="document" /></span>
                    <h2 class="type-heading mt-4 text-[1.1rem]" lang="{{ $lang }}">{{ $title }}</h2>
                    <span class="mt-auto pt-4 text-sm font-semibold text-accent-text">{{ __('content.read_guide') }} <x-ki name="arrow-right" class="ml-1 inline size-4 transition-transform group-hover:translate-x-0.5" /></span>
                </a>
            @empty
                <x-empty-state class="sm:col-span-2 lg:col-span-3" icon="document" :title="__('content.no_guides')" />
            @endforelse
        </div>
    </div>
</x-layouts.site>
