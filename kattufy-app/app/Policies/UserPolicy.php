<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends StaffPolicy
{
    protected function permission(): string
    {
        return 'staff.manage';
    }

    /** Nobody edits their own roles or status from the panel. */
    public function update(User $user, $model): bool
    {
        return $this->allowed($user) && $model->id !== $user->id;
    }
}
