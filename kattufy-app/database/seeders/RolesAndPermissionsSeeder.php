<?php

namespace Database\Seeders;

use App\Modules\Accounts\Enums\Role as R;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** The six roles and the Architecture §8 permission matrix. */
class RolesAndPermissionsSeeder extends Seeder
{
    public const MATRIX = [
        'candidates.manage' => [R::SuperAdmin, R::VerificationStaff, R::OnboardingStaff],
        'providers.edit' => [R::SuperAdmin, R::VerificationStaff, R::OnboardingStaff],
        'credentials.view_documents' => [R::SuperAdmin, R::VerificationStaff],
        'providers.verify' => [R::SuperAdmin, R::VerificationStaff],
        'directory.manage' => [R::SuperAdmin],
        'enquiries.handle' => [R::SuperAdmin, R::VerificationStaff, R::OnboardingStaff],
        'content.edit' => [R::SuperAdmin, R::ContentStaff],
        'reports.view' => [R::SuperAdmin, R::VerificationStaff, R::OnboardingStaff, R::ContentStaff],
        'staff.manage' => [R::SuperAdmin],
        'contacts.reveal' => [R::Customer],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (R::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        foreach (self::MATRIX as $permission => $roles) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (R::cases() as $role) {
            $permissions = array_keys(array_filter(self::MATRIX, fn ($roles) => in_array($role, $roles, true)));
            Role::findByName($role->value, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
