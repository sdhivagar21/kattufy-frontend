<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    | The slogan is fixed copy and must keep its final full stop. Image paths
    | are relative to public/. Replace the files in public/images/ (keeping
    | the names) or point these settings at new files.
    */
    'brand' => [
        'name' => 'Kattufy',
        'slogan' => 'Where Trust Takes Shape.',
        'logo_on_dark' => env('KATTUFY_LOGO_ON_DARK', 'images/kattufy-mark.png'),
        'logo_on_light' => env('KATTUFY_LOGO_ON_LIGHT', 'images/kattufy-mark-dark.png'),
        'splash_image' => env('KATTUFY_SPLASH_IMAGE', 'images/kattufy-splash.jpg'),
        'splash_image_portrait' => env('KATTUFY_SPLASH_IMAGE_PORTRAIT', 'images/kattufy-splash-portrait.jpg'),
        // How long the splash stays before moving on, in milliseconds.
        'splash_duration_ms' => 1800,
    ],

    'locales' => ['en', 'ta'],

    // Redirect /login → /login/ (one canonical URL per page). Off in automated
    // tests, whose HTTP client strips trailing slashes from request paths.
    'trailing_slash_redirect' => (bool) env('KATTUFY_TRAILING_SLASH_REDIRECT', true),

    // Launch region (Architecture section 9).
    'launch_city' => 'chennai',

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    | Fixed codes, no SMS or email leaves the server, and the demo access panel
    | is shown on the login page. Never enable on a public production server.
    */
    'demo_mode' => (bool) env('KATTUFY_DEMO_MODE', false),
    'demo_codes' => [
        'mobile' => (string) env('KATTUFY_DEMO_MOBILE_CODE', '123456'),
        'email' => (string) env('KATTUFY_DEMO_EMAIL_CODE', '654321'),
    ],

    // When true every page outside sign-in, registration and legal pages
    // requires a logged-in account (current product decision).
    'require_login' => (bool) env('KATTUFY_REQUIRE_LOGIN', true),

    /*
    |--------------------------------------------------------------------------
    | One-time codes (Architecture section 6B and 7)
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'length' => 6,
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'resend_gap_seconds' => 60,
        // Codes allowed per hour for one number (per purpose) and for one IP
        // address. Keep the defaults in production; raise them locally, where
        // every browser on this computer shares 127.0.0.1.
        'max_per_phone_per_hour' => (int) env('KATTUFY_OTP_MAX_PER_PHONE_PER_HOUR', 5),
        'max_per_ip_per_hour' => (int) env('KATTUFY_OTP_MAX_PER_IP_PER_HOUR', 10),
        // Stops runaway SMS cost from SMS-pumping fraud.
        'daily_sms_cap' => (int) env('KATTUFY_DAILY_SMS_CAP', 2000),
    ],

    'sms' => [
        'driver' => env('KATTUFY_SMS_DRIVER', 'demo'),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'staff_session_hours' => (int) env('KATTUFY_STAFF_SESSION_HOURS', 8),

    // Contact reveal limit per customer per day (Architecture section 6C).
    'reveal_limit_per_day' => (int) env('KATTUFY_REVEAL_LIMIT', 20),

    // Category-city pair turns Live at this many published providers.
    'live_threshold' => (int) env('KATTUFY_LIVE_THRESHOLD', 10),
    'locality_page_threshold' => (int) env('KATTUFY_LOCALITY_PAGE_THRESHOLD', 5),

    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Uploads (Architecture section 7)
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        'max_kb' => 8192,
        'document_disk' => 'local', // storage/app/private, never web-served
        'document_link_minutes' => 5,
    ],
];
