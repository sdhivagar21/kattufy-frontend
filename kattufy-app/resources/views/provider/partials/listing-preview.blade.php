{{--
    Live listing preview inside a listingPlanner. Every block is gated by
    has(field) from App\Support\ListingPlans::FIELDS, so a Free preview can
    never show Pro or Premium information. Not public: a preview only.
--}}
<div class="overflow-hidden rounded-panel border bg-surface shadow-raised"
     :class="plan === 'premium' ? 'border-amber/50' : (plan === 'pro' ? 'border-pro-600/40' : 'border-line')">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-surface-2 px-4 py-3">
        <p class="flex items-center gap-2 text-sm font-semibold"><x-ki name="eye" class="size-4 text-fg-subtle" /> {{ __('plans.preview_title') }}</p>
        <span class="rounded-full bg-surface px-2.5 py-0.5 text-xs font-semibold text-fg-muted ring-1 ring-line">{{ __('plans.preview_badge') }}</span>
    </div>

    <article class="p-5">
        <div class="flex items-start gap-3">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-panel font-bold" lang="en"
                  :class="plan === 'premium' ? 'bg-amber text-ink' : (plan === 'pro' ? 'bg-pro-800 text-amber' : 'bg-ink text-amber dark:bg-white/10')">{{ $provider->initials() }}</span>
            <div class="min-w-0">
                <h3 class="type-heading text-lg" style="overflow-wrap:anywhere" x-text="form.name || @js($provider->business_name_en)"></h3>
                <p class="text-sm text-fg-subtle">{{ $provider->primaryCategory() ? tr_field($provider->primaryCategory(), 'name') : '' }}</p>
            </div>
        </div>

        <p x-show="has('description')" x-text="previewDescription || @js(__('plans.preview_empty'))"
           :class="previewDescription ? 'text-fg-muted' : 'text-fg-subtle italic'"
           class="mt-4 text-[0.95rem] leading-relaxed whitespace-pre-line" style="overflow-wrap:anywhere"></p>

        <dl class="mt-4 space-y-2 text-[0.95rem]">
            <div x-show="has('address') && form.address.trim()" class="flex gap-2"><dt class="sr-only">{{ __('plans.fields.address') }}</dt><x-ki name="map-pin" class="mt-0.5 size-4 text-fg-subtle" /><dd x-text="form.address" style="overflow-wrap:anywhere"></dd></div>
            <div x-show="has('email') && form.email.trim()" class="flex gap-2"><dt class="sr-only">{{ __('plans.fields.email') }}</dt><x-ki name="mail" class="mt-0.5 size-4 text-fg-subtle" /><dd x-text="form.email" style="overflow-wrap:anywhere"></dd></div>
            <div x-show="has('phone') && form.phone.trim()" class="flex gap-2"><dt class="sr-only">{{ __('plans.fields.phone') }}</dt><x-ki name="phone" class="mt-0.5 size-4 text-fg-subtle" /><dd lang="en" x-text="formatPhone(form.phone)"></dd></div>
            <div x-show="has('website') && websiteHost && !errors.website" class="flex gap-2"><dt class="sr-only">{{ __('plans.fields.website') }}</dt><x-ki name="globe" class="mt-0.5 size-4 text-fg-subtle" /><dd><span class="link" x-text="websiteHost"></span></dd></div>
        </dl>

        <div x-show="(has('phone') && form.phone.trim()) || (has('whatsapp') && form.whatsappEnabled)" class="mt-4 grid grid-cols-2 gap-2">
            <span x-show="has('phone') && form.phone.trim()" class="btn btn-primary btn-sm"><x-ki name="phone" class="size-4" /> {{ __('plans.call') }}</span>
            <span x-show="has('whatsapp') && form.whatsappEnabled" class="btn btn-sm bg-[#1f8f4e] text-white"><x-ki name="chat" class="size-4" /> {{ __('plans.whatsapp') }}</span>
        </div>

        <section x-show="has('photos')" class="mt-5">
            <h4 class="text-sm font-semibold">{{ __('plans.section_media') }}</h4>
            <div x-show="previewPhotos.length" class="mt-2 grid grid-cols-3 gap-1.5">
                <template x-for="item in previewPhotos" :key="item.id">
                    <div class="relative aspect-[4/3] overflow-hidden rounded-md bg-surface-2">
                        <template x-if="item.kind === 'image'"><img :src="item.url" alt="" class="size-full object-cover"></template>
                    </div>
                </template>
            </div>
            <p x-show="!previewPhotos.length" class="mt-1 text-sm text-fg-subtle">{{ __('plans.no_photos') }}</p>
            <p x-show="media.videos.length || form.videoLinks.length" class="mt-2 flex items-center gap-1.5 text-xs text-fg-subtle"><x-ki name="film" class="size-3.5" />
                <span x-text="count(@js([trans_choice('plans.videos_count', 0), trans_choice('plans.videos_count', 1), trans_choice('plans.videos_count', 2, ['count' => ':count'])]), media.videos.length + form.videoLinks.length)"></span></p>
        </section>

        <section x-show="has('services')" class="mt-5">
            <h4 class="text-sm font-semibold">{{ __('plans.services') }}</h4>
            <ul class="mt-2 flex flex-wrap gap-1.5">
                <template x-for="service in form.services" :key="service.id">
                    <li x-show="service.name.trim()" class="rounded-tag bg-success-soft px-2 py-1 text-xs font-semibold text-success-text" x-text="service.name"></li>
                </template>
            </ul>
            <p x-show="!form.services.length" class="text-sm text-fg-subtle">{{ __('plans.no_services') }}</p>
        </section>

        <section x-show="has('projects')" class="mt-5">
            <h4 class="text-sm font-semibold">{{ __('plans.projects') }}</h4>
            <ul class="mt-2 space-y-1.5 text-sm">
                <template x-for="project in form.projects" :key="project.id">
                    <li x-show="project.name.trim()" class="flex justify-between gap-2"><span class="min-w-0 font-medium" style="overflow-wrap:anywhere" x-text="project.name"></span><span class="shrink-0 text-fg-subtle" x-text="[project.place, project.year].filter(Boolean).join(', ')"></span></li>
                </template>
            </ul>
            <p x-show="!form.projects.length" class="text-sm text-fg-subtle">{{ __('plans.no_projects') }}</p>
        </section>
    </article>

    <p class="border-t border-line px-4 py-2.5 text-xs text-fg-subtle" x-text="@js(__('plans.preview_note', ['plan' => '__PLAN__'])).replace('__PLAN__', planName())"></p>
</div>
