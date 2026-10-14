<?php

namespace App\Modules\Directory\Services;

use App\Modules\Directory\Models\Category;
use App\Modules\Geography\Models\City;
use App\Modules\Providers\Models\Provider;
use Illuminate\Support\Facades\DB;

/**
 * A category-city pair is Live once it has kattufy.live_threshold published
 * providers, unless staff hold it at Coming soon (Architecture §9).
 */
class CategoryGateService
{
    public function refreshFor(Provider $provider): void
    {
        $city = $provider->city ?? City::launch();

        if (! $city) {
            return;
        }

        foreach ($provider->categories()->get() as $category) {
            $this->refresh($category, $city);
        }
    }

    public function refresh(Category $category, City $city): void
    {
        $count = $category->providers()->published()->where('providers.city_id', $city->id)->count();

        $row = DB::table('category_city_status')->where('category_id', $category->id)->where('city_id', $city->id)->first();
        $held = (bool) ($row->is_held ?? false);
        $status = ! $held && $count >= config('kattufy.live_threshold') ? 'live' : 'coming_soon';

        DB::table('category_city_status')->updateOrInsert(
            ['category_id' => $category->id, 'city_id' => $city->id],
            array_filter([
                'published_provider_count' => $count,
                'status' => $status,
                'status_changed_at' => ($row->status ?? null) !== $status ? now() : ($row->status_changed_at ?? now()),
                'last_evaluated_at' => now(),
                'updated_at' => now(),
                'created_at' => $row ? null : now(),
            ], fn ($v) => $v !== null),
        );
    }
}
