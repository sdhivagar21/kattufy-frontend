{{-- Plan form: Company (every plan). Inside a listingPlanner. --}}
<fieldset class="card card-pad space-y-6">
    <legend class="sr-only">{{ __('plans.section_company') }}</legend>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="type-heading flex items-center gap-2.5 text-lg"><x-ki name="building" class="size-5 text-fg-subtle" /> {{ __('plans.section_company') }}</h2>
        <p class="text-sm text-fg-subtle">{!! __('plans.required_note', ['star' => '<span class="text-danger-text">*</span>']) !!}</p>
    </div>

    <div>
        <label for="plan-name" class="field-label">{{ __('plans.fields.name') }} <span class="text-danger-text" aria-hidden="true">*</span><span class="sr-only">({{ __('ui.required') }})</span></label>
        <input id="plan-name" type="text" maxlength="200" autocomplete="organization" required
               x-model="form.name" @blur="touch('name')" class="input" :aria-invalid="invalid('name')" aria-describedby="plan-name-help">
        <p id="plan-name-help" class="field-hint" x-show="!showError('name')">{{ __('plans.name_hint') }}</p>
        <p class="field-error" role="alert" x-cloak x-show="showError('name')"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('name')"></span></p>
    </div>

    <div>
        <label for="plan-description" class="field-label">{{ __('plans.fields.description') }} <span class="ml-1 text-sm font-normal text-fg-subtle">({{ __('ui.optional') }})</span></label>
        <textarea id="plan-description" rows="6" x-model="form.description" @blur="touch('description')"
                  class="input" :aria-invalid="errors.description ? 'true' : 'false'" aria-describedby="plan-description-count"></textarea>
        {{-- Live counter --}}
        <div class="mt-2 h-1 overflow-hidden rounded-full bg-surface-2" aria-hidden="true">
            <div class="h-full rounded-full transition-[width,background-color] duration-200"
                 :class="{ 'bg-forest dark:bg-forest-300': descriptionState === 'ok', 'bg-amber': descriptionState === 'near', 'bg-danger': descriptionState === 'over' }"
                 :style="`width: ${Math.min(100, descriptionLength / limit * 100)}%`"></div>
        </div>
        <div id="plan-description-count" class="mt-1.5 flex flex-wrap items-start justify-between gap-x-4 gap-y-1 text-sm" aria-live="polite">
            <span x-show="!errors.description" class="text-fg-subtle" x-text="descriptionHint"></span>
            <span x-cloak x-show="errors.description" class="field-error mt-0" role="alert"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="errors.description"></span></span>
            <span class="ml-auto font-semibold tabular-nums" lang="en"
                  :class="{ 'text-fg-subtle': descriptionState === 'ok', 'text-accent-text': descriptionState === 'near', 'text-danger-text': descriptionState === 'over' }"
                  x-text="counterText"></span>
        </div>
    </div>
</fieldset>
