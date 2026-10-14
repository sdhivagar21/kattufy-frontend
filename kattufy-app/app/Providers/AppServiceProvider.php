<?php

namespace App\Providers;

use App\Http\Responses\AdminLogoutResponse;
use App\Models\User;
use App\Modules\Content\Models\ContentPage;
use App\Modules\Enquiries\Models\Enquiry;
use App\Modules\Providers\Models\Media;
use App\Modules\Providers\Models\PortfolioItem;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Models\Credential;
use App\Modules\Directory\Models\Category;
use App\Policies\ProviderPolicy;
use App\Support\Sms\DemoSmsGateway;
use App\Support\Sms\LogSmsGateway;
use App\Support\Sms\SmsGateway;
use App\Support\TrailingSlashUrlGenerator;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, fn () => match (config('kattufy.sms.driver')) {
            'log' => new LogSmsGateway,
            // A production gateway is added here (see README, "SMS gateway").
            default => new DemoSmsGateway,
        });

        $this->app->bind(LogoutResponse::class, AdminLogoutResponse::class);

        // Same generator as Laravel's, plus trailing slashes on public page URLs.
        $this->app->extend('url', function ($url, $app) {
            $generator = new TrailingSlashUrlGenerator($app['router']->getRoutes(), $app['request'], $app['config']['app.asset_url']);
            $generator->setSessionResolver(fn () => $app['session'] ?? null);
            $generator->setKeyResolver(fn () => [$app['config']['app.key'], ...($app['config']['app.previous_keys'] ?? [])]);
            $app->rebinding('routes', fn ($app, $routes) => $app['url']->setRoutes($routes));

            return $generator;
        });
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'provider' => Provider::class,
            'credential' => Credential::class,
            'portfolio_item' => PortfolioItem::class,
            'media' => Media::class,
            'enquiry' => Enquiry::class,
            'category' => Category::class,
            'content_page' => ContentPage::class,
        ]);

        Gate::policy(Provider::class, ProviderPolicy::class);
        Gate::policy(Enquiry::class, \App\Policies\EnquiryPolicy::class);
        Gate::policy(Category::class, \App\Policies\CategoryPolicy::class);
        Gate::policy(ContentPage::class, \App\Policies\ContentPagePolicy::class);
        Gate::policy(User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\Spatie\Activitylog\Models\Activity::class, \App\Policies\ActivityPolicy::class);

        // Super Admin can do everything staff can (Architecture §8 matrix), but
        // only inside a session that passed both admin codes.
        Gate::before(fn (User $user) => $user->hasRole('super_admin') && session('admin_verified_user_id') === $user->id ? true : null);

        $this->rateLimits();
    }

    private function rateLimits(): void
    {
        // Requests that send a code; the per-phone and per-IP hourly caps live in OtpService.
        RateLimiter::for('otp-request', fn (Request $r) => Limit::perMinute(6)->by('otp-request|'.$r->ip()));

        // Code guesses; each code also dies after 5 wrong attempts.
        RateLimiter::for('otp-check', fn (Request $r) => Limit::perMinute(12)->by('otp-check|'.$r->ip()));

        RateLimiter::for('reveal', fn (Request $r) => Limit::perMinute(30)->by('reveal|'.($r->user()?->id ?: $r->ip())));

        RateLimiter::for('enquiry', fn (Request $r) => Limit::perHour(10)->by('enquiry|'.($r->user()?->id ?: $r->ip())));
    }
}
