{{-- Staff panel sidebar: who is signed in, laid out like the User and Provider dashboards. --}}
@php($user = filament()->auth()->user())
<div x-show="$store.sidebar.isOpen" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <p class="text-xs font-semibold tracking-[0.14em] text-primary-700 uppercase dark:text-primary-400">{{ __('ui.area_admin') }}</p>
    <p class="mt-2 truncate font-semibold text-gray-950 dark:text-white">{{ $user->name }}</p>
    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ \App\Support\Phone::mask($user->phone) }}</p>
</div>
