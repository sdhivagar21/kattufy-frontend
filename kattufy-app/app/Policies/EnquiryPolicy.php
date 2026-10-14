<?php

namespace App\Policies;

use App\Models\User;

class EnquiryPolicy extends StaffPolicy
{
    protected function permission(): string
    {
        return 'enquiries.handle';
    }

    public function create(User $user): bool
    {
        return false; // Enquiries come from customers.
    }
}
