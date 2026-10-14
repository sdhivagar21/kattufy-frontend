<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Modules\Accounts\Enums\Role;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** Staff roles are replaced; the customer/provider role the account chose is kept. */
    protected function afterSave(): void
    {
        $chosen = $this->record->roles->pluck('name')->diff(Role::staff())->values()->all();
        $staff = array_intersect($this->data['staff_roles'] ?? [], Role::staff());

        $this->record->syncRoles(array_merge($chosen, $staff));

        activity('accounts')->performedOn($this->record)->causedBy(auth()->user())->event('roles_changed')
            ->withProperties(['roles' => array_merge($chosen, $staff)])->log('Account roles updated');
    }
}
