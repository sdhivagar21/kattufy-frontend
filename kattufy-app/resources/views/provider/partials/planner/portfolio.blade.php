{{-- Plan form: Services offered and previous projects (Premium). Inside a listingPlanner. --}}
<div class="space-y-6">
    {{-- Services --}}
    <fieldset class="card overflow-hidden">
        <legend class="sr-only">{{ __('plans.services') }}</legend>
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
            <div>
                <h2 class="type-heading flex items-center gap-2.5 text-lg"><x-ki name="briefcase" class="size-5 text-fg-subtle" /> {{ __('plans.services') }}
                    <span class="text-sm font-normal text-fg-subtle tabular-nums" x-text="'(' + form.services.length + ')'"></span></h2>
                <p class="mt-0.5 text-sm text-fg-subtle">{{ __('plans.services_hint') }}</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" @click="addItem('services')"><x-ki name="plus" class="size-4" /> {{ __('plans.add_service') }}</button>
        </div>

        <p x-show="!form.services.length" class="px-5 py-8 text-center text-fg-subtle sm:px-6">{{ __('plans.no_services') }}</p>

        <ol class="divide-y divide-line" data-list="services">
            <template x-for="(service, index) in form.services" :key="service.id">
                <li class="grid grid-cols-1 gap-3 px-5 py-4 sm:grid-cols-[2rem_minmax(0,1fr)_auto] sm:px-6">
                    <span class="hidden size-8 items-center justify-center rounded-full bg-surface-2 text-sm font-bold text-fg-muted sm:flex" x-text="index + 1" aria-hidden="true"></span>
                    <div class="grid min-w-0 grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="field-label text-sm" :for="'service-name-' + service.id">{{ __('plans.service_name') }} <span class="text-danger-text" aria-hidden="true">*</span></label>
                            <input type="text" maxlength="120" class="input" :id="'service-name-' + service.id" x-model="service.name"
                                   @blur="touch('services.' + index)" :aria-invalid="invalid('services.' + index)">
                            <p class="field-error" role="alert" x-show="showError('services.' + index)"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('services.' + index)"></span></p>
                        </div>
                        <div>
                            <label class="field-label text-sm" :for="'service-description-' + service.id">{{ __('plans.service_description') }} <span class="ml-1 font-normal text-fg-subtle">({{ __('ui.optional') }})</span></label>
                            <input type="text" maxlength="200" class="input" :id="'service-description-' + service.id" x-model="service.description">
                        </div>
                    </div>
                    <div class="flex items-center gap-1 sm:flex-col sm:items-end sm:pt-7">
                        <span class="flex">
                            <button type="button" class="btn btn-ghost btn-sm px-2" @click="moveItem('services', index, -1)" :disabled="index === 0" :aria-label="@js(__('plans.move_up')) + ': ' + (service.name || @js(__('plans.service_n')).replace(':n', index + 1))"><x-ki name="chevron-down" class="size-4 rotate-180" /></button>
                            <button type="button" class="btn btn-ghost btn-sm px-2" @click="moveItem('services', index, 1)" :disabled="index === form.services.length - 1" :aria-label="@js(__('plans.move_down')) + ': ' + (service.name || @js(__('plans.service_n')).replace(':n', index + 1))"><x-ki name="chevron-down" class="size-4" /></button>
                        </span>
                        <button type="button" class="btn btn-ghost btn-sm ml-auto text-danger-text hover:bg-danger-soft hover:text-danger-text sm:ml-0" @click="removeItem('services', index)"><x-ki name="trash" class="size-4" /> {{ __('plans.remove') }}</button>
                    </div>
                </li>
            </template>
        </ol>
    </fieldset>

    {{-- Previous projects --}}
    <fieldset class="card overflow-hidden">
        <legend class="sr-only">{{ __('plans.projects') }}</legend>
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
            <div>
                <h2 class="type-heading flex items-center gap-2.5 text-lg"><x-ki name="building" class="size-5 text-fg-subtle" /> {{ __('plans.projects') }}
                    <span class="text-sm font-normal text-fg-subtle tabular-nums" x-text="'(' + form.projects.length + ')'"></span></h2>
                <p class="mt-0.5 text-sm text-fg-subtle">{{ __('plans.projects_hint') }}</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" @click="addItem('projects')"><x-ki name="plus" class="size-4" /> {{ __('plans.add_project') }}</button>
        </div>

        <p x-show="!form.projects.length" class="px-5 py-8 text-center text-fg-subtle sm:px-6">{{ __('plans.no_projects') }}</p>

        <ol class="divide-y divide-line" data-list="projects">
            <template x-for="(project, index) in form.projects" :key="project.id">
                <li class="px-5 py-5 sm:px-6">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-fg-muted" x-text="@js(__('plans.project_n')).replace(':n', index + 1)"></p>
                        <div class="flex items-center gap-1">
                            <button type="button" class="btn btn-ghost btn-sm px-2" @click="moveItem('projects', index, -1)" :disabled="index === 0" :aria-label="@js(__('plans.move_up')) + ': ' + (project.name || @js(__('plans.project_n')).replace(':n', index + 1))"><x-ki name="chevron-down" class="size-4 rotate-180" /></button>
                            <button type="button" class="btn btn-ghost btn-sm px-2" @click="moveItem('projects', index, 1)" :disabled="index === form.projects.length - 1" :aria-label="@js(__('plans.move_down')) + ': ' + (project.name || @js(__('plans.project_n')).replace(':n', index + 1))"><x-ki name="chevron-down" class="size-4" /></button>
                            <button type="button" class="btn btn-ghost btn-sm text-danger-text hover:bg-danger-soft hover:text-danger-text" @click="removeItem('projects', index)"><x-ki name="trash" class="size-4" /> {{ __('plans.remove') }}</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                        <div class="sm:col-span-6">
                            <label class="field-label text-sm" :for="'project-name-' + project.id">{{ __('plans.project_name') }} <span class="text-danger-text" aria-hidden="true">*</span></label>
                            <input type="text" maxlength="120" class="input" :id="'project-name-' + project.id" x-model="project.name"
                                   @blur="touch('projects.' + index + '.name')" :aria-invalid="invalid('projects.' + index + '.name')">
                            <p class="field-error" role="alert" x-show="showError('projects.' + index + '.name')"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('projects.' + index + '.name')"></span></p>
                        </div>
                        <div class="sm:col-span-4">
                            <label class="field-label text-sm" :for="'project-place-' + project.id">{{ __('plans.project_place') }}</label>
                            <input type="text" maxlength="80" class="input" :id="'project-place-' + project.id" x-model="project.place">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label text-sm" :for="'project-year-' + project.id">{{ __('plans.project_year') }}</label>
                            <input type="text" inputmode="numeric" maxlength="4" placeholder="{{ date('Y') }}" class="input tabular-nums" :id="'project-year-' + project.id" x-model="project.year"
                                   @blur="touch('projects.' + index + '.year')" :aria-invalid="invalid('projects.' + index + '.year')">
                            <p class="field-error" role="alert" x-show="showError('projects.' + index + '.year')"><x-ki name="alert" class="mt-0.5 size-4" /> <span x-text="showError('projects.' + index + '.year')"></span></p>
                        </div>
                        <div class="sm:col-span-6">
                            <label class="field-label text-sm" :for="'project-description-' + project.id">{{ __('plans.project_description') }}</label>
                            <textarea rows="3" maxlength="500" class="input min-h-24" :id="'project-description-' + project.id" x-model="project.description"></textarea>
                        </div>
                    </div>
                </li>
            </template>
        </ol>
    </fieldset>
</div>
