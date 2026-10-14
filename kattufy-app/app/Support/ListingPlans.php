<?php

namespace App\Support;

/**
 * The three company listing plans and exactly which public fields each one
 * shows. Every view (plan cards, the plan forms and live preview, public
 * profile and directory cards) reads this one table, so a Free listing can
 * never show a Pro or Premium field through a shared template.
 *
 * Front end only for now: there is no stored plan yet. A provider's choice and
 * plan details are kept in their browser, and public pages keep their current
 * layout until a plan is stored on the provider (see README, "Listing plans").
 */
class ListingPlans
{
    public const PLANS = ['free', 'pro', 'premium'];

    /** "projects" covers previous projects with their photographs and videos. */
    public const FIELDS = [
        'free' => ['name', 'description'],
        'pro' => ['name', 'description', 'address', 'website', 'phone', 'whatsapp'],
        'premium' => ['name', 'description', 'email', 'address', 'website', 'phone', 'whatsapp', 'photos', 'services', 'projects'],
    ];

    /** Every field any plan can show, in display order. */
    public const ALL_FIELDS = ['name', 'description', 'email', 'address', 'website', 'phone', 'whatsapp', 'photos', 'services', 'projects'];

    /** Longest company description each plan shows (Premium = the profile's own 2,000 limit). */
    public const DESCRIPTION_LIMITS = ['free' => 300, 'pro' => 1000, 'premium' => 2000];

    public const FREE_DESCRIPTION_LIMIT = self::DESCRIPTION_LIMITS['free'];

    public static function shows(?string $plan, string $field): bool
    {
        // No stored plan: the page keeps its existing (pre-plan) layout.
        if ($plan === null) {
            return true;
        }

        return in_array($field, self::FIELDS[$plan] ?? [], true);
    }

    /** The provider's stored plan, or null while plans are not stored yet. */
    public static function of(object $provider): ?string
    {
        $plan = $provider->listing_plan ?? null;

        return in_array($plan, self::PLANS, true) ? $plan : null;
    }

    /** Browser storage key for a provider's selected plan (front-end only). */
    public static function storageKey(object $provider): string
    {
        return 'kattufy.listingPlan.'.$provider->id;
    }

    /** Browser storage key for the plan details saved from the plan form (front-end only). */
    public static function draftKey(object $provider): string
    {
        return 'kattufy.listingDraft.'.$provider->id;
    }
}
