{{-- Plan form: Contact and location (Pro and Premium). Email is Premium only. --}}
<fieldset class="card card-pad space-y-6">
    <legend class="sr-only">{{ __('plans.section_contact') }}</legend>
    <h2 class="type-heading flex items-center gap-2.5 text-lg"><x-ki name="map-pin" class="size-5 text-fg-subtle" /> {{ __('plans.section_contact') }}</h2>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        {{-- Business email (Premium) --}}
        <div class="sm:col-span-2" x-show="has('email')">
            <label for="plan-email" class="field-label">{{ __('plans.fields.email') }} <span class="ml-1 text-sm font-normal text-fg-subtle">({{ __('ui.optional') }})</span></label>
            <input id="plan-email" type="email" inputmode="email" autocomplete="email" maxlength="255" placeholder="office@example.com"
                   x-model.trim="form.email" @blur="touch('email')" class="input" :aria-invalid="invalid('email')" aria-describedby="plan-email-help">
            <p id="plan-email-help" class="field-hint" x-show="!showError('email')">{{ __('plans.email_hint') }}</p>
            <p class="field-error" role="alert" x-cloak x-show="showError('email')"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('email')"></span></p>
        </div>

        {{-- Business address --}}
        <div class="sm:col-span-2">
            <label for="plan-address" class="field-label">{{ __('plans.fields.address') }} <span class="ml-1 text-sm font-normal text-fg-subtle">({{ __('ui.optional') }})</span></label>
            <textarea id="plan-address" rows="3" maxlength="255" autocomplete="street-address"
                      x-model="form.address" @blur="touch('address')" class="input min-h-24" :aria-invalid="invalid('address')" aria-describedby="plan-address-help"></textarea>
            <p id="plan-address-help" class="field-hint" x-show="!showError('address')">{{ __('plans.address_hint') }}</p>
            <p class="field-error" role="alert" x-cloak x-show="showError('address')"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('address')"></span></p>
        </div>

        {{-- Website --}}
        <div class="sm:col-span-2">
            <label for="plan-website" class="field-label">{{ __('plans.fields.website') }} <span class="ml-1 text-sm font-normal text-fg-subtle">({{ __('ui.optional') }})</span></label>
            <div class="relative">
                <x-ki name="globe" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-fg-subtle" />
                <input id="plan-website" type="url" inputmode="url" autocomplete="url" maxlength="255" placeholder="https://www.example.com"
                       x-model="form.website" @blur="normaliseWebsite()" class="input pl-11" :aria-invalid="invalid('website')" aria-describedby="plan-website-help">
            </div>
            <p id="plan-website-help" class="field-hint" x-show="!showError('website')">{{ __('plans.website_hint') }}</p>
            <p class="field-error" role="alert" x-cloak x-show="showError('website')"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('website')"></span></p>
        </div>

        {{-- Business phone --}}
        <div class="sm:col-span-2 lg:col-span-1">
            <label for="plan-phone" class="field-label">{{ __('plans.fields.phone') }} <span class="ml-1 text-sm font-normal text-fg-subtle">({{ __('ui.optional') }})</span></label>
            <div class="flex">
                <span class="inline-flex items-center rounded-l-control border border-r-0 border-line-strong bg-surface-2 px-3.5 font-semibold text-fg-muted" aria-hidden="true">+91</span>
                <input id="plan-phone" type="tel" inputmode="tel" autocomplete="tel-national" maxlength="16" placeholder="98400 12345"
                       x-model="form.phone" @blur="touch('phone')" class="input rounded-l-none" :aria-invalid="invalid('phone')" aria-describedby="plan-phone-help">
            </div>
            <p id="plan-phone-help" class="field-hint" x-show="!showError('phone')">{{ __('plans.phone_hint') }}</p>
            <p class="field-error" role="alert" x-cloak x-show="showError('phone')"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('phone')"></span></p>
        </div>
    </div>

    {{-- WhatsApp contact button --}}
    <div class="rounded-panel border border-line bg-sunken p-4 sm:p-5">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p id="plan-whatsapp-label" class="font-semibold">{{ __('plans.whatsapp_toggle') }}</p>
                <p class="mt-0.5 text-sm text-fg-subtle">{{ __('plans.whatsapp_toggle_hint') }}</p>
            </div>
            <button type="button" role="switch" :aria-checked="form.whatsappEnabled.toString()" aria-labelledby="plan-whatsapp-label"
                    @click="form.whatsappEnabled = !form.whatsappEnabled"
                    class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors duration-200"
                    :class="form.whatsappEnabled ? 'bg-forest' : 'bg-line-strong'">
                <span class="inline-block size-5 rounded-full bg-white shadow transition-transform duration-200" :class="form.whatsappEnabled ? 'translate-x-6' : 'translate-x-1'"></span>
            </button>
        </div>

        <div x-show="form.whatsappEnabled" x-collapse>
            <div class="pt-4">
                <label for="plan-whatsapp" class="field-label">{{ __('plans.whatsapp_number') }} <span class="text-danger-text" aria-hidden="true">*</span><span class="sr-only">({{ __('ui.required') }})</span></label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <div class="flex min-w-0 flex-1">
                        <span class="inline-flex items-center rounded-l-control border border-r-0 border-line-strong bg-surface-2 px-3.5 font-semibold text-fg-muted" aria-hidden="true">+91</span>
                        <input id="plan-whatsapp" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="16" placeholder="98400 12345"
                               x-model="form.whatsapp" @blur="touch('whatsapp')" class="input rounded-l-none" :aria-invalid="invalid('whatsapp')">
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm min-h-12 shrink-0" @click="useBusinessPhone()" :disabled="!form.phone">{{ __('plans.same_as_phone') }}</button>
                </div>
                <p class="field-error" role="alert" x-cloak x-show="showError('whatsapp')"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('whatsapp')"></span></p>
            </div>
        </div>
    </div>
</fieldset>
