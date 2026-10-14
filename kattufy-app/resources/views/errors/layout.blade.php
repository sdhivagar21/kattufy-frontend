{{-- Shared error page. Kept minimal so it renders even when parts of the app fail. --}}
<x-layouts.base :title="$title" noindex>
    <main id="main" class="relative flex flex-1 items-center justify-center overflow-hidden bg-ink px-4 py-16 text-white dark:bg-ink-950">
        <div class="bg-blueprint absolute inset-0" aria-hidden="true"></div>
        <div class="relative max-w-lg text-center">
            <x-brand tone="dark" class="justify-center" />
            <p class="mt-10 font-mono text-6xl font-bold text-amber">{{ $code }}</p>
            <h1 class="type-display mt-4 text-[1.8rem] text-white ta:text-[1.5rem]">{{ $title }}</h1>
            <p class="mt-3 text-white/75">{{ $message }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ auth()->check() ? \App\Modules\Accounts\Services\AccountHome::url(auth()->user()) : lroute('home') }}" class="btn btn-primary">{{ __('ui.go_home') }}</a>
                <button type="button" onclick="history.back()" class="btn border border-white/25 text-white hover:bg-white/5">{{ __('ui.go_back') }}</button>
            </div>
        </div>
    </main>
</x-layouts.base>
