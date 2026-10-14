@use('App\Support\Phone')
@use('App\Support\Mask')
{{-- The account that manages this business: the person, not the business record. --}}
<x-layouts.dashboard :title="__('plans.account_title')" area="provider" :heading="__('plans.account_title')" :subheading="__('plans.account_intro')">
    <section class="card max-w-2xl overflow-hidden animate-fade-up">
        <div class="flex items-center gap-4 border-b border-line bg-surface-2 p-5 sm:p-6">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-amber text-xl font-bold text-ink">{{ mb_strtoupper(mb_substr($user->name ?: 'K', 0, 1)) }}</span>
            <div class="min-w-0">
                <p class="truncate text-lg font-semibold">{{ $user->name }}</p>
                <p class="text-sm text-fg-subtle">{{ __('ui.role_provider') }} · {{ $provider->business_name_en }}</p>
            </div>
        </div>
        <dl class="divide-y divide-line text-[0.95rem]">
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5 sm:px-6">
                <dt class="text-fg-subtle">{{ __('auth.mobile_number') }}</dt>
                <dd class="flex items-center gap-2 font-medium" lang="en">{{ Phone::mask($user->phone) }}
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-success-text"><x-ki name="check-circle" class="size-3.5" />{{ __('user.verified') }}</span></dd>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5 sm:px-6">
                <dt class="text-fg-subtle">{{ __('auth.email_address') }}</dt>
                <dd class="font-medium" lang="en">{{ Mask::email($user->email) }}</dd>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5 sm:px-6">
                <dt class="text-fg-subtle">{{ __('user.member_since') }}</dt>
                <dd class="font-medium">{{ ist($user->created_at) }}</dd>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5 sm:px-6">
                <dt class="text-fg-subtle">{{ __('user.language') }}</dt>
                <dd><x-lang-switch tone="light" full /></dd>
            </div>
        </dl>
        <p class="border-t border-line px-5 py-4 text-sm text-fg-subtle sm:px-6">{{ __('plans.account_note') }}</p>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-line p-4 sm:px-6">
            @csrf
            <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
            <button type="submit" class="btn btn-secondary w-full sm:w-auto"><x-ki name="logout" class="size-4" /> {{ __('ui.logout') }}</button>
        </form>
    </section>
</x-layouts.dashboard>
