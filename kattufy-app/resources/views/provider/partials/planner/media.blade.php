{{--
    Plan form: business photographs, project photographs and project videos
    (Premium). Files are checked and previewed in this tab only: upload
    storage is not connected, so nothing is sent to Kattufy.
--}}
<div class="space-y-6">
    <x-alert tone="info" icon="info">{{ __('plans.media_session_note') }}</x-alert>

    @foreach ([
        'business' => ['photo', __('plans.business_photos'), __('plans.business_photos_hint'), __('plans.choose_photos'), __('plans.photo_rules', ['size' => 8, 'count' => 12]), 'image/jpeg,image/png,image/webp', __('plans.no_photos')],
        'project' => ['photo', __('plans.project_photos'), __('plans.project_photos_hint'), __('plans.choose_photos'), __('plans.photo_rules', ['size' => 8, 'count' => 24]), 'image/jpeg,image/png,image/webp', __('plans.no_photos')],
        'videos' => ['film', __('plans.project_videos'), __('plans.project_videos_hint'), __('plans.choose_videos'), __('plans.video_rules', ['size' => 50, 'count' => 4]), 'video/mp4,video/webm', __('plans.no_videos')],
    ] as $kind => [$icon, $heading, $hint, $chooseLabel, $rules, $accept, $empty])
        <section class="card overflow-hidden" aria-labelledby="media-{{ $kind }}-title">
            <div class="border-b border-line px-5 py-4 sm:px-6">
                <h2 id="media-{{ $kind }}-title" class="type-heading flex items-center gap-2.5 text-lg"><x-ki :name="$icon" class="size-5 text-fg-subtle" /> {{ $heading }}
                    <span class="text-sm font-normal text-fg-subtle tabular-nums" x-text="'(' + media.{{ $kind }}.length + ')'"></span></h2>
                <p class="mt-0.5 text-sm text-fg-subtle">{{ $hint }}</p>
            </div>

            <div class="space-y-4 p-5 sm:p-6">
                {{-- Drop zone --}}
                <label for="media-input-{{ $kind }}"
                       @dragover.prevent="dragging = '{{ $kind }}'" @dragleave.prevent="dragging = ''" @drop.prevent="dropMedia('{{ $kind }}', $event)"
                       :class="dragging === '{{ $kind }}' ? 'border-amber bg-accent-soft' : 'border-line-strong bg-sunken hover:border-fg-subtle'"
                       class="flex cursor-pointer flex-col items-center gap-2 rounded-panel border-2 border-dashed px-5 py-7 text-center transition-colors sm:flex-row sm:gap-4 sm:text-left">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-surface text-amber-700 ring-1 ring-line dark:text-amber"><x-ki name="upload" class="size-5" /></span>
                    <span class="min-w-0">
                        <span class="block font-semibold">{{ $chooseLabel }} <span class="font-normal text-fg-subtle">{{ __('plans.drop_here') }}</span></span>
                        <span class="block text-sm text-fg-subtle">{{ $rules }}</span>
                    </span>
                    <input id="media-input-{{ $kind }}" type="file" multiple accept="{{ $accept }}" class="sr-only" @change="pickMedia('{{ $kind }}', $event)">
                </label>

                <div aria-live="polite">
                    <p x-show="mediaNotice.{{ $kind }}" x-text="mediaNotice.{{ $kind }}" class="flex items-center gap-2 text-sm font-medium text-success-text"></p>
                    <template x-for="error in mediaErrors.{{ $kind }}"><p class="field-error" role="alert" x-text="error"></p></template>
                </div>

                <p x-show="!media.{{ $kind }}.length" class="py-2 text-center text-sm text-fg-subtle">{{ $empty }}</p>

                {{-- Previews --}}
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <template x-for="(item, index) in media.{{ $kind }}" :key="item.id">
                        <li class="group flex min-w-0 flex-col overflow-hidden rounded-control border border-line bg-surface shadow-card">
                            <button type="button" @click="openViewer('{{ $kind }}', index)" class="relative block aspect-[4/3] w-full overflow-hidden bg-surface-2" :aria-label="@js(__('plans.view')) + ': ' + item.name">
                                <template x-if="item.kind === 'image'"><img :src="item.url" alt="" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]" loading="lazy"></template>
                                <template x-if="item.kind === 'video'"><video :src="item.url" muted preload="metadata" class="size-full object-cover"></video></template>
                                <span x-show="item.kind === 'video'" class="absolute inset-0 m-auto flex size-11 items-center justify-center rounded-full bg-ink/75 text-white"><span class="ml-1 size-0 border-y-[7px] border-l-[12px] border-y-transparent border-l-white"></span></span>
                                <span x-show="!item.local" class="absolute top-2 left-2 rounded-full bg-ink/80 px-2 py-0.5 text-[0.7rem] font-semibold text-white">{{ __('plans.on_current_listing') }}</span>
                            </button>
                            <div class="flex flex-1 flex-col gap-2 p-2.5">
                                <p class="truncate text-xs text-fg-subtle" :title="item.name"><span x-text="item.name"></span><span x-show="item.size" x-text="' · ' + item.size"></span></p>
                                @if ($kind === 'project')
                                    <label class="sr-only" :for="'photo-project-' + item.id">{{ __('plans.photo_project') }}</label>
                                    <select class="input min-h-9 py-1 text-sm" :id="'photo-project-' + item.id" x-model="item.project" x-show="form.projects.length">
                                        <option value="">{{ __('plans.no_project') }}</option>
                                        <template x-for="project in form.projects" :key="project.id"><option :value="project.id" x-text="project.name || '—'" :selected="item.project === project.id"></option></template>
                                    </select>
                                @endif
                                <div class="mt-auto flex items-center justify-between gap-1">
                                    <span class="flex">
                                        <button type="button" class="btn btn-ghost btn-sm px-2" @click="moveMedia('{{ $kind }}', index, -1)" :disabled="index === 0"><span class="sr-only">{{ __('plans.move_up') }}</span><x-ki name="arrow-left" class="size-4" /></button>
                                        <button type="button" class="btn btn-ghost btn-sm px-2" @click="moveMedia('{{ $kind }}', index, 1)" :disabled="index === media.{{ $kind }}.length - 1"><span class="sr-only">{{ __('plans.move_down') }}</span><x-ki name="arrow-right" class="size-4" /></button>
                                    </span>
                                    <button type="button" class="btn btn-ghost btn-sm px-2 text-danger-text hover:bg-danger-soft hover:text-danger-text" @click="removeMedia('{{ $kind }}', index)" :aria-label="@js(__('plans.remove_file', ['name' => '__NAME__'])).replace('__NAME__', item.name)"><x-ki name="trash" class="size-4" /></button>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>

                @if ($kind === 'videos')
                    {{-- Video links --}}
                    <div class="border-t border-line pt-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="flex items-center gap-2 font-semibold"><x-ki name="link" class="size-4 text-fg-subtle" /> {{ __('plans.video_links') }}</h3>
                            <button type="button" class="btn btn-secondary btn-sm" @click="addItem('videoLinks')"><x-ki name="plus" class="size-4" /> {{ __('plans.add_video_link') }}</button>
                        </div>
                        <ol class="mt-3 space-y-3" data-list="videoLinks">
                            <template x-for="(link, index) in form.videoLinks" :key="link.id">
                                <li>
                                    <div class="flex gap-2">
                                        <label class="sr-only" :for="'video-link-' + link.id">{{ __('plans.video_link') }}</label>
                                        <input type="url" inputmode="url" maxlength="255" placeholder="https://www.youtube.com/watch?v=…" class="input" :id="'video-link-' + link.id"
                                               x-model.trim="link.url" @blur="touch('videoLinks.' + index)" :aria-invalid="invalid('videoLinks.' + index)">
                                        <button type="button" class="btn btn-ghost min-h-12 shrink-0 px-3 text-danger-text hover:bg-danger-soft hover:text-danger-text" @click="removeItem('videoLinks', index)"><x-ki name="trash" class="size-4" /><span class="sr-only">{{ __('plans.remove') }}</span></button>
                                    </div>
                                    <p class="field-error" role="alert" x-show="showError('videoLinks.' + index)"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('videoLinks.' + index)"></span></p>
                                </li>
                            </template>
                        </ol>
                    </div>
                @endif
            </div>
        </section>
    @endforeach

    {{-- Viewer --}}
    <dialog x-ref="viewer" @close="viewer = null" @keydown.arrow-left="stepViewer(-1)" @keydown.arrow-right="stepViewer(1)"
            class="m-auto w-[min(64rem,calc(100vw-2rem))] max-w-none overflow-hidden rounded-panel bg-ink p-0 text-white shadow-raised backdrop:bg-ink/85">
        <template x-if="viewerItem">
            <div>
                <div class="flex items-center justify-between gap-3 px-4 py-3">
                    <p class="truncate text-sm" x-text="viewerItem.name"></p>
                    <button type="button" @click="closeViewer()" class="btn btn-sm border border-white/25 text-white hover:bg-white/10">{{ __('plans.close') }}</button>
                </div>
                <div class="flex items-center justify-center bg-black">
                    <template x-if="viewerItem.kind === 'image'"><img :src="viewerItem.url" :alt="viewerItem.name" class="max-h-[75vh] w-auto"></template>
                    <template x-if="viewerItem.kind === 'video'"><video :src="viewerItem.url" controls playsinline class="max-h-[75vh] w-full"></video></template>
                </div>
                <div class="flex justify-between px-4 py-3">
                    <button type="button" @click="stepViewer(-1)" class="btn btn-sm border border-white/25 text-white hover:bg-white/10"><x-ki name="arrow-left" class="size-4" /> {{ __('plans.previous') }}</button>
                    <button type="button" @click="stepViewer(1)" class="btn btn-sm border border-white/25 text-white hover:bg-white/10">{{ __('plans.next_item') }} <x-ki name="arrow-right" class="size-4" /></button>
                </div>
            </div>
        </template>
    </dialog>
</div>
