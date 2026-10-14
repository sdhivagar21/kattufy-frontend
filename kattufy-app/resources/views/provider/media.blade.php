{{--
    Business photographs, project photographs and project videos (Premium).
    The same section as the Premium plan form. Files are checked and previewed
    in this tab only: upload storage is not connected, nothing is sent.
--}}
<x-layouts.dashboard :title="__('plans.media_title')" area="provider">
    <div @include('provider.partials.planner-data', ['mode' => 'section', 'section' => 'media']) class="animate-fade-up">
        @include('provider.partials.planner.section-page', [
            'heading' => __('plans.media_title'),
            'intro' => __('plans.media_intro'),
            'partial' => 'provider.partials.planner.media',
        ])
    </div>
</x-layouts.dashboard>
