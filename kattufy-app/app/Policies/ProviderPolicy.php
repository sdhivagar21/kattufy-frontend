<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Providers\Models\Provider;

/**
 * Architecture §8. Super Admin passes everything through Gate::before. Staff
 * permissions only count inside a verified admin session.
 */
class ProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->staff($user) && $user->hasAnyPermission(['providers.edit', 'providers.verify', 'candidates.manage']);
    }

    public function view(User $user, Provider $provider): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Provider $provider): bool
    {
        return $this->staff($user) && $user->can('providers.edit');
    }

    /** Record credential checks, publish, ask for updates, suspend. */
    public function verify(User $user, Provider $provider): bool
    {
        return $this->staff($user) && $user->can('providers.verify');
    }

    public function viewDocuments(User $user, Provider $provider): bool
    {
        if ($provider->user_id !== null && $provider->user_id === $user->id) {
            return true;
        }

        return $this->staff($user) && $user->can('credentials.view_documents');
    }

    public function create(User $user): bool
    {
        return $this->staff($user) && $user->can('candidates.manage');
    }

    public function delete(User $user, Provider $provider): bool
    {
        return false; // Only Super Admin (Gate::before); privacy deletions anonymise instead.
    }

    private function staff(User $user): bool
    {
        return $user->isStaff() && session('admin_verified_user_id') === $user->id;
    }
}
