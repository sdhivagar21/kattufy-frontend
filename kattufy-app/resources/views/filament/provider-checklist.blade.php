@php
    $items = app(\App\Modules\Verification\Services\PublishGate::class)->checklist($getRecord());
    $passed = collect($items)->where('passed', true)->count();
@endphp

<div class="space-y-3">
    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $passed }} of {{ count($items) }} done</p>
    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
        <div class="h-full rounded-full {{ $passed === count($items) ? 'bg-success-600' : 'bg-primary-500' }}" style="width: {{ round($passed / max(1, count($items)) * 100) }}%"></div>
    </div>
    <ul class="space-y-2">
        @foreach ($items as $item)
            <li class="flex items-start gap-2 text-sm">
                @if ($item['passed'])
                    <x-filament::icon icon="heroicon-m-check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-success-600 dark:text-success-400" />
                    <span class="text-gray-700 dark:text-gray-200">{{ $item['label'] }}</span>
                @else
                    <x-filament::icon icon="heroicon-m-x-circle" class="mt-0.5 h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500" />
                    <span class="text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
