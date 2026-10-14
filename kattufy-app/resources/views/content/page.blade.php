{{-- Content page rendered from Markdown (raw HTML is escaped). English shown, marked lang="en", where Tamil is not reviewed yet. --}}
<x-layouts.site :title="$title">
    <article class="container-page py-10 sm:py-14">
        <div class="mx-auto max-w-3xl">
            @if ($page->page_type === 'guide')
                <a href="{{ lroute('guides') }}" class="inline-flex items-center gap-1.5 text-sm text-fg-subtle hover:text-fg"><x-ki name="arrow-left" class="size-4" /> {{ __('ui.guides') }}</a>
            @endif
            <div class="rule-amber mt-5"></div>
            <h1 class="type-display mt-5 text-[2rem] sm:text-[2.5rem] ta:text-[1.6rem] sm:ta:text-[1.95rem]" lang="{{ $titleLang }}">{{ $title }}</h1>
            @if (app()->getLocale() === 'ta' && $bodyLang === 'en')
                <p class="mt-4 text-sm text-fg-subtle">{{ __('directory.tamil_not_available') }}</p>
            @endif
            <div class="prose-kattufy mt-8" lang="{{ $bodyLang }}">{!! $html !!}</div>
            @if ($page->updated_at)
                <p class="mt-10 border-t border-line pt-4 text-sm text-fg-subtle">{{ __('ui.last_updated', ['date' => ist($page->updated_at, 'j F Y')]) }}</p>
            @endif
        </div>
    </article>
</x-layouts.site>
