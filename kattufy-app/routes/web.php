<?php

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OtpVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RoleSelectionController;
use App\Http\Controllers\Public\AccountController;
use App\Http\Controllers\Public\ContactRevealController;
use App\Http\Controllers\Public\ContentController;
use App\Http\Controllers\Public\DirectoryController;
use App\Http\Controllers\Public\DocumentController;
use App\Http\Controllers\Public\EnquiryController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\SplashController;
use App\Http\Controllers\Provider\ProviderDashboardController;
use App\Http\Controllers\Provider\ProviderRegistrationController;
use App\Http\Controllers\Provider\VerificationStatusController;
use App\Http\Controllers\User\UserDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Language-neutral endpoints
|--------------------------------------------------------------------------
*/

// Private credential documents: short-lived signed link + login + policy check.
Route::get('documents/{media}', DocumentController::class)
    ->middleware(['auth', 'signed'])
    ->name('documents.show');

// The only way a provider number leaves the server (Architecture §6C).
Route::post('contact/{provider}/reveal', ContactRevealController::class)
    ->middleware(['member', 'throttle:reveal'])
    ->name('contact.reveal');

Route::post('logout', LogoutController::class)->name('logout');

/*
|--------------------------------------------------------------------------
| Pages, registered for English (/) and Tamil (/ta/) with identical paths
|--------------------------------------------------------------------------
*/

// First URL segments that are never a directory category or group.
$reserved = 'admin|livewire|filament|storage|build|images|media|fonts|documents|contact|logout|up|ta|login|register|select-role|account|user|provider|search|enquiry|guides|faq|join|privacy|terms|disclaimer|how-listings-are-ordered';
$segment = '(?!(?:'.$reserved.')\b)[a-z0-9]+(?:-[a-z0-9]+)*';

$pages = function () use ($segment) {
    Route::get('/', SplashController::class)->name('home');

    // Sign-in and registration (guests only).
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'show'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:otp-request')->name('login.store');
        Route::get('register', [RegisterController::class, 'show'])->name('register');
        Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:otp-request')->name('register.store');
    });

    // Code entry for login, registration and a provider's business phone.
    Route::get('login/verify', [OtpVerificationController::class, 'show'])->name('verify');
    Route::post('login/verify', [OtpVerificationController::class, 'store'])->middleware('throttle:otp-check')->name('verify.store');
    Route::post('login/resend', [OtpVerificationController::class, 'resend'])->middleware('throttle:otp-request')->name('verify.resend');

    // Super Admin sign-in: credentials, then mobile OTP, then email OTP.
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('login', [AdminLoginController::class, 'show'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->middleware('throttle:otp-request')->name('login.store');
        Route::get('verify-mobile', [AdminLoginController::class, 'showMobile'])->name('verify-mobile');
        Route::post('verify-mobile', [AdminLoginController::class, 'verifyMobile'])->middleware('throttle:otp-check')->name('verify-mobile.store');
        Route::get('verify-email', [AdminLoginController::class, 'showEmail'])->name('verify-email');
        Route::post('verify-email', [AdminLoginController::class, 'verifyEmail'])->middleware('throttle:otp-check')->name('verify-email.store');
        Route::post('resend', [AdminLoginController::class, 'resend'])->middleware('throttle:otp-request')->name('resend');
    });

    // Legal pages stay readable without an account.
    Route::get('privacy', [ContentController::class, 'legal'])->defaults('slug', 'privacy')->name('privacy');
    Route::get('terms', [ContentController::class, 'legal'])->defaults('slug', 'terms')->name('terms');
    Route::get('disclaimer', [ContentController::class, 'legal'])->defaults('slug', 'disclaimer')->name('disclaimer');

    Route::middleware('member')->group(function () use ($segment) {
        Route::get('select-role', [RoleSelectionController::class, 'show'])->middleware('role.unchosen')->name('role.select');
        Route::post('select-role', [RoleSelectionController::class, 'store'])->middleware('role.unchosen')->name('role.store');
        Route::get('account', AccountController::class)->name('account');

        // User area.
        Route::prefix('user')->name('user.')->middleware('area:customer')->group(function () {
            Route::get('dashboard', [UserDashboardController::class, 'dashboard'])->name('dashboard');
            Route::get('profile', [UserDashboardController::class, 'profile'])->name('profile');
            Route::put('profile', [UserDashboardController::class, 'updateProfile'])->name('profile.update');
            Route::get('faqs', [UserDashboardController::class, 'faqs'])->name('faqs');
        });

        // Provider area.
        Route::prefix('provider')->name('provider.')->middleware('area:provider')->group(function () {
            Route::get('register', [ProviderRegistrationController::class, 'show'])->name('register');
            Route::post('register', [ProviderRegistrationController::class, 'store'])->name('register.store');
            Route::get('verification', VerificationStatusController::class)->name('verification');

            Route::middleware('provider.approved')->group(function () {
                Route::get('dashboard', [ProviderDashboardController::class, 'dashboard'])->name('dashboard');
                Route::get('profile', [ProviderDashboardController::class, 'profile'])->name('profile');
                Route::put('profile', [ProviderDashboardController::class, 'update'])->name('profile.update');
                // Listing plans (front end only: the choice is kept in the browser for now).
                Route::get('plan', [ProviderDashboardController::class, 'plan'])->name('plan');
                Route::get('services-and-projects', [ProviderDashboardController::class, 'portfolio'])->name('portfolio');
                Route::get('media', [ProviderDashboardController::class, 'media'])->name('media');
                Route::get('account', [ProviderDashboardController::class, 'account'])->name('account');
            });
        });

        // Directory and help pages (any signed-in account).
        Route::get('search', SearchController::class)->name('search');
        Route::get('enquiry', [EnquiryController::class, 'create'])->name('enquiry');
        Route::post('enquiry', [EnquiryController::class, 'store'])->middleware('throttle:enquiry')->name('enquiry.store');
        Route::get('enquiry/sent/{reference}', [EnquiryController::class, 'sent'])->name('enquiry.sent');
        Route::get('faq', [ContentController::class, 'faq'])->name('faq');
        Route::get('how-listings-are-ordered', [ContentController::class, 'page'])->defaults('slug', 'how-listings-are-ordered')->name('ranking');
        Route::get('guides', [ContentController::class, 'guides'])->name('guides');
        Route::get('guides/{slug}', [ContentController::class, 'guide'])->name('guides.show');
        Route::get('join', [ContentController::class, 'join'])->name('join');

        // /architects/, /building-a-house/, /architects/chennai/, /architects/{provider}/, /architects/chennai/velachery/
        Route::get('{category}', [DirectoryController::class, 'section'])->where('category', $segment)->name('directory.section');
        Route::get('{category}/{slug}', [DirectoryController::class, 'child'])->where(['category' => $segment, 'slug' => '[a-z0-9-]+'])->name('directory.child');
        Route::get('{category}/{city}/{locality}', [DirectoryController::class, 'locality'])->where(['category' => $segment, 'city' => '[a-z0-9-]+', 'locality' => '[a-z0-9-]+'])->name('directory.locality');
    });
};

Route::middleware('locale:en')->group($pages);
Route::prefix('ta')->name('ta.')->middleware('locale:ta')->group($pages);
