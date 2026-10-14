{{-- Build-stage hub, e.g. /building-a-house/. --}}
<x-layouts.site :title="tr_field($group, 'name')" :description="tr_field($group, 'description')">
    <section class="border-b border-line bg-surface">
        <div class="container-page py-10 sm:py-14">
            @include('directory.partials.breadcrumbs', ['crumbs' => [__('ui.home') => \App\Modules\Accounts\Services\AccountHome::url(auth()->user()), tr_field($group, 'name') => null]])
            <h1 class="type-display mt-4 text-[2rem] sm:text-[2.6rem] ta:text-[1.65rem] sm:ta:text-[2rem]">{{ tr_field($group, 'name') }}</h1>
            <p class="mt-3 max-w-2xl text-lg text-fg-muted">{{ tr_field($group, 'description') }}</p>
        </div>
    </section>
    <div class="container-page py-10">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($group->categories as $category)
                @php $pivot = $category->cities->first()?->pivot; $live = $pivot?->status === 'live'; @endphp
                <a href="{{ lroute('directory.section', $category->slug) }}" class="card group flex flex-col p-6 transition-[box-shadow,border-color,transform] hover:-translate-y-0.5 hover:border-line-strong hover:shadow-raised">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="type-heading text-[1.15rem]">{{ tr_field($category, 'name') }}</h2>
                        @if ($live)
                            <x-status-badge tone="success" size="sm" :label="__('directory.live')" />
                        @else
                            <x-status-badge tone="neutral" size="sm" :label="__('directory.coming_soon')" />
                        @endif
                    </div>
                    <p class="mt-2 line-clamp-3 text-[0.95rem] text-fg-muted">{{ tr_field($category, 'description') }}</p>
                    <p class="mt-auto pt-4 text-sm font-semibold text-accent-text">
                        {{ $live ? trans_choice('directory.provider_count', $pivot->published_provider_count, ['count' => $pivot->published_provider_count]) : __('directory.send_enquiry_instead') }}
                        <x-ki name="arrow-right" class="ml-1 inline size-4 transition-transform group-hover:translate-x-0.5" />
                    </p>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.site>
