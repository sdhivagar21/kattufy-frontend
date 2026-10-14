<?php

namespace App\Modules\Enquiries\Services;

use App\Models\User;
use App\Modules\Enquiries\Exceptions\RevealLimitReached;
use App\Modules\Enquiries\Models\ContactEvent;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use App\Support\Phone;

/**
 * The only way a provider's number leaves the server (Architecture §6C):
 * after login, for a published provider, within the daily reveal limit, and
 * logged in contact_events. Repeat taps on the same provider the same day
 * reuse the earlier reveal and count once.
 */
class ContactRevealService
{
    /**
     * @return array{display: string, tel: ?string, whatsapp: ?string}
     *
     * @throws RevealLimitReached
     */
    public function reveal(User $user, Provider $provider, string $action): array
    {
        abort_unless($provider->status === ProviderStatus::Published, 404);
        abort_unless($action === 'call' ? $provider->allow_call : $provider->allow_whatsapp, 404);

        $today = now()->startOfDay();

        $alreadyToday = ContactEvent::where('user_id', $user->id)
            ->where('provider_id', $provider->id)
            ->where('created_at', '>=', $today)
            ->exists();

        if (! $alreadyToday) {
            $distinct = ContactEvent::where('user_id', $user->id)->where('created_at', '>=', $today)->distinct()->count('provider_id');

            if ($distinct >= config('kattufy.reveal_limit_per_day')) {
                throw new RevealLimitReached;
            }

            ContactEvent::create([
                'provider_id' => $provider->id,
                'user_id' => $user->id,
                'action' => $action,
                'locale' => app()->getLocale(),
            ]);
        }

        $whatsapp = $provider->whatsapp_number ?: $provider->phone;

        return [
            'display' => Phone::display($action === 'whatsapp' ? $whatsapp : $provider->phone),
            'tel' => $provider->allow_call && $provider->phone ? 'tel:'.$provider->phone : null,
            'whatsapp' => $provider->allow_whatsapp && $whatsapp ? 'https://wa.me/'.ltrim($whatsapp, '+') : null,
        ];
    }
}
