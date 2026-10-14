<?php

namespace App\Modules\Verification\Services;

use App\Modules\Directory\Services\CategoryGateService;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Exceptions\TransitionNotAllowed;
use App\Modules\Verification\Models\VerificationEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The one door for provider status (Architecture §5). Every change checks the
 * transition table and its guard, writes the status, a verification_events
 * row and an audit log entry. Nothing else may set providers.status.
 */
class LifecycleService
{
    /** Transitions that must carry a reason, keyed "from>to". */
    private const NEEDS_REASON = [
        'under_review>consented',
        'published>needs_update',
        'published>suspended',
        'needs_update>suspended',
        'suspended>under_review',
    ];

    public function __construct(
        private readonly PublishGate $gate,
        private readonly CategoryGateService $categoryGate,
    ) {}

    /**
     * @throws TransitionNotAllowed
     */
    public function transition(Provider $provider, ProviderStatus $to, ?string $reason = null, array $metadata = []): Provider
    {
        $from = $provider->status;

        if (! $from->canMoveTo($to)) {
            throw new TransitionNotAllowed("A provider cannot move from {$from->label()} to {$to->label()}.");
        }

        $this->guard($provider, $from, $to, $reason);

        return DB::transaction(function () use ($provider, $from, $to, $reason, $metadata) {
            $changes = ['status' => $to, 'status_changed_at' => now()];

            if ($to === ProviderStatus::Published) {
                $changes += [
                    'published_at' => $provider->published_at ?? now(),
                    'last_verified_at' => now(),
                    'reviewed_by' => Auth::id(),
                    'unreviewed_edits_at' => null,
                ];
            }

            $provider->forceFill($changes)->save();

            VerificationEvent::create([
                'provider_id' => $provider->id,
                'event_type' => 'status_change',
                'from_status' => $from->value,
                'to_status' => $to->value,
                'reason' => $reason,
                'metadata' => $metadata ?: null,
            ]);

            activity('lifecycle')
                ->performedOn($provider)
                ->causedBy(Auth::user())
                ->event('status_changed')
                ->withProperties(['old' => ['status' => $from->value], 'attributes' => ['status' => $to->value], 'reason' => $reason])
                ->log("Status changed from {$from->label()} to {$to->label()}");

            if ($from === ProviderStatus::Published || $to === ProviderStatus::Published) {
                $this->categoryGate->refreshFor($provider);
            }

            return $provider;
        });
    }

    private function guard(Provider $provider, ProviderStatus $from, ProviderStatus $to, ?string $reason): void
    {
        if (in_array($from->value.'>'.$to->value, self::NEEDS_REASON, true) && blank($reason)) {
            throw new TransitionNotAllowed('A reason is required for this change.');
        }

        if ($to === ProviderStatus::Consented && $from === ProviderStatus::Found
            && $provider->consents()->whereNull('withdrawn_at')->doesntExist()) {
            throw new TransitionNotAllowed('Record the provider\'s consent first.');
        }

        if ($to === ProviderStatus::UnderReview && $from === ProviderStatus::Consented && ! $provider->phone_verified_at) {
            throw new TransitionNotAllowed('The business phone must be verified by OTP first.');
        }

        if ($to === ProviderStatus::Published) {
            $failures = $this->gate->failures($provider);

            if ($failures !== []) {
                throw new TransitionNotAllowed('The profile is not ready to publish.', $failures);
            }
        }
    }
}
