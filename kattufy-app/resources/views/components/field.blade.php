@props([
    'name',
    'label',
    'hint' => null,
    'required' => false,
    'optional' => false,
    'id' => null,
])
{{--
    Label, hint and error around one input. The input itself is the slot and
    should use id="{{ $id }}", aria-describedby and aria-invalid from here:
    <x-field name="email" label="Email"> <input ... id="email" ...> </x-field>
--}}
@php
    $id ??= $name;
    $error = $errors->first($name);
@endphp
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <label for="{{ $id }}" class="field-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger-text" aria-hidden="true">*</span><span class="sr-only">({{ __('ui.required') }})</span>
        @elseif ($optional)
            <span class="ml-1 text-sm font-normal text-fg-subtle">({{ __('ui.optional') }})</span>
        @endif
    </label>
    {{ $slot }}
    @if ($error)
        <p id="{{ $id }}-error" class="field-error" role="alert">
            <x-ki name="alert" class="mt-0.5 size-4" /> <span>{{ $error }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>
    @endif
</div>
