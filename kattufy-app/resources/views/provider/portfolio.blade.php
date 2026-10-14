{{--
    Services and previous projects (Premium). The same section and the same
    browser draft as the Premium plan form; saved in this browser only.
--}}
<x-layouts.dashboard :title="__('plans.services_title')" area="provider">
    <div @include('provider.partials.planner-data', ['mode' => 'section', 'section' => 'portfolio']) class="animate-fade-up">
        @include('provider.partials.planner.section-page', [
            'heading' => __('plans.services_title'),
            'intro' => __('plans.services_intro'),
            'partial' => 'provider.partials.planner.portfolio',
        ])
    </div>
</x-layouts.dashboard>
