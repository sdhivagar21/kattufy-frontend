<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Accounts\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "How would you like to continue?" Only User or Provider can be chosen, once.
 * Staff roles are never selectable, whatever the form posts.
 */
class RoleSelectionController extends Controller
{
    public function show(): View
    {
        return view('auth.select-role');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(Role::selectable())]]);

        $user = $request->user();
        $user->assignRole($data['role']);

        activity('accounts')->performedOn($user)->causedBy($user)->event('role_chosen')
            ->withProperties(['role' => $data['role']])->log('Chose role '.$data['role']);

        return $data['role'] === Role::Provider->value
            ? redirect()->to(lroute('provider.register'))->with('success', __('auth.role_provider_chosen'))
            : redirect()->to(lroute('user.dashboard'))->with('success', __('auth.role_user_chosen'));
    }
}
