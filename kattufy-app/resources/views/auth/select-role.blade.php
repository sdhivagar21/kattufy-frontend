{{-- "How would you like to continue?" — User or Provider, chosen once. --}}
<x-layouts.auth :title="__('auth.role_title')" wide>
    <x-stepper :steps="[__('auth.step_details'), __('auth.step_verify'), __('auth.step_role')]" :current="3" class="mx-auto mb-8 max-w-xl" />

    <div class="mb-8 text-center">
        <p class="eyebrow">{{ __('auth.welcome_name', ['name' => auth()->user()->name]) }}</p>
        <h1 class="type-display mt-2 text-[1.9rem] sm:text-[2.4rem] ta:text-[1.6rem] sm:ta:text-[1.9rem]">{{ __('auth.role_heading') }}</h1>
        <p class="mx-auto mt-3 max-w-lg text-fg-muted">{{ __('auth.role_intro') }}</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        @foreach ([
            'customer' => ['user', __('auth.role_user_title'), __('auth.role_user_text'), [__('auth.role_user_point1'), __('auth.role_user_point2'), __('auth.role_user_point3')], __('auth.role_user_button')],
            'provider' => ['helmet', __('auth.role_provider_title'), __('auth.role_provider_text'), [__('auth.role_provider_point1'), __('auth.role_provider_point2'), __('auth.role_provider_point3')], __('auth.role_provider_button')],
        ] as $role => [$icon, $title, $text, $points, $button])
            <form method="POST" action="{{ lroute('role.store') }}" x-data="submitOnce" @submit="submit"
                  class="group relative flex flex-col rounded-panel border border-line bg-canvas/60 p-6 shadow-card dark:bg-white/[0.02] transition-[box-shadow,border-color,transform] duration-200 hover:-translate-y-0.5 hover:border-amber/60 hover:shadow-raised sm:p-7">
                @csrf
                <input type="hidden" name="role" value="{{ $role }}">
                <span @class([
                    'flex size-14 items-center justify-center rounded-2xl',
                    'bg-accent-soft text-amber-700 ring-1 ring-amber/30 dark:text-amber' => $role === 'customer',
                    'bg-ink text-amber dark:bg-white/10' => $role === 'provider',
                ])><x-ki :name="$icon" class="size-7" /></span>
                <h2 class="type-heading mt-5 text-[1.35rem]">{{ $title }}</h2>
                <p class="mt-2 text-fg-muted">{{ $text }}</p>
                <ul class="mt-5 space-y-2.5 text-[0.95rem]">
                    @foreach ($points as $point)
                        <li class="flex items-start gap-2.5"><x-ki name="check" class="mt-0.5 size-4 text-forest dark:text-forest-300" /> {{ $point }}</li>
                    @endforeach
                </ul>
                @if ($role === 'provider')
                    <p class="mt-5 rounded-control bg-surface-2 px-3.5 py-2.5 text-sm text-fg-muted">{{ __('auth.role_provider_note') }}</p>
                @endif
                <button type="submit" class="btn btn-primary btn-lg mt-6 w-full" :disabled="busy">
                    {{ $button }} <x-ki name="arrow-right" class="size-5" />
                </button>
            </form>
        @endforeach
    </div>

    <p class="mt-6 text-center text-sm text-fg-subtle">{{ __('auth.role_once') }}</p>
</x-layouts.auth>
