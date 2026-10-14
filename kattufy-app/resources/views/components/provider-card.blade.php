@props(['provider', 'category' => null])
{{-- One provider in a listing. No contact numbers here, ever (Architecture §6A). --}}
@php
    $category ??= $provider->primaryCategory();
    // Listing plan decides what a card may show (no stored plan yet = existing card).
    $plan = \App\Support\ListingPlans::of($provider);
    $image = \App\Support\ListingPlans::shows($plan, 'photos') ? $provider->portfolioItems->first()?->image : null;
    $showDescription = \App\Support\ListingPlans::shows($plan, 'description');
    $url = lroute('directory.child', [$category->slug, $provider->slug]);
    [$description, $descLang] = app()->getLocale() === 'ta' && $provider->description_ta_status === 'reviewed' && $provider->description_ta
        ? [$provider->description_ta, 'ta'] : [$provider->description_en, 'en'];
@endphp
<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-panel border bg-surface '.($plan === 'premium' ? 'border-amber/50' : 'border-line').' shadow-card transition-[box-shadow,transform,border-color] duration-200 hover:-translate-y-0.5 hover:border-line-strong hover:shadow-raised']) }}>
    <div class="relative aspect-[16/9] overflow-hidden bg-surface-2">
        @if ($image)
            <img src="{{ $image->publicUrl(400) }}" srcset="{{ $image->publicUrl(400) }} 400w, {{ $image->publicUrl(800) }} 800w" sizes="(min-width: 1024px) 380px, 100vw"
                 alt="{{ app()->getLocale() === 'ta' && $image->alt_ta ? $image->alt_ta : $image->alt_en }}" loading="lazy" width="800" height="450"
                 class="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03]">
        @else
            <div class="bg-blueprint-light flex size-full items-center justify-center">
                <span class="flex size-16 items-center justify-center rounded-panel bg-ink text-xl font-bold text-amber" style="font-stretch:116%" lang="en">{{ $provider->initials() }}</span>
            </div>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <h3 class="type-heading text-[1.1rem]">
            <a href="{{ $url }}" class="after:absolute after:inset-0 focus-visible:outline-none">{{ $provider->name() }}</a>
        </h3>
        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-fg-subtle">
            @if ($provider->subtype)<span>{{ tr_field($provider->subtype, 'name') }}</span>@endif
            @if ($provider->locality)
                <span class="inline-flex items-center gap-1"><x-ki name="map-pin" class="size-4" />{{ tr_field($provider->locality, 'name') }}</span>
            @endif
        </p>
        @if ($showDescription && $description)
            <p class="mt-3 line-clamp-2 text-[0.95rem] leading-relaxed text-fg-muted" lang="{{ $descLang }}">{{ $description }}</p>
        @endif
        @if ($category)
            <p class="mt-2 text-xs font-semibold tracking-wide text-accent-text uppercase ta:normal-case">{{ tr_field($category, 'name') }}</p>
        @endif
        <ul class="mt-auto flex flex-wrap gap-1.5 pt-4" aria-label="{{ __('directory.checks') }}">
            @if ($provider->isPhoneVerified())
                <li class="inline-flex items-center gap-1 rounded-tag bg-success-soft px-2 py-1 text-xs font-semibold text-success-text"><x-ki name="phone" class="size-3.5" />{{ __('directory.badge_phone') }}</li>
            @endif
            @if ($provider->hasCredentialsChecked())
                <li class="inline-flex items-center gap-1 rounded-tag bg-success-soft px-2 py-1 text-xs font-semibold text-success-text"><x-ki name="shield" class="size-3.5" />{{ __('directory.badge_credentials') }}</li>
            @endif
            @if ($provider->hasPortfolioShown())
                <li class="inline-flex items-center gap-1 rounded-tag bg-surface-2 px-2 py-1 text-xs font-semibold text-fg-muted"><x-ki name="sparkle" class="size-3.5" />{{ __('directory.badge_portfolio') }}</li>
            @endif
        </ul>
        <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-fg group-hover:text-accent-text" aria-hidden="true">
            {{ __('directory.view_profile') }} <x-ki name="arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" />
        </span>
    </div>
</article>
