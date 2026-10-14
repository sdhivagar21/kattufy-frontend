<x-layouts.site :title="__('directory.search_title')" noindex>
    <section class="border-b border-line bg-surface">
        <div class="container-page py-8 sm:py-10">
            <h1 class="type-display text-[1.9rem] sm:text-[2.3rem] ta:text-[1.55rem]">{{ __('directory.search_title') }}</h1>
            <form method="GET" action="{{ lroute('search') }}" role="search" class="mt-5 grid gap-3 md:grid-cols-[1.3fr_1fr_1fr_auto] md:items-end">
                <div>
                    <label for="q" class="field-label">{{ __('directory.what_do_you_need') }}</label>
                    <input id="q" name="q" type="search" value="{{ $query }}" maxlength="100" placeholder="{{ __('directory.search_placeholder') }}" class="input">
                </div>
                <div>
                    <label for="category" class="field-label">{{ __('directory.service') }}</label>
                    <select id="category" name="category" class="input">
                        <option value="">{{ __('directory.any_service') }}</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->slug }}" @selected($category?->id === $c->id)>{{ tr_field($c, 'name') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="area" class="field-label">{{ __('directory.area_or_pin') }}</label>
                    <input id="area" name="area" type="text" list="search-areas" value="{{ $area }}" maxlength="100" placeholder="{{ __('directory.area_placeholder') }}" class="input" @if ($areaNotFound) aria-invalid="true" aria-describedby="area-error" @endif>
                    <datalist id="search-areas">@foreach ($localities as $l)<option value="{{ tr_field($l, 'name') }}"></option>@endforeach</datalist>
                </div>
                <button type="submit" class="btn btn-primary min-h-12"><x-ki name="search" /> {{ __('ui.search') }}</button>
            </form>
            @if ($areaNotFound)
                <p id="area-error" class="field-error" role="alert"><x-ki name="alert" class="mt-0.5 size-4" /> {{ __('directory.area_not_found') }}</p>
            @endif
        </div>
    </section>

    <div class="container-page py-10">
        <p class="mb-5 text-fg-muted" aria-live="polite">
            {{ trans_choice('directory.results_count', $results->total(), ['count' => $results->total()]) }}
            @if ($locality) · {{ __('directory.serving', ['area' => tr_field($locality, 'name')]) }} @endif
        </p>

        @if ($results->isEmpty())
            <x-empty-state icon="search" :title="__('directory.no_results_title')" :text="__('directory.no_results_text')">
                <x-slot:actions>
                    <a href="{{ lroute('enquiry') }}?source=no_results{{ $category ? '&category='.$category->slug : '' }}" class="btn btn-primary"><x-ki name="chat" class="size-5" /> {{ __('ui.send_enquiry') }}</a>
                </x-slot:actions>
            </x-empty-state>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($results as $provider)
                    <x-provider-card :provider="$provider" :category="$category && $provider->categories->contains($category) ? $category : null" />
                @endforeach
            </div>
            <div class="mt-10">{{ $results->links('directory.partials.pagination') }}</div>
        @endif
    </div>
</x-layouts.site>
