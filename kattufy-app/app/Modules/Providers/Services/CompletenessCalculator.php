<?php

namespace App\Modules\Providers\Services;

use App\Modules\Providers\Models\Provider;

/** 0–100 profile completeness, the first ranking key (Architecture §9). */
class CompletenessCalculator
{
    private const WEIGHTS = [
        'description' => 20,
        'tamil' => 10,
        'type' => 10,
        'zones' => 10,
        'portfolio' => 20,
        'hours' => 10,
        'established' => 5,
        'contact' => 5,
        'address' => 10,
    ];

    public function score(Provider $provider): int
    {
        $provider->loadMissing(['zones', 'portfolioItems']);

        $checks = [
            'description' => filled($provider->description_en),
            'tamil' => filled($provider->description_ta),
            'type' => $provider->provider_type_id !== null,
            'zones' => $provider->zones->isNotEmpty(),
            'portfolio' => $provider->portfolioItems->where('is_published', true)->isNotEmpty(),
            'hours' => ! empty($provider->working_hours),
            'established' => $provider->established_year !== null,
            'contact' => $provider->allow_call || $provider->allow_whatsapp,
            'address' => filled($provider->address) && $provider->locality_id !== null,
        ];

        return array_sum(array_map(fn ($key) => $checks[$key] ? self::WEIGHTS[$key] : 0, array_keys(self::WEIGHTS)));
    }
}
