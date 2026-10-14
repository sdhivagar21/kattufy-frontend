@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('ui.pagination') }}" class="flex items-center justify-between gap-4">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary pointer-events-none opacity-50"><x-ki name="arrow-left" class="size-4" /> {{ __('ui.previous') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary"><x-ki name="arrow-left" class="size-4" /> {{ __('ui.previous') }}</a>
        @endif

        <p class="text-sm text-fg-muted">{{ __('ui.page_of', ['page' => $paginator->currentPage(), 'total' => $paginator->lastPage()]) }}</p>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary">{{ __('ui.next') }} <x-ki name="arrow-right" class="size-4" /></a>
        @else
            <span class="btn btn-secondary pointer-events-none opacity-50">{{ __('ui.next') }} <x-ki name="arrow-right" class="size-4" /></span>
        @endif
    </nav>
@endif
