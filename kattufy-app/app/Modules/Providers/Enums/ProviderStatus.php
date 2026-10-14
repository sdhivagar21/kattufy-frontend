<?php

namespace App\Modules\Providers\Enums;

/**
 * Provider lifecycle (Architecture section 5). Change it only through
 * LifecycleService::transition().
 */
enum ProviderStatus: string
{
    case Found = 'found';
    case Consented = 'consented';
    case UnderReview = 'under_review';
    case Published = 'published';
    case NeedsUpdate = 'needs_update';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Found => 'Found',
            self::Consented => 'Consented',
            self::UnderReview => 'Under review',
            self::Published => 'Published',
            self::NeedsUpdate => 'Needs update',
            self::Suspended => 'Suspended',
        };
    }

    /** Filament badge colour. */
    public function color(): string
    {
        return match ($this) {
            self::Found => 'gray',
            self::Consented => 'info',
            self::UnderReview => 'warning',
            self::Published => 'success',
            self::NeedsUpdate => 'warning',
            self::Suspended => 'danger',
        };
    }

    /** Allowed transitions: [to => [from, ...]] (section 5 table). */
    public static function transitions(): array
    {
        return [
            self::Consented->value => [self::Found, self::UnderReview],
            self::UnderReview->value => [self::Consented, self::NeedsUpdate, self::Suspended],
            self::Published->value => [self::UnderReview],
            self::NeedsUpdate->value => [self::Published],
            self::Suspended->value => [self::Published, self::NeedsUpdate],
        ];
    }

    public function canMoveTo(self $to): bool
    {
        return in_array($this, self::transitions()[$to->value] ?? [], true);
    }
}
