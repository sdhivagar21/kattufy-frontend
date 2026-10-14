@props(['title' => null, 'variant' => 'member', 'wide' => false])
{{--
    Sign-in, registration, code entry and role choice. One charcoal page with
    the blueprint grid behind both columns: the brand story on the left (large
    screens) and the form panel on the right. The panel follows the light/dark
    theme; the page around it stays charcoal. On phones the brand bar sits on
    top, the form comes first and the trust points follow it.
--}}
@php
    $points = ['shield' => __('auth.panel_point_checked'), 'lock' => __('auth.panel_point_private'), 'phone' => __('auth.panel_point_otp')];
@endphp
<x-layouts.base :title="$title" noindex body-class="bg-ink dark:bg-ink-950">
    <x-demo-banner />
    <div class="relative isolate flex flex-1 flex-col overflow-hidden bg-ink text-white dark:bg-ink-950">
        {{-- One grid across the whole page, so the lines run through both columns. --}}
        <div class="bg-blueprint pointer-events-none absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="pointer-events-none absolute top-1/4 -left-48 -z-10 size-[30rem] rounded-full bg-amber/[0.07] blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-40 -bottom-48 -z-10 size-[32rem] rounded-full bg-amber/[0.05] blur-3xl" aria-hidden="true"></div>

        <div class="grid w-full flex-1 grid-cols-1 lg:grid-cols-2">
            {{-- Brand column (large screens) --}}
            <aside class="hidden min-h-full flex-col px-10 py-9 lg:flex xl:px-16 xl:py-11">
                <x-brand :href="lroute('home')" tone="dark" class="self-start" />

                <div class="my-auto w-full max-w-[34rem] py-14 animate-fade-up">
                    <div class="rule-amber"></div>
                    @if ($variant === 'admin')
                        <h2 class="type-display mt-7 max-w-[16ch] text-[2.3rem] leading-[1.1] text-white xl:text-[2.6rem] ta:max-w-none ta:text-[1.7rem]">{{ __('auth.admin_panel_title') }}</h2>
                        <p class="mt-5 max-w-[32rem] text-lg leading-relaxed text-white/72">{{ __('auth.admin_panel_intro') }}</p>
                        <ol class="mt-10 space-y-5">
                            @foreach ([__('auth.admin_step_details'), __('auth.admin_step_mobile'), __('auth.admin_step_email')] as $i => $step)
                                <li class="grid grid-cols-[2.5rem_minmax(0,1fr)] items-center gap-4">
                                    <span class="flex size-10 items-center justify-center rounded-xl border border-amber/50 text-sm font-bold text-amber">{{ $i + 1 }}</span>
                                    <span class="text-white/85">{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                        <p class="mt-10 flex items-center gap-2 text-sm text-white/60"><x-ki name="lock" class="size-4" /> {{ __('auth.admin_never_from_member') }}</p>
                    @else
                        <h2 class="type-display mt-7 max-w-[15ch] text-[2.5rem] leading-[1.08] text-white xl:text-[2.85rem] ta:max-w-[20ch] ta:text-[1.8rem] ta:leading-snug">{{ __('auth.panel_title') }}</h2>
                        <p class="mt-5 max-w-[32rem] text-lg leading-relaxed text-white/72">{{ __('auth.panel_intro') }}</p>
                        <ul class="mt-10 space-y-5">
                            @foreach ($points as $icon => $point)
                                <li class="grid grid-cols-[2.5rem_minmax(0,1fr)] items-center gap-4">
                                    <span class="flex size-10 items-center justify-center rounded-xl bg-white/[0.06] text-amber ring-1 ring-white/10"><x-ki :name="$icon" class="size-5" /></span>
                                    <span class="text-white/85">{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <p class="text-sm text-white/50">© {{ date('Y') }} Kattufy · {{ __('ui.location') }}</p>
            </aside>

            {{-- Form column --}}
            <div class="flex min-h-full min-w-0 flex-col lg:border-l lg:border-white/[0.07] lg:bg-black/[0.14]">
                <div class="flex items-center gap-2 px-4 py-3 sm:px-6 lg:justify-end lg:px-10 lg:py-4">
                    <x-brand :href="lroute('home')" tone="dark" size="sm" class="mr-auto lg:hidden" />
                    <x-lang-switch tone="dark" />
                    <x-theme-toggle tone="dark" />
                </div>

                <main id="main" tabindex="-1" class="flex flex-1 items-start justify-center px-4 pt-2 pb-10 sm:px-6 sm:pt-6 lg:items-center lg:px-10 lg:pt-0 lg:pb-8">
                    <div @class(['w-full animate-fade-up', 'max-w-[29rem]' => ! $wide, 'max-w-3xl' => $wide])>
                        <div class="rounded-[1.25rem] border border-white/10 bg-surface p-6 text-fg shadow-[0_30px_80px_-32px_rgb(0_0_0/0.7)] sm:px-8 sm:py-7 dark:border-white/[0.08] dark:bg-ink-900/90 dark:backdrop-blur">
                            <x-flash />
                            {{ $slot }}
                        </div>

                        {{-- Phones and tablets: the trust points follow the form. --}}
                        @if ($variant !== 'admin')
                            <ul class="mt-8 space-y-3.5 px-1 lg:hidden">
                                @foreach ($points as $icon => $point)
                                    <li class="grid grid-cols-[2.25rem_minmax(0,1fr)] items-center gap-3 text-[0.95rem] text-white/80">
                                        <span class="flex size-9 items-center justify-center rounded-lg bg-white/[0.06] text-amber ring-1 ring-white/10"><x-ki :name="$icon" class="size-[1.1rem]" /></span>
                                        <span>{{ $point }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </main>

                <footer class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 px-4 pb-6 text-center text-sm text-white/55 sm:px-6">
                    <span class="basis-full lg:hidden">© {{ date('Y') }} Kattufy · {{ __('ui.location') }}</span>
                    <a href="{{ lroute('privacy') }}" class="rounded hover:text-white">{{ __('ui.privacy') }}</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ lroute('terms') }}" class="rounded hover:text-white">{{ __('ui.terms') }}</a>
                </footer>
            </div>
        </div>
    </div>
</x-layouts.base>
