{{-- A category that is not Live in this city yet: no listing, offer the fallback enquiry (noindex). --}}
<x-layouts.site :title="tr_field($category, 'name').' — '.__('directory.coming_soon')" noindex>
    <div class="container-page py-12 sm:py-16">
        @include('directory.partials.breadcrumbs', ['crumbs' => [__('ui.home') => \App\Modules\Accounts\Services\AccountHome::url(auth()->user()), tr_field($category, 'name') => lroute('directory.section', $category->slug), tr_field($city, 'name') => null]])
        <x-empty-state class="mt-6" icon="clock" :title="__('directory.coming_soon_in', ['category' => tr_field($category, 'name'), 'city' => tr_field($city, 'name')])" :text="__('directory.coming_soon_text', ['city' => tr_field($city, 'name')])">
            <x-slot:actions>
                <a href="{{ lroute('enquiry') }}?category={{ $category->slug }}&source=coming_soon" class="btn btn-primary"><x-ki name="chat" class="size-5" /> {{ __('ui.send_enquiry') }}</a>
                <a href="{{ lroute('directory.section', $category->group->slug) }}" class="btn btn-secondary">{{ __('directory.other_services') }}</a>
            </x-slot:actions>
        </x-empty-state>
    </div>
</x-layouts.site>
