{{-- "Can't find what you need?" The reply goes to the account's verified mobile number. --}}
<x-layouts.site :title="__('directory.enquiry_title')" noindex>
    <div class="container-page grid grid-cols-1 gap-10 py-10 sm:py-14 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 max-w-2xl">
            <p class="eyebrow">{{ __('ui.send_enquiry') }}</p>
            <h1 class="type-display mt-2 text-[1.9rem] sm:text-[2.3rem] ta:text-[1.55rem]">{{ __('directory.enquiry_title') }}</h1>
            <p class="mt-3 text-fg-muted">{{ __('directory.enquiry_intro') }}</p>

            <form method="POST" action="{{ lroute('enquiry.store') }}" x-data="submitOnce" @submit="submit" novalidate class="card card-pad mt-8 grid gap-5 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="source" value="{{ $source }}">
                <x-field name="name" :label="__('auth.full_name')" required class="sm:col-span-2">
                    <input id="name" name="name" required maxlength="150" value="{{ old('name', $user->name) }}" class="input" @error('name') aria-invalid="true" @enderror>
                </x-field>
                <x-field name="category_id" :label="__('directory.service')" required>
                    <select id="category_id" name="category_id" required class="input" @error('category_id') aria-invalid="true" @enderror>
                        <option value="">{{ __('ui.choose') }}</option>
                        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((int) old('category_id', $selectedCategory) === $c->id)>{{ tr_field($c, 'name') }}</option>@endforeach
                    </select>
                </x-field>
                <x-field name="locality_id" :label="__('directory.area')" required>
                    <select id="locality_id" name="locality_id" required class="input" @error('locality_id') aria-invalid="true" @enderror>
                        <option value="">{{ __('ui.choose') }}</option>
                        @foreach ($localities as $l)<option value="{{ $l->id }}" @selected((int) old('locality_id') === $l->id)>{{ tr_field($l, 'name') }}</option>@endforeach
                    </select>
                </x-field>
                <x-field name="message" :label="__('directory.enquiry_message')" :hint="__('directory.enquiry_message_hint')" required class="sm:col-span-2">
                    <textarea id="message" name="message" rows="5" required maxlength="2000" class="input" @error('message') aria-invalid="true" @enderror>{{ old('message') }}</textarea>
                </x-field>
                <x-field name="budget" :label="__('directory.budget')" optional>
                    <input id="budget" name="budget" maxlength="50" value="{{ old('budget') }}" placeholder="₹" class="input">
                </x-field>
                <x-field name="preferred_date" :label="__('directory.preferred_date')" optional>
                    <input id="preferred_date" name="preferred_date" type="date" min="{{ now('Asia/Kolkata')->toDateString() }}" value="{{ old('preferred_date') }}" class="input" @error('preferred_date') aria-invalid="true" @enderror>
                </x-field>
                <p class="flex items-start gap-2 text-sm text-fg-subtle sm:col-span-2"><x-ki name="phone" class="mt-0.5 size-4" /> {{ __('directory.enquiry_phone', ['phone' => $maskedPhone]) }}</p>
                <div class="sm:col-span-2">
                    <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto" :disabled="busy">{{ __('directory.enquiry_submit') }}</button>
                </div>
            </form>
        </div>
        <aside class="card card-pad h-fit">
            <h2 class="font-semibold">{{ __('directory.enquiry_what_next') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-fg-muted">{{ __('directory.enquiry_what_next_text') }}</p>
        </aside>
    </div>
</x-layouts.site>
