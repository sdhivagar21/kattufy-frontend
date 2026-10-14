@use('App\Support\ListingPlans')
@use('App\Support\Phone')
{{--
    Company profile: the business details saved to Kattufy (unchanged form,
    unchanged endpoint). Plan-only details such as website, WhatsApp, photos,
    services and projects are prepared in the listing plan form instead.
--}}
@php
    $planNames = collect(ListingPlans::PLANS)->mapWithKeys(fn ($p) => [$p => __('plans.'.$p.'.name')])->all();
@endphp
<x-layouts.dashboard :title="__('provider.profile_title')" area="provider">
    <header class="mb-6 max-w-2xl animate-fade-up sm:mb-8">
        <h1 class="type-display text-[1.75rem] sm:text-[2.1rem] ta:text-[1.45rem] sm:ta:text-[1.7rem]">{{ __('provider.profile_title') }}</h1>
        <p class="mt-2 text-fg-muted">{{ __('provider.profile_intro') }}</p>
    </header>

    <div class="grid grid-cols-1 gap-8 animate-fade-up xl:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ lroute('provider.profile.update') }}" novalidate x-data="submitOnce" @submit="submit" class="min-w-0 space-y-6">
            @csrf
            @method('PUT')

            {{-- About the business --}}
            <div class="card card-pad space-y-6">
                <h2 class="type-heading text-lg">{{ __('provider.about_business') }}</h2>

                <div>
                    <p class="field-label">{{ __('plans.fields.name') }}</p>
                    <p class="rounded-control border border-line bg-sunken px-3.5 py-3 font-semibold">{{ $provider->business_name_en }}</p>
                    <p class="field-hint">{{ __('provider.locked_fields') }}</p>
                </div>

                <x-field name="description_en" :label="__('provider.description_en')" :hint="__('provider.description_hint')" optional>
                    <div x-data="counter({ max: 2000 })">
                        <textarea id="description_en" name="description_en" rows="6" maxlength="2000" lang="en" x-ref="field" @input="update()"
                                  class="input" aria-describedby="description_en-hint description_en-error" @error('description_en') aria-invalid="true" @enderror>{{ old('description_en', $provider->description_en) }}</textarea>
                        <p class="mt-1 text-right text-xs text-fg-subtle tabular-nums" aria-live="polite"><span x-text="left"></span> {{ __('provider.characters_left') }}</p>
                    </div>
                </x-field>

                <x-field name="description_ta" :label="__('provider.description_ta')" :hint="__('provider.description_ta_hint')" optional>
                    <div x-data="counter({ max: 2000 })">
                        <textarea id="description_ta" name="description_ta" rows="5" maxlength="2000" lang="ta" x-ref="field" @input="update()"
                                  class="input leading-loose" @error('description_ta') aria-invalid="true" @enderror>{{ old('description_ta', $provider->description_ta) }}</textarea>
                        <p class="mt-1 text-right text-xs text-fg-subtle tabular-nums" aria-live="polite"><span x-text="left"></span> {{ __('provider.characters_left') }}</p>
                    </div>
                    @if ($provider->description_ta_status === 'pending_review')
                        <p class="mt-1.5 flex items-center gap-1.5 text-sm text-accent-text"><x-ki name="clock" class="size-4" /> {{ __('provider.tamil_pending') }}</p>
                    @endif
                </x-field>
            </div>

            {{-- Address and email --}}
            <div class="card card-pad grid grid-cols-1 gap-6 sm:grid-cols-2">
                <h2 class="type-heading text-lg sm:col-span-2">{{ __('provider.contact_and_address') }}</h2>

                <x-field name="address" :label="__('provider.business_address')" required class="sm:col-span-2">
                    <textarea id="address" name="address" rows="3" maxlength="255" required autocomplete="street-address" class="input" @error('address') aria-invalid="true" @enderror>{{ old('address', $provider->address) }}</textarea>
                </x-field>
                <x-field name="pincode" :label="__('provider.pincode')" :hint="__('provider.pincode_hint')" required>
                    <input id="pincode" name="pincode" inputmode="numeric" maxlength="6" required autocomplete="postal-code" value="{{ old('pincode', $provider->pincode) }}" class="input tracking-widest" @error('pincode') aria-invalid="true" @enderror>
                </x-field>
                <x-field name="business_email" :label="__('provider.business_email')" required>
                    <input id="business_email" name="business_email" type="email" required autocomplete="email" value="{{ old('business_email', $provider->email) }}" class="input" @error('business_email') aria-invalid="true" @enderror>
                </x-field>

                <div class="sm:col-span-2">
                    <p class="field-label">{{ __('provider.business_phone') }}</p>
                    <p class="flex flex-wrap items-center gap-2 rounded-control border border-line bg-sunken px-3.5 py-3 font-medium" lang="en">
                        {{ Phone::display($provider->phone) }}
                        @if ($provider->phone_verified_at)<span class="inline-flex items-center gap-1 text-xs font-semibold text-success-text"><x-ki name="check-circle" class="size-3.5" />{{ __('user.verified') }}</span>@endif
                    </p>
                    <p class="field-hint">{{ __('provider.locked_fields') }}</p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ lroute('provider.dashboard') }}" class="btn btn-ghost">{{ __('plans.cancel') }}</a>
                <button type="submit" class="btn btn-primary btn-lg" :disabled="busy">
                    <span x-show="!busy">{{ __('provider.save_changes') }}</span>
                    <span x-cloak x-show="busy">{{ __('ui.saving') }}</span>
                </button>
            </div>
        </form>

        {{-- Plan details live in the listing plan --}}
        <aside class="min-w-0">
            <div class="card card-pad xl:sticky xl:top-28" x-data="planState({ key: @js(ListingPlans::storageKey($provider)) })">
                <span class="flex size-10 items-center justify-center rounded-xl bg-accent-soft text-amber-700 dark:text-amber"><x-ki name="sparkle" class="size-5" /></span>
                <h2 class="mt-4 font-semibold">{{ __('plans.profile_plan_title') }}</h2>
                <p class="mt-1 text-sm text-fg-muted">{{ __('plans.profile_plan_text') }}</p>
                <dl class="mt-4 rounded-control bg-surface-2 px-3.5 py-3 text-sm">
                    <dt class="text-fg-subtle">{{ __('plans.listing_plan') }}</dt>
                    <dd class="mt-0.5 font-semibold" x-text="plan ? @js($planNames)[plan] : @js(__('plans.no_plan'))">{{ __('plans.no_plan') }}</dd>
                    <dd class="mt-1 text-xs font-semibold text-accent-text" x-cloak x-show="plan">{{ __('plans.state_not_active') }}</dd>
                </dl>
                <a href="{{ lroute('provider.plan') }}" class="btn btn-secondary mt-4 w-full">{{ __('plans.open_plan') }} <x-ki name="arrow-right" class="size-4" /></a>
            </div>
        </aside>
    </div>
</x-layouts.dashboard>
