<?php

namespace App\Policies;

use App\Models\User;

/** The audit log is read-only. */
class ActivityPolicy extends StaffPolicy
{
    protected function permission(): string
    {
        return 'staff.manage';
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, $model): bool
    {
        return false;
    }
}
