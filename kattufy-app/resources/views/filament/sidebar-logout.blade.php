{{-- Staff panel sidebar: log out after the navigation, as on the User and Provider dashboards. --}}
<form method="POST" action="{{ filament()->getLogoutUrl() }}">
    @csrf
    <ul class="fi-sidebar-nav-groups">
        <li class="fi-sidebar-item">
            <button type="submit" class="fi-sidebar-item-btn w-full text-start" x-bind:aria-label="$store.sidebar.isOpen ? null : @js(__('ui.logout'))">
                {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedArrowLeftEndOnRectangle, attributes: (new \Filament\Support\View\ComponentAttributeBag)->class(['fi-sidebar-item-icon']), size: \Filament\Support\Enums\IconSize::Large) }}
                <span x-show="$store.sidebar.isOpen" class="fi-sidebar-item-label">{{ __('ui.logout') }}</span>
            </button>
        </li>
    </ul>
</form>
