<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Accounts\Services\AccountHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The front door. Visitors see the brand splash, which moves on to the login
 * page by itself; signed-in accounts go straight to their own area.
 */
class SplashController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->to(AccountHome::url($request->user()));
        }

        return view('splash', [
            'next' => lroute('login'),
            'image' => brand_image('splash_image'),
            'portrait' => brand_image('splash_image_portrait'),
        ]);
    }
}
