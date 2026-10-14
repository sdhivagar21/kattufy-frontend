{{-- How a business gets listed. Self-registration itself starts at /register/ → Provider. --}}
<x-layouts.site :title="__('content.join_title')">
    <section class="relative overflow-hidden bg-ink text-white dark:bg-ink-950">
        <div class="bg-blueprint absolute inset-0" aria-hidden="true"></div>
        <div class="container-page relative py-14 sm:py-20">
            <p class="eyebrow text-amber">{{ __('content.join_eyebrow') }}</p>
            <h1 class="type-display mt-3 max-w-3xl text-[2.1rem] text-white sm:text-[2.8rem] ta:text-[1.7rem] sm:ta:text-[2.1rem]">{{ __('content.join_title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg text-white/75">{{ __('content.join_intro') }}</p>
        </div>
    </section>
    <div class="container-page py-12">
        <ol class="grid gap-5 md:grid-cols-3">
            @foreach (['register', 'details', 'review'] as $i => $step)
                <li class="card card-pad">
                    <span class="flex size-9 items-center justify-center rounded-full bg-amber font-bold text-ink">{{ $i + 1 }}</span>
                    <h2 class="type-heading mt-4 text-lg">{{ __('content.join_'.$step.'_title') }}</h2>
                    <p class="mt-2 text-fg-muted">{{ __('content.join_'.$step.'_text') }}</p>
                </li>
            @endforeach
        </ol>
        <x-alert tone="info" class="mt-8">{{ __('content.join_one_role') }}</x-alert>
    </div>
</x-layouts.site>
