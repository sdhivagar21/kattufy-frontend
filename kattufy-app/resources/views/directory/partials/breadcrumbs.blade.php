<nav aria-label="{{ __('ui.breadcrumb') }}" class="text-sm text-fg-subtle">
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($crumbs as $label => $url)
            <li class="flex items-center gap-1.5">
                @if (! $loop->first)<x-ki name="chevron-right" class="size-3.5" />@endif
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-fg hover:underline">{{ $label }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" class="text-fg-muted" @endif>{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
