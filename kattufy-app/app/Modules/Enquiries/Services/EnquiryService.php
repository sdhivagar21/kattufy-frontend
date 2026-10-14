<?php

namespace App\Modules\Enquiries\Services;

use App\Models\User;
use App\Modules\Accounts\Enums\Role;
use App\Modules\Enquiries\Models\Enquiry;
use App\Modules\Geography\Models\City;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Stores a fallback enquiry and tells staff about it. */
class EnquiryService
{
    public function create(User $user, array $data): Enquiry
    {
        $enquiry = Enquiry::create([
            'reference_no' => $this->reference(),
            'user_id' => $user->id,
            'name' => $data['name'],
            'phone' => $user->phone, // the account's OTP-verified number
            'category_id' => $data['category_id'] ?? null,
            'city_id' => City::launch()?->id,
            'locality_id' => $data['locality_id'] ?? null,
            'message' => $data['message'],
            'budget' => $data['budget'] ?? null,
            'preferred_date' => $data['preferred_date'] ?? null,
            'locale' => app()->getLocale(),
            'source' => $data['source'] ?? 'no_results',
            'status' => 'new',
        ]);

        $this->notifyStaff($enquiry);

        return $enquiry;
    }

    private function reference(): string
    {
        do {
            $ref = 'KTF-E-'.Str::upper(Str::random(6));
        } while (Enquiry::where('reference_no', $ref)->exists());

        return $ref;
    }

    private function notifyStaff(Enquiry $enquiry): void
    {
        $emails = User::role([Role::SuperAdmin->value, Role::OnboardingStaff->value])->whereNotNull('email')->pluck('email')->all();

        if ($emails === []) {
            return;
        }

        try {
            Mail::raw("New enquiry {$enquiry->reference_no}. Open the Kattufy admin to assign it.", function ($m) use ($emails, $enquiry) {
                $m->to($emails)->subject("New Kattufy enquiry {$enquiry->reference_no}");
            });
        } catch (\Throwable $e) {
            // The enquiry is saved and listed in the admin either way.
            Log::warning('Enquiry alert email failed: '.$e->getMessage());
        }
    }
}
