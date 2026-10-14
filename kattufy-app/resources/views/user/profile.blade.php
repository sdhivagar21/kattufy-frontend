@use('App\Support\Phone')
<x-layouts.dashboard :title="__('user.profile_title')" :heading="__('user.profile_title')" :subheading="__('user.profile_intro')" area="user">
    <div x-data="{ editing: {{ ($errors->any() || request()->boolean('edit')) ? 'true' : 'false' }}, sharing: {{ request()->boolean('share') ? 'true' : 'false' }} }" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ lroute('user.dashboard') }}" class="link inline-flex items-center gap-2"><x-ki name="arrow-left" class="size-4" /> {{ __('user.back_dashboard') }}</a>
            <div class="flex gap-2">
                <button type="button" @click="sharing = true" class="btn btn-secondary"><x-ki name="share" class="size-4" /> {{ __('user.share_profile') }}</button>
                <button type="button" @click="editing = !editing" class="btn btn-primary"><x-ki name="pencil" class="size-4" /> {{ __('user.edit_profile') }}</button>
            </div>
        </div>

        <section class="card overflow-hidden">
            <div class="flex flex-wrap items-center gap-4 border-b border-line bg-surface-2 p-5 sm:p-7">
                <span class="flex size-16 items-center justify-center rounded-full bg-amber text-2xl font-bold text-ink" aria-label="{{ __('user.profile_avatar') }}">{{ mb_strtoupper(mb_substr($user->name ?: 'U', 0, 1)) }}</span>
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-xl font-semibold">{{ $user->name }}</h2>
                    <p class="mt-1 text-sm text-fg-muted">{{ __('ui.role_customer') }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-success-soft px-3 py-1.5 text-sm font-medium text-success-text">
                    <x-ki name="check-circle" class="size-4" /> {{ $user->isVerified() ? __('user.account_verified') : __('user.account_unverified') }}
                </span>
            </div>

            <div x-show="!editing" x-cloak class="p-5 sm:p-7">
                <dl class="grid gap-x-8 sm:grid-cols-2">
                    <div class="border-b border-line py-4"><dt class="text-sm text-fg-subtle">{{ __('auth.full_name') }}</dt><dd class="mt-1 font-medium">{{ $user->name }}</dd></div>
                    <div class="border-b border-line py-4"><dt class="text-sm text-fg-subtle">{{ __('auth.mobile_number') }}</dt><dd class="mt-1 flex items-center gap-2 font-medium">{{ Phone::mask($user->phone) }} @if($user->isVerified())<x-ki name="check-circle" class="size-4 text-success-text" />@endif</dd></div>
                    <div class="border-b border-line py-4"><dt class="text-sm text-fg-subtle">{{ __('auth.email_address') }}</dt><dd class="mt-1 break-all font-medium">{{ $user->email ?: '—' }}</dd></div>
                    <div class="border-b border-line py-4"><dt class="text-sm text-fg-subtle">{{ __('user.location_address') }}</dt><dd class="mt-1 font-medium">{{ $user->profile_address ?: '—' }}</dd></div>
                    <div class="py-4"><dt class="text-sm text-fg-subtle">{{ __('user.member_since') }}</dt><dd class="mt-1 font-medium">{{ ist($user->created_at) }}</dd></div>
                    <div class="py-4"><dt class="text-sm text-fg-subtle">{{ __('user.mobile_verification') }}</dt><dd class="mt-1 font-medium">{{ $user->phone_verified_at ? ist($user->phone_verified_at) : __('user.not_verified') }}</dd></div>
                </dl>
            </div>

            <form x-show="editing" x-cloak method="POST" action="{{ lroute('user.profile.update') }}" class="space-y-5 p-5 sm:p-7">
                @csrf @method('PUT')
                <div>
                    <label for="name" class="field-label">{{ __('auth.full_name') }}</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required minlength="2" maxlength="120" autocomplete="name" class="input" aria-describedby="name-error">
                    @error('name')<p id="name-error" class="mt-1 text-sm text-danger-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="field-label">{{ __('auth.mobile_number') }}</label>
                    <input id="phone" value="{{ Phone::mask($user->phone) }}" readonly class="input bg-surface-2" aria-describedby="phone-note">
                    <p id="phone-note" class="mt-1 text-sm text-fg-subtle">{{ __('user.phone_change_note') }}</p>
                </div>
                <div>
                    <label for="email" class="field-label">{{ __('auth.email_address') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email" class="input" aria-describedby="email-error">
                    @error('email')<p id="email-error" class="mt-1 text-sm text-danger-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="profile_address" class="field-label">{{ __('user.location_address') }}</label>
                    <input id="profile_address" name="profile_address" value="{{ old('profile_address', $user->profile_address) }}" maxlength="255" autocomplete="street-address" class="input">
                    @error('profile_address')<p class="mt-1 text-sm text-danger-text">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap justify-end gap-2 border-t border-line pt-5">
                    <button type="button" @click="editing = false" class="btn btn-secondary">{{ __('user.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><x-ki name="check" class="size-4" /> {{ __('user.save_changes') }}</button>
                </div>
            </form>
        </section>

        <div x-show="sharing" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-ink/60 p-4" @keydown.escape.window="sharing = false" @click.self="sharing = false">
            <section role="dialog" aria-modal="true" aria-labelledby="share-title" class="w-full max-w-lg rounded-panel bg-surface p-6 shadow-raised">
                <div class="flex items-start justify-between gap-4">
                    <div><h2 id="share-title" class="type-subheading">{{ __('user.share_profile') }}</h2><p class="mt-2 text-sm text-fg-muted">{{ __('user.sharing_unavailable') }}</p></div>
                    <button type="button" @click="sharing = false" class="rounded-control p-2 hover:bg-surface-2" aria-label="{{ __('ui.dismiss') }}"><x-ki name="x" class="size-5" /></button>
                </div>
                <p class="mt-4 rounded-control bg-surface-2 p-4 text-sm text-fg-muted">{{ __('user.sharing_privacy_note') }}</p>
                <div class="mt-5 flex justify-end"><button type="button" @click="sharing = false" class="btn btn-primary">{{ __('ui.done') }}</button></div>
            </section>
        </div>
    </div>
</x-layouts.dashboard>
