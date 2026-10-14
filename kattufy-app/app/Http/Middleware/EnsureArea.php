<?php

namespace App\Http\Middleware;

use App\Modules\Accounts\Services\AccountHome;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the User and Provider areas apart. An account without the role is
 * sent to its own area; changing a URL never grants a role.
 */
class EnsureArea
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole($role)) {
            return $next($request);
        }

        abort_if(! $user, 403);

        return redirect()->to(AccountHome::url($user))->with('error', __('auth.wrong_area'));
    }
}
