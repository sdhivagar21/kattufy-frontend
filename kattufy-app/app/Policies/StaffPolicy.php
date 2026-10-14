<?php

namespace App\Policies;

use App\Models\User;

/**
 * Shared base for admin-panel resources: one permission covers the resource,
 * and only inside a session that passed the two admin codes. Super Admin
 * passes through Gate::before.
 */
abstract class StaffPolicy
{
    /** Permission name from the Architecture §8 matrix. */
    abstract protected function permission(): string;

    protected function allowed(User $user): bool
    {
        return $user->isStaff() && session('admin_verified_user_id') === $user->id && $user->can($this->permission());
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user, $model): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, $model): bool
    {
        return $this->allowed($user);
    }

    public function delete(User $user, $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
