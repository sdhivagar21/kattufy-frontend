@use('App\Support\Phone')
{{-- Business details and documents for verification. --}}
@php
    $id = $provider?->idCredential();
    $proof = $provider?->addressProof();
    $certificate = $provider?->certificate();
    $types = implode(',', config('kattufy.uploads.mimes'));
    $maxMb = (int) (config('kattufy.uploads.max_kb') / 1024);
    $fileMessages = ['type' => __('provider.file_type'), 'size' => __('provider.file_size')];
    $requirements = \App\Modules\Verification\Models\VerificationRequirement::with('credentialType')->get()->groupBy('category_id');
@endphp
<x-layouts.dashboard :title="__('provider.register_title')" area="provider">
    <header class="mb-6 sm:mb-8">
        <div class="flex flex-wrap items-center gap-3">
            <x-status-badge :state="$state ?? \App\Modules\Verification\Enums\VerificationState::NotSubmitted" />
        </div>
        <h1 class="type-display mt-3 text-[1.75rem] sm:text-[2.1rem] ta:text-[1.45rem] sm:ta:text-[1.7rem]">{{ __('provider.register_heading') }}</h1>
        <p class="mt-2 max-w-2xl text-fg-muted">{{ __('provider.register_intro') }}</p>
        <div class="mt-6 max-w-2xl rounded-panel border border-line bg-surface px-4 py-4 shadow-card sm:px-6">
            @include('provider.partials.verification-steps', ['state' => $state ?? \App\Modules\Verification\Enums\VerificationState::NotSubmitted])
        </div>
    </header>

    @if ($feedback)
        <x-alert tone="warning" :title="__('provider.feedback_title')" class="mb-6">
            <p class="font-medium text-fg">“{{ $feedback->reason }}”</p>
            <p class="mt-1 text-sm text-fg-muted">{{ __('provider.feedback_when', ['date' => ist($feedback->created_at, 'j F Y')]) }}</p>
        </x-alert>
    @endif

    @if (config('kattufy.demo_mode') && ! app()->isProduction())
        <x-alert tone="demo" :title="__('provider.sample_data_title')" class="mb-6">{{ __('provider.sample_data_text') }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert tone="danger" :title="__('ui.fix_errors')" class="mb-6" role="alert">
            <ul class="list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </x-alert>
    @endif

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_19rem]">
        <form method="POST" action="{{ lroute('provider.register.store') }}" enctype="multipart/form-data" novalidate
              x-data="submitOnce" @submit="submit" class="min-w-0 space-y-6">
            @csrf
            <p class="text-sm text-fg-subtle">{!! __('provider.required_note', ['star' => '<span class="text-danger-text">*</span>']) !!}</p>

            {{-- 1. Business --}}
            <fieldset class="card card-pad">
                <legend class="sr-only">{{ __('provider.section_business') }}</legend>
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex size-8 items-center justify-center rounded-full bg-ink text-sm font-bold text-amber dark:bg-amber dark:text-ink">1</span>
                    <h2 class="type-heading text-lg" aria-hidden="true">{{ __('provider.section_business') }}</h2>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="business_name" :label="__('provider.business_name')" :hint="__('provider.business_name_hint')" required class="sm:col-span-2">
                        <input id="business_name" name="business_name" type="text" required maxlength="200" autocomplete="organization"
                               value="{{ old('business_name', $provider?->business_name_en) }}" class="input" @error('business_name') aria-invalid="true" @enderror>
                    </x-field>

                    <x-field name="category_id" :label="__('provider.category')" :hint="__('provider.category_hint')" required class="sm:col-span-2">
                        <select id="category_id" name="category_id" required class="input" @error('category_id') aria-invalid="true" @enderror>
                            <option value="">{{ __('ui.choose') }}</option>
                            @foreach ($groups as $group)
                                <optgroup label="{{ tr_field($group, 'name') }}">
                                    @foreach ($group->categories as $category)
                                        <option value="{{ $category->id }}" @selected((int) old('category_id', $provider?->primaryCategory()?->id) === $category->id)>{{ tr_field($category, 'name') }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </x-field>

                    <x-field name="address" :label="__('provider.business_address')" required class="sm:col-span-2">
                        <div x-data="counter({ max: 255 })">
                            <textarea id="address" name="address" rows="3" required maxlength="255" autocomplete="street-address" x-ref="field" @input="update()"
                                      class="input" @error('address') aria-invalid="true" @enderror>{{ old('address', $provider?->address) }}</textarea>
                            <p class="mt-1 text-right text-xs text-fg-subtle" aria-live="polite"><span x-text="left"></span> {{ __('provider.characters_left') }}</p>
                        </div>
                    </x-field>

                    <x-field name="pincode" :label="__('provider.pincode')" :hint="__('provider.pincode_hint')" required>
                        <input id="pincode" name="pincode" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="postal-code"
                               value="{{ old('pincode', $provider?->pincode) }}" class="input tracking-widest" @error('pincode') aria-invalid="true" @enderror>
                    </x-field>

                    <x-field name="business_phone" :label="__('provider.business_phone')" :hint="__('provider.business_phone_hint')" required>
                        <div class="flex">
                            <span class="inline-flex items-center rounded-l-control border border-r-0 border-line-strong bg-surface-2 px-3.5 font-medium text-fg-muted" aria-hidden="true">+91</span>
                            <input id="business_phone" name="business_phone" type="tel" inputmode="numeric" maxlength="14" required
                                   value="{{ old('business_phone', $defaults['business_phone']) }}" class="input rounded-l-none" @error('business_phone') aria-invalid="true" @enderror>
                        </div>
                    </x-field>

                    <x-field name="business_email" :label="__('provider.business_email')" required class="sm:col-span-2">
                        <input id="business_email" name="business_email" type="email" required maxlength="255" autocomplete="email"
                               value="{{ old('business_email', $defaults['business_email']) }}" class="input" @error('business_email') aria-invalid="true" @enderror>
                    </x-field>
                </div>
            </fieldset>

            {{-- 2. Identification --}}
            <fieldset class="card card-pad">
                <legend class="sr-only">{{ __('provider.section_identity') }}</legend>
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex size-8 items-center justify-center rounded-full bg-ink text-sm font-bold text-amber dark:bg-amber dark:text-ink">2</span>
                    <h2 class="type-heading text-lg" aria-hidden="true">{{ __('provider.section_identity') }}</h2>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="id_type" :label="__('provider.id_type')" required>
                        <select id="id_type" name="id_type" required class="input" @error('id_type') aria-invalid="true" @enderror>
                            <option value="">{{ __('ui.choose') }}</option>
                            @foreach ($idTypes as $type)
                                <option value="{{ $type->slug }}" @selected(old('id_type', $id?->type?->slug) === $type->slug)>{{ tr_field($type, 'name') }}</option>
                            @endforeach
                        </select>
                    </x-field>

                    <x-field name="id_number" :label="__('provider.id_number')" :hint="$id ? __('provider.id_number_saved', ['masked' => $id->maskedNumber()]) : __('provider.id_number_hint')" :required="! $id">
                        <input id="id_number" name="id_number" type="text" @unless ($id) required @endunless maxlength="20" autocomplete="off" spellcheck="false"
                               value="{{ old('id_number') }}" placeholder="{{ $id?->maskedNumber() }}" class="input font-mono uppercase tracking-wider" @error('id_number') aria-invalid="true" @enderror>
                    </x-field>
                </div>
                <p class="mt-4 flex items-start gap-2 text-sm text-fg-muted"><x-ki name="id-card" class="mt-0.5 size-4" /> {{ __('provider.section_identity_text') }}</p>
                <p class="mt-2 flex items-start gap-2 text-sm text-fg-subtle"><x-ki name="lock" class="mt-0.5 size-4" /> {{ __('provider.no_aadhaar_note') }}</p>
            </fieldset>

            {{-- 3. Address proof --}}
            <fieldset class="card card-pad">
                <legend class="sr-only">{{ __('provider.section_address') }}</legend>
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex size-8 items-center justify-center rounded-full bg-ink text-sm font-bold text-amber dark:bg-amber dark:text-ink">3</span>
                    <h2 class="type-heading text-lg" aria-hidden="true">{{ __('provider.section_address') }}</h2>
                </div>
                @include('provider.partials.file-field', [
                    'name' => 'address_proof',
                    'label' => __('provider.address_proof'),
                    'hint' => __('provider.address_proof_hint'),
                    'required' => true,
                    'current' => $proof?->document,
                    'credential' => $proof,
                ])
            </fieldset>

            {{-- 4. Business registration documents (optional) --}}
            <fieldset class="card card-pad">
                <legend class="sr-only">{{ __('provider.section_business_docs') }}</legend>
                <div class="mb-2 flex flex-wrap items-center gap-3">
                    <span class="flex size-8 items-center justify-center rounded-full bg-ink text-sm font-bold text-amber dark:bg-amber dark:text-ink">4</span>
                    <h2 class="type-heading text-lg" aria-hidden="true">{{ __('provider.section_business_docs') }}</h2>
                    <span class="rounded-full bg-surface-2 px-2 py-0.5 text-[0.7rem] font-semibold text-fg-subtle">{{ __('provider.item_optional') }}</span>
                </div>
                <p class="mb-5 text-sm text-fg-muted">{{ __('provider.business_docs_text') }}</p>

                <div class="space-y-4">
                    <x-field name="certificate_type" :label="__('provider.certificate_type')" :hint="__('provider.certificate_hint')" optional>
                        <select id="certificate_type" name="certificate_type" class="input" @error('certificate_type') aria-invalid="true" @enderror>
                            <option value="">{{ __('provider.certificate_none') }}</option>
                            @foreach ($certificateTypes as $type)
                                <option value="{{ $type->slug }}" @selected(old('certificate_type', $certificate?->type?->slug) === $type->slug)>{{ tr_field($type, 'name') }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    @include('provider.partials.file-field', [
                        'name' => 'certificate',
                        'label' => __('provider.certificate'),
                        'hint' => null,
                        'required' => false,
                        'optional' => true,
                        'current' => $certificate?->document,
                        'credential' => $certificate,
                    ])
                    @if ($requirements->isNotEmpty())
                        <p class="flex items-start gap-2 text-sm text-fg-muted"><x-ki name="info" class="mt-0.5 size-4" />
                            {{ __('provider.category_requirements', ['list' => $requirements->flatten()->map(fn ($r) => tr_field($r->credentialType, 'name'))->unique()->join(', ')]) }}
                        </p>
                    @endif
                </div>

                <div class="mt-6 flex items-start gap-3 rounded-control bg-surface-2 p-4 text-sm text-fg-muted">
                    <x-ki name="shield" class="mt-0.5 size-5 text-forest dark:text-forest-300" />
                    <p>{{ __('provider.documents_notice') }}</p>
                </div>
            </fieldset>

            {{-- Consent and actions --}}

            {{-- Consent and actions --}}
            <div class="card card-pad">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="consent" value="1" class="checkbox" @checked(old('consent', $provider !== null)) required @error('consent') aria-invalid="true" @enderror>
                    <span>
                        <span class="font-semibold">{{ __('provider.consent_label') }}</span>
                        <span class="mt-0.5 block text-sm text-fg-muted">{{ __('provider.consent_text') }}</span>
                    </span>
                </label>
                @error('consent')<p class="field-error" role="alert"><x-ki name="alert" class="mt-0.5 size-4" /> {{ $message }}</p>@enderror

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-end">
                    <button type="submit" name="action" value="save" class="btn btn-secondary" :disabled="busy">
                        <span x-show="!(busy && action === 'save')">{{ __('provider.save_draft') }}</span>
                        <span x-cloak x-show="busy && action === 'save'">{{ __('ui.saving') }}</span>
                    </button>
                    <button type="submit" name="action" value="submit" class="btn btn-primary btn-lg" :disabled="busy">
                        <x-ki name="upload" class="size-5" />
                        <span x-show="!(busy && action === 'submit')">{{ __('provider.submit') }}</span>
                        <span x-cloak x-show="busy && action === 'submit'">{{ __('provider.uploading') }}</span>
                    </button>
                </div>
            </div>
        </form>

        {{-- What happens next --}}
        <aside class="space-y-5">
            <section class="card card-pad xl:sticky xl:top-28">
                <h2 class="font-semibold">{{ __('provider.next_title') }}</h2>
                <ol class="mt-4 space-y-5">
                    @foreach (['submit', 'review', 'result'] as $i => $step)
                        <li class="flex gap-3">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-accent-soft text-sm font-bold text-accent-text ring-1 ring-amber/30">{{ $i + 1 }}</span>
                            <div>
                                <p class="font-medium">{{ __('provider.next_'.$step.'_title') }}</p>
                                <p class="mt-0.5 text-sm text-fg-muted">{{ __('provider.next_'.$step.'_text') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </aside>
    </div>
</x-layouts.dashboard>
