<?php

namespace App\Policies;

use App\Models\User;

class CategoryPolicy extends StaffPolicy
{
    protected function permission(): string
    {
        return 'directory.manage';
    }

    public function create(User $user): bool
    {
        return false; // Categories are seeded; a new one also needs copy and verification requirements.
    }
}
