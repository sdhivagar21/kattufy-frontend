{{-- /architects/chennai/ and /architects/chennai/velachery/ --}}
@php
    $place = $locality ? tr_field($locality, 'name') : tr_field($city, 'name');
    $title = __('directory.listing_title', ['category' => tr_field($category, 'name'), 'place' => $place]);
@endphp
<x-layouts.site :title="$title">
    <section class="border-b border-line bg-surface">
        <div class="container-page py-8 sm:py-10">
            @include('directory.partials.breadcrumbs', ['crumbs' => array_filter([
                __('ui.home') => \App\Modules\Accounts\Services\AccountHome::url(auth()->user()),
                tr_field($category, 'name') => lroute('directory.section', $category->slug),
                tr_field($city, 'name') => $locality ? lroute('directory.child', [$category->slug, $city->slug]) : null,
                $locality ? tr_field($locality, 'name') : '' => null,
            ], fn ($v, $k) => $k !== '', ARRAY_FILTER_USE_BOTH)])
            <h1 class="type-display mt-4 text-[1.9rem] sm:text-[2.4rem] ta:text-[1.55rem] sm:ta:text-[1.9rem]">{{ $title }}</h1>
            <p class="mt-2 text-fg-muted">{{ trans_choice('directory.results_count', $results->total(), ['count' => $results->total()]) }} · {{ __('directory.ranking_short') }} <a href="{{ lroute('ranking') }}" class="link">{{ __('ui.how_listings_are_ordered') }}</a></p>

            @if ($localities->isNotEmpty())
                <div class="mt-5 -mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:thin]" aria-label="{{ __('directory.filter_area') }}">
                    <a href="{{ lroute('directory.child', [$category->slug, $city->slug]) }}"
                       @class(['inline-flex min-h-10 shrink-0 items-center rounded-full border px-4 text-sm font-medium', 'border-ink bg-ink text-white dark:border-amber dark:bg-amber dark:text-ink' => ! $locality, 'border-line bg-surface hover:bg-surface-2' => $locality])>{{ __('directory.all_areas') }}</a>
                    @foreach ($localities as $item)
                        <a href="{{ lroute('directory.locality', [$category->slug, $city->slug, $item->slug]) }}"
                           @class(['inline-flex min-h-10 shrink-0 items-center rounded-full border px-4 text-sm font-medium', 'border-ink bg-ink text-white dark:border-amber dark:bg-amber dark:text-ink' => $locality?->id === $item->id, 'border-line bg-surface hover:bg-surface-2' => $locality?->id !== $item->id])>{{ tr_field($item, 'name') }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <div class="container-page py-10">
        @if ($results->isEmpty())
            <x-empty-state icon="search" :title="__('directory.no_results_title')" :text="__('directory.no_results_text')">
                <x-slot:actions>
                    <a href="{{ lroute('enquiry') }}?category={{ $category->slug }}&source=no_results" class="btn btn-primary">{{ __('ui.send_enquiry') }}</a>
                </x-slot:actions>
            </x-empty-state>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($results as $provider)
                    <x-provider-card :provider="$provider" :category="$category" />
                @endforeach
            </div>
            <div class="mt-10">{{ $results->links('directory.partials.pagination') }}</div>
        @endif
    </div>
</x-layouts.site>
