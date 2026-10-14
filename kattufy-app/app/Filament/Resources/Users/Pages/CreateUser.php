<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Modules\Accounts\Enums\Role;
use Filament\Resources\Pages\CreateRecord;

/** Adds a staff member. They sign in at /admin/login/ with this mobile number and email. */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $roles = array_intersect($this->data['staff_roles'] ?? [], Role::staff());
        $this->record->syncRoles($roles);
    }
}
