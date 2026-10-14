@if (config('kattufy.demo_mode') && ! app()->isProduction())
    <span class="ms-2 hidden items-center gap-1.5 rounded-md bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-300 sm:inline-flex dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30">
        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
        Demo mode · sample data, no real SMS or email
    </span>
@endif
