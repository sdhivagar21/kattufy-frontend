@props(['items' => []])
{{-- Accessible accordion: one question open at a time, smooth expand. --}}
<div x-data="{ open: null }" {{ $attributes->merge(['class' => 'divide-y divide-line overflow-hidden rounded-panel border border-line bg-surface shadow-card']) }}>
    @foreach ($items as $i => $item)
        <div>
            <h3>
                <button type="button" id="faq-q-{{ $i }}" aria-controls="faq-a-{{ $i }}"
                        @click="open = open === {{ $i }} ? null : {{ $i }}"
                        :aria-expanded="(open === {{ $i }}).toString()"
                        class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left text-[1.02rem] font-semibold text-fg transition-colors hover:bg-surface-2 sm:px-6">
                    <span>{{ $item['q'] }}</span>
                    <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-surface-2 text-fg-muted transition-transform duration-200" :class="open === {{ $i }} && 'rotate-180 bg-amber text-ink'">
                        <x-ki name="chevron-down" class="size-4" />
                    </span>
                </button>
            </h3>
            <div id="faq-a-{{ $i }}" role="region" aria-labelledby="faq-q-{{ $i }}" x-cloak x-show="open === {{ $i }}" x-collapse.duration.250ms>
                <p class="px-5 pb-5 leading-relaxed text-fg-muted sm:px-6">{{ $item['a'] }}</p>
            </div>
        </div>
    @endforeach
</div>
