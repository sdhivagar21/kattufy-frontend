<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureStaffSession;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The staff panel at /admin (Architecture §2). There is no Filament login
 * page: staff sign in at /login/ with credentials, a mobile code and an
 * email code, and EnsureStaffSession checks that on every request.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Kattufy Admin')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::hex('#F2A900'),
                'gray' => Color::Slate,
                'success' => Color::hex('#275D4A'),
                'danger' => Color::hex('#B42318'),
                'warning' => Color::Amber,
                'info' => Color::Sky,
            ])
            ->font('Archivo Kattufy', url: asset('css/admin-fonts.css'), provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode(true)
            ->maxContentWidth(Width::SevenExtraLarge)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Verification'),
                NavigationGroup::make('Directory'),
                NavigationGroup::make('Website'),
                NavigationGroup::make('Administration'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->renderHook(PanelsRenderHook::TOPBAR_START, fn () => view('filament.demo-badge'))
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn () => view('filament.sidebar-profile'))
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_END, fn () => view('filament.sidebar-logout'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([EnsureStaffSession::class]);
    }
}
