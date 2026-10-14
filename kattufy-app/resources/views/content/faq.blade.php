<x-layouts.site :title="__('ui.faq')">
    <div class="container-page py-10 sm:py-14">
        <div class="mx-auto max-w-3xl">
            <p class="eyebrow">{{ __('ui.help') }}</p>
            <h1 class="type-display mt-2 text-[2rem] sm:text-[2.5rem] ta:text-[1.6rem]">{{ __('ui.faq') }}</h1>
            <p class="mt-3 text-fg-muted">{{ __('user.faq_intro') }}</p>
            <x-faq-list :items="$faqs" class="mt-8" />
        </div>
    </div>
</x-layouts.site>
