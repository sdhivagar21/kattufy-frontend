<?php

namespace App\Modules\Accounts\Services;

use App\Models\User;
use App\Modules\Accounts\Enums\Role;

/** Where an account belongs after signing in, decided by its role and provider status. */
class AccountHome
{
    public static function url(User $user): string
    {
        if ($user->isStaff()) {
            return session('admin_verified_user_id') === $user->getKey() ? url('/admin') : lroute('admin.login');
        }

        return match ($user->chosenRole()) {
            Role::Customer => lroute('user.dashboard'),
            Role::Provider => self::providerUrl($user),
            default => lroute('role.select'),
        };
    }

    public static function providerUrl(User $user): string
    {
        $provider = $user->provider;

        if (! $provider) {
            return lroute('provider.register');
        }

        $state = $provider->verificationState();

        if ($state->opensDashboard()) {
            return lroute('provider.dashboard');
        }

        return $state === \App\Modules\Verification\Enums\VerificationState::NotSubmitted
            ? lroute('provider.register')
            : lroute('provider.verification');
    }
}
