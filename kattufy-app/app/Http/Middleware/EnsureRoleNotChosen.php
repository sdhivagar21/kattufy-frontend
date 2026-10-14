<?php

namespace App\Http\Middleware;

use App\Modules\Accounts\Services\AccountHome;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The role is chosen once. Accounts that already have one go to their area. */
class EnsureRoleNotChosen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->isStaff() || $user->chosenRole() !== null) {
            return redirect()->to(AccountHome::url($user));
        }

        return $next($request);
    }
}
