@props(['title' => null, 'description' => null, 'noindex' => false, 'flash' => true])
{{-- Public pages and directory: charcoal header, content, footer. --}}
<x-layouts.base :title="$title" :description="$description" :noindex="$noindex">
    <x-demo-banner />
    <x-site-header />
    <main id="main" class="flex-1" tabindex="-1">
        @if ($flash && (session('success') || session('status') || session('error')))
            <div class="container-page pt-6"><x-flash /></div>
        @endif
        {{ $slot }}
    </main>
    <x-site-footer />
</x-layouts.base>
