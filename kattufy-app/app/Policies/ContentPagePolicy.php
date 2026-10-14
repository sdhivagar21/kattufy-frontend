<?php

namespace App\Policies;

class ContentPagePolicy extends StaffPolicy
{
    protected function permission(): string
    {
        return 'content.edit';
    }
}
