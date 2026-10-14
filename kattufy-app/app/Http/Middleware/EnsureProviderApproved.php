<?php

namespace App\Http\Middleware;

use App\Modules\Accounts\Services\AccountHome;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The provider dashboard opens only after an admin has approved the business. */
class EnsureProviderApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $provider = $request->user()?->provider;

        if ($provider && $provider->verificationState()->opensDashboard()) {
            return $next($request);
        }

        return redirect()->to(AccountHome::providerUrl($request->user()));
    }
}
