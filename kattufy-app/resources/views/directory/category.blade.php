{{-- Category landing, e.g. /architects/. --}}
<x-layouts.site :title="tr_field($category, 'name')" :description="tr_field($category, 'meta_description') ?? tr_field($category, 'description')" :noindex="! $live">
    <section class="relative overflow-hidden border-b border-line bg-surface">
        <div class="bg-blueprint-light absolute inset-0 opacity-60" aria-hidden="true"></div>
        <div class="container-page relative py-10 sm:py-14">
            @include('directory.partials.breadcrumbs', ['crumbs' => [__('ui.home') => \App\Modules\Accounts\Services\AccountHome::url(auth()->user()), tr_field($category->group, 'name') => lroute('directory.section', $category->group->slug), tr_field($category, 'name') => null]])
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <h1 class="type-display text-[2rem] sm:text-[2.6rem] ta:text-[1.65rem] sm:ta:text-[2rem]">{{ tr_field($category, 'name') }}</h1>
                <x-status-badge :tone="$live ? 'success' : 'neutral'" :label="$live ? __('directory.live_in', ['city' => tr_field($city, 'name')]) : __('directory.coming_soon')" />
            </div>
            <p class="mt-3 max-w-2xl text-lg text-fg-muted" @if (app()->getLocale() === 'ta' && ! $category->description_ta) lang="en" @endif>{{ tr_field($category, 'description') }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                @if ($live)
                    <a href="{{ lroute('directory.child', [$category->slug, $city->slug]) }}" class="btn btn-primary btn-lg">{{ __('directory.browse_in', ['category' => tr_field($category, 'name'), 'city' => tr_field($city, 'name')]) }} <x-ki name="arrow-right" class="size-5" /></a>
                @else
                    <a href="{{ lroute('enquiry') }}?category={{ $category->slug }}&source=coming_soon" class="btn btn-primary btn-lg"><x-ki name="chat" class="size-5" /> {{ __('ui.send_enquiry') }}</a>
                @endif
            </div>
        </div>
    </section>

    <div class="container-page space-y-10 py-10">
        @if ($preview->isNotEmpty())
            <section>
                <h2 class="type-heading text-[1.35rem]">{{ __('directory.top_listed') }}</h2>
                <p class="mt-1 text-fg-muted">{{ __('directory.ranking_short') }} <a href="{{ lroute('ranking') }}" class="link">{{ __('ui.how_listings_are_ordered') }}</a></p>
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($preview as $provider)
                        <x-provider-card :provider="$provider" :category="$category" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($category->services->isNotEmpty())
            <section>
                <h2 class="type-heading text-lg">{{ __('directory.specialisations') }}</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($category->services as $service)
                        <li><a href="{{ lroute('search') }}?category={{ $category->slug }}&q={{ urlencode($service->name_en) }}" class="inline-flex min-h-9 items-center rounded-full border border-line bg-surface px-3.5 text-sm hover:border-line-strong hover:bg-surface-2">{{ tr_field($service, 'name') }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @unless ($live)
            <x-empty-state icon="clock" :title="__('directory.coming_soon_title')" :text="__('directory.coming_soon_text', ['city' => tr_field($city, 'name')])" />
        @endunless
    </div>
</x-layouts.site>
