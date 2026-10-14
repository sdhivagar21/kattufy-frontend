<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * English lives at the root and Tamil under /ta/ with identical paths
 * (Architecture section 10). Every public route is registered twice: "name"
 * and "ta.name". These helpers pick the right one for the current language.
 */
class Localized
{
    public static function isTamil(): bool
    {
        return app()->getLocale() === 'ta';
    }

    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $base = str_starts_with($name, 'ta.') ? substr($name, 3) : $name;

        return $locale === 'ta' && Route::has('ta.'.$base) ? 'ta.'.$base : $base;
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return route(self::routeName($name), $parameters, $absolute);
    }

    /** The current page in the other language (falls back to that language's home). */
    public static function switchUrl(string $locale): string
    {
        $route = request()->route();
        $name = $route?->getName();

        if ($name && Route::has(self::routeName($name, $locale))) {
            $query = request()->query();

            return route(self::routeName($name, $locale), $route->parameters()).($query ? '?'.http_build_query($query) : '');
        }

        return route(self::routeName('home', $locale));
    }

    /**
     * Pick the column for the current language: name_ta in Tamil when filled,
     * otherwise name_en. Returns [text, langUsed] so views can mark English
     * fallback text with lang="en" (Architecture section 10).
     *
     * @return array{0: ?string, 1: string}
     */
    public static function pick(object $model, string $field): array
    {
        if (self::isTamil() && filled($model->{$field.'_ta'} ?? null)) {
            return [$model->{$field.'_ta'}, 'ta'];
        }

        return [$model->{$field.'_en'} ?? null, 'en'];
    }
}
