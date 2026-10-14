<?php

namespace App\Modules\Verification\Enums;

/**
 * What a provider sees about their own submission. Computed from the lifecycle
 * status and the verification history, never stored, so the provider and admin
 * views can never disagree.
 *
 *   no record / Found / Consented without a returned review  → Not submitted
 *   Under review                                              → Pending review
 *   Consented after staff asked for more information          → Changes requested ("Needs update")
 *   Published                                                 → Approved
 *   Needs update                                              → Needs update
 *   Suspended                                                 → Suspended
 */
enum VerificationState: string
{
    case NotSubmitted = 'not_submitted';
    case PendingReview = 'pending_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case NeedsUpdate = 'needs_update';
    case Suspended = 'suspended';

    public function label(): string
    {
        return __('status.'.$this->value);
    }

    /** Badge tone used by <x-status-badge>. */
    public function tone(): string
    {
        return match ($this) {
            self::NotSubmitted => 'neutral',
            self::PendingReview => 'pending',
            self::ChangesRequested, self::NeedsUpdate => 'warning',
            self::Approved => 'success',
            self::Suspended => 'danger',
        };
    }

    /** Approved providers (and published ones flagged for an update) use the dashboard. */
    public function opensDashboard(): bool
    {
        return in_array($this, [self::Approved, self::NeedsUpdate], true);
    }

    /** States in which the provider may edit and (re)submit the registration form. */
    public function canEditSubmission(): bool
    {
        return in_array($this, [self::NotSubmitted, self::ChangesRequested, self::NeedsUpdate], true);
    }
}
