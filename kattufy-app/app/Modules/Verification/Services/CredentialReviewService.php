<?php

namespace App\Modules\Verification\Services;

use App\Modules\Verification\Enums\CredentialStatus;
use App\Modules\Verification\Models\Credential;
use App\Modules\Verification\Models\VerificationEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Staff record each credential check as an event; there is no single "verified" flag. */
class CredentialReviewService
{
    public function verify(Credential $credential, string $method, ?string $notes = null): void
    {
        $this->record($credential, CredentialStatus::Verified, $method, $notes, [
            'verified_at' => now(),
            'verified_by' => Auth::id(),
            'rejection_reason' => null,
        ]);
    }

    public function reject(Credential $credential, string $reason): void
    {
        $this->record($credential, CredentialStatus::Rejected, 'document_review', $reason, [
            'verified_at' => null,
            'verified_by' => Auth::id(),
            'rejection_reason' => $reason,
        ]);
    }

    private function record(Credential $credential, CredentialStatus $status, string $method, ?string $reason, array $changes): void
    {
        DB::transaction(function () use ($credential, $status, $method, $reason, $changes) {
            $before = $credential->status?->value;

            $credential->forceFill($changes + ['status' => $status])->save();

            VerificationEvent::create([
                'provider_id' => $credential->provider_id,
                'credential_id' => $credential->id,
                'event_type' => 'credential_check',
                'check_method' => $method,
                'result' => $status->value,
                'reason' => $reason,
            ]);

            activity('verification')
                ->performedOn($credential)
                ->causedBy(Auth::user())
                ->event('credential_'.$status->value)
                ->withProperties(['old' => ['status' => $before], 'attributes' => ['status' => $status->value], 'reason' => $reason])
                ->log(($credential->type?->name_en ?? 'Credential').' marked '.$status->value);
        });
    }
}
