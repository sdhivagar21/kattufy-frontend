<?php

use App\Support\Localized;

if (! function_exists('lroute')) {
    /** route() for the current language: lroute('login') → /login/ or /ta/login/. */
    function lroute(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return Localized::route($name, $parameters, $absolute);
    }
}

if (! function_exists('tr_field')) {
    /** Translated column value for the current language, falling back to English. */
    function tr_field(object $model, string $field): ?string
    {
        return Localized::pick($model, $field)[0];
    }
}

if (! function_exists('brand_image')) {
    /** URL of a configured brand image, or null when the file is missing. */
    function brand_image(string $key): ?string
    {
        $path = config("kattufy.brand.$key");

        return $path && is_file(public_path($path)) ? asset($path) : null;
    }
}

if (! function_exists('ist')) {
    /** Times are stored in UTC and shown in India Standard Time. */
    function ist(?\DateTimeInterface $time, string $format = 'j M Y'): string
    {
        return $time ? \Illuminate\Support\Carbon::instance($time)->timezone('Asia/Kolkata')->locale(app()->getLocale())->translatedFormat($format) : '';
    }
}
