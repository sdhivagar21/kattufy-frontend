@use('App\Modules\Verification\Enums\CredentialStatus')
{{--
    One document upload: name, Required/Optional, file guidance, the chosen
    file with a preview and Remove/Replace, and the status of the file already
    on record (Submitted / Verified / Action required, from the admin review).
    The file is only sent with the form (Save draft or Submit).
--}}
@php
    $optional ??= false;
    $credential ??= null;
    $error = $errors->first($name);
    $maxMb = (int) (config('kattufy.uploads.max_kb') / 1024);
    $saved = $credential?->status
        ? match ($credential->status) {
            CredentialStatus::Verified => ['success', __('provider.item_verified')],
            CredentialStatus::Rejected, CredentialStatus::Expired => ['danger', __('provider.item_action')],
            default => ['pending', __('provider.item_submitted')],
        }
        : ['pending', __('provider.doc_on_file')];
@endphp
<div x-data="fileField({ maxKb: {{ (int) config('kattufy.uploads.max_kb') }}, types: @js(config('kattufy.uploads.mimes')), messages: @js(['type' => __('provider.file_type'), 'size' => __('provider.file_size')]) })"
     @class(['rounded-panel border bg-surface p-4 transition-colors sm:p-5', 'border-danger' => $error, 'border-line' => ! $error])
     :class="name && 'border-forest/40'">
    <div class="flex flex-wrap items-start gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-surface-2 text-fg-muted ring-1 ring-line"><x-ki name="document" class="size-5" /></span>
        <div class="min-w-0 flex-1">
            <p class="flex flex-wrap items-center gap-2 font-semibold" id="{{ $name }}-label">
                {{ $label }}
                @if ($required)
                    <span class="rounded-full bg-danger-soft px-2 py-0.5 text-[0.7rem] font-semibold text-danger-text">{{ __('provider.item_required') }}</span>
                @else
                    <span class="rounded-full bg-surface-2 px-2 py-0.5 text-[0.7rem] font-semibold text-fg-subtle">{{ __('provider.item_optional') }}</span>
                @endif
            </p>
            @if ($hint)<p class="mt-0.5 text-sm text-fg-muted">{{ $hint }}</p>@endif
            <p class="mt-0.5 text-xs text-fg-subtle">{{ __('provider.doc_guidance', ['max' => $maxMb]) }}</p>
        </div>
        {{-- Status --}}
        <span class="shrink-0">
            <span x-show="name"><x-status-badge size="sm" tone="pending" :label="__('provider.doc_ready')" /></span>
            <span x-show="!name">
                @if ($current)
                    <x-status-badge size="sm" :tone="$saved[0]" :label="$saved[1]" />
                @else
                    <x-status-badge size="sm" tone="neutral" :label="__('provider.doc_not_added')" />
                @endif
            </span>
        </span>
    </div>

    {{-- File already on record --}}
    @if ($current)
        <div class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-control bg-sunken px-3.5 py-2.5 text-sm text-fg-muted" x-show="!name">
            <x-ki name="check-circle" class="size-4 text-forest dark:text-forest-300" />
            <span>{{ __('provider.on_file') }}</span>
            <a href="{{ $current->temporaryUrl() }}" target="_blank" rel="noopener" class="link font-medium" style="overflow-wrap:anywhere">{{ $current->original_filename }}</a>
            <span class="text-fg-subtle">· {{ $current->humanSize() }}</span>
        </div>
        @if ($credential?->rejection_reason && in_array($credential->status, [CredentialStatus::Rejected, CredentialStatus::Expired], true))
            <p class="mt-2 text-sm font-medium text-danger-text">{{ __('provider.doc_reason', ['reason' => $credential->rejection_reason]) }}</p>
        @endif
    @endif

    {{-- Pick or drop a file --}}
    <label for="{{ $name }}" x-show="!name"
           @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
           :class="dragging ? 'border-amber bg-accent-soft' : 'border-line-strong hover:border-fg-subtle hover:bg-surface-2'"
           class="mt-4 flex cursor-pointer flex-col items-center gap-2 rounded-control border-2 border-dashed px-4 py-5 text-center transition-colors sm:flex-row sm:text-left">
        <span class="btn btn-secondary btn-sm pointer-events-none"><x-ki name="upload" class="size-4" /> {{ $current ? __('provider.doc_replace') : __('provider.choose_file') }}</span>
        <span class="text-sm text-fg-subtle">{{ __('provider.drop_or_choose') }}</span>
    </label>
    <input id="{{ $name }}" name="{{ $name }}" type="file" x-ref="input" @change="pick($event)" class="sr-only"
           accept="{{ collect(config('kattufy.uploads.mimes'))->map(fn ($e) => '.'.$e)->join(',') }}"
           aria-labelledby="{{ $name }}-label" @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>

    {{-- Chosen file --}}
    <div x-cloak x-show="name" class="mt-4 flex flex-wrap items-center gap-3 rounded-control border border-forest/30 bg-success-soft p-3">
        <span class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-md bg-surface ring-1 ring-line">
            <template x-if="isImage && previewUrl"><img :src="previewUrl" alt="" class="size-full object-cover"></template>
            <x-ki name="document" class="size-6 text-fg-subtle" x-show="!isImage" />
        </span>
        <div class="min-w-0 flex-1 basis-[11rem]">
            <p class="truncate font-semibold" x-text="name" :title="name"></p>
            <p class="text-sm text-fg-muted"><span x-text="size"></span> · {{ __('provider.doc_ready_text') }}</p>
        </div>
        <div class="flex flex-wrap gap-1 max-sm:basis-full">
            <a :href="previewUrl" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-ki name="eye" class="size-4" /> {{ __('provider.doc_preview') }}</a>
            <label for="{{ $name }}" class="btn btn-ghost btn-sm cursor-pointer"><x-ki name="refresh" class="size-4" /> {{ __('provider.doc_replace') }}</label>
            <button type="button" class="btn btn-ghost btn-sm text-danger-text hover:bg-danger-soft hover:text-danger-text" @click="clear()"><x-ki name="trash" class="size-4" /> {{ __('provider.doc_remove') }}</button>
        </div>
    </div>

    <p class="field-error" x-cloak x-show="error" role="alert"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="error"></span></p>
    @if ($error)
        <p id="{{ $name }}-error" class="field-error" role="alert"><x-ki name="alert" class="mt-0.5 size-4" /> {{ $error }}</p>
    @endif
</div>
