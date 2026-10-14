<x-layouts.dashboard :title="__('ui.faq')" area="user" :heading="__('ui.faq')" :subheading="__('user.faq_intro')">
    <x-faq-list :items="$faqs" />
    <div class="card card-pad mt-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold">{{ __('user.still_need_help') }}</h2>
            <p class="mt-1 text-sm text-fg-muted">{{ __('user.still_need_help_text') }}</p>
        </div>
        <a href="{{ lroute('enquiry') }}" class="btn btn-secondary">{{ __('ui.send_enquiry') }}</a>
    </div>
</x-layouts.dashboard>
