<?php

namespace App\Modules\Accounts\Enums;

/** The six roles from Architecture section 8. */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case VerificationStaff = 'verification_staff';
    case OnboardingStaff = 'onboarding_staff';
    case ContentStaff = 'content_staff';
    case Provider = 'provider';
    case Customer = 'customer';

    /** @return list<string> */
    public static function staff(): array
    {
        return [self::SuperAdmin->value, self::VerificationStaff->value, self::OnboardingStaff->value, self::ContentStaff->value];
    }

    /** Roles an account may pick for itself on the role-selection page. */
    public static function selectable(): array
    {
        return [self::Customer->value, self::Provider->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::VerificationStaff => 'Verification staff',
            self::OnboardingStaff => 'Onboarding staff',
            self::ContentStaff => 'Content staff',
            self::Provider => 'Provider',
            self::Customer => 'User',
        };
    }
}
