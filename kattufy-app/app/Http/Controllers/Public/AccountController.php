<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Accounts\Services\AccountHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** /account/ always leads to the right area for this account. */
class AccountController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->to(AccountHome::url($request->user()));
    }
}
