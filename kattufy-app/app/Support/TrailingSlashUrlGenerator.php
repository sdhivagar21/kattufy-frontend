<?php

namespace App\Support;

use App\Http\Middleware\TrailingSlash;
use Illuminate\Routing\UrlGenerator;

/**
 * Public page URLs end in a slash (/login/, /ta/architects/chennai/), the
 * canonical form in Architecture §10. Laravel's format() trims slashes, so
 * the slash is added after it. Admin, Livewire, signed and POST routes are
 * left as Laravel makes them.
 */
class TrailingSlashUrlGenerator extends UrlGenerator
{
    public function format($root, $path, $route = null)
    {
        $url = parent::format($root, $path, $route);

        return TrailingSlash::applies($route) && ! str_ends_with($url, '/') ? $url.'/' : $url;
    }
}
