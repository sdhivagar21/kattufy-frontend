<?php

namespace App\Modules\Directory\Services;

use App\Modules\Analytics\Models\SearchLog;
use App\Modules\Directory\Models\Category;
use App\Modules\Geography\Models\City;
use App\Modules\Geography\Models\Locality;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Enums\CredentialStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Directory search in MySQL (Architecture §9): published providers in Live
 * category-city pairs, matched to a locality through its zone, ranked by
 * completeness, checked credentials, portfolio and freshness. No paid ranking.
 */
class SearchService
{
    public function search(City $city, ?Category $category = null, ?Locality $locality = null, ?string $text = null, bool $log = true): LengthAwarePaginator
    {
        $text = trim((string) $text);

        $query = Provider::query()
            ->published()
            ->where('providers.city_id', $city->id)
            ->with(['categories', 'locality', 'subtype', 'credentials.type', 'portfolioItems.image']);

        if ($category) {
            $query->whereHas('categories', fn (Builder $q) => $q->whereKey($category->id));
        }

        // Only Live category-city pairs are listed.
        $query->whereHas('categories.cities', fn (Builder $q) => $q->where('cities.id', $city->id)->where('category_city_status.status', 'live'));

        if ($locality) {
            $query->whereHas('zones', fn (Builder $q) => $q->whereKey($locality->zone_id));
        }

        if ($text !== '') {
            $this->applyText($query, $text);
        }

        $results = $this->rank($query)->paginate(config('kattufy.per_page'))->withQueryString();

        if ($log) {
            SearchLog::create([
                'query' => $text !== '' ? mb_substr($text, 0, 255) : null,
                'category_id' => $category?->id,
                'city_id' => $city->id,
                'locality_id' => $locality?->id,
                'result_count' => $results->total(),
                'locale' => app()->getLocale(),
            ]);
        }

        return $results;
    }

    private function applyText(Builder $query, string $text): void
    {
        $like = '%'.addcslashes($text, '%_\\').'%';

        $query->where(function (Builder $q) use ($text, $like) {
            // FULLTEXT for real words, LIKE for one- and two-character input.
            if (mb_strlen($text) >= 3) {
                $q->whereFullText(['business_name_en', 'business_name_ta', 'description_en', 'description_ta'], $text);
            }

            $q->orWhere('business_name_en', 'like', $like)
                ->orWhere('business_name_ta', 'like', $like)
                ->orWhereHas('categories', fn (Builder $c) => $c->where('name_en', 'like', $like)->orWhere('name_ta', 'like', $like))
                ->orWhereHas('services', fn (Builder $s) => $s->where('name_en', 'like', $like)->orWhere('name_ta', 'like', $like));
        });
    }

    /** The public ranking rule, written in plain words on /how-listings-are-ordered/. */
    public function rank(Builder $query): Builder
    {
        return $query
            ->withCount([
                'credentials as checked_credentials_count' => fn (Builder $q) => $q->where('status', CredentialStatus::Verified->value)
                    ->where(fn (Builder $e) => $e->whereNull('expiry_date')->orWhere('expiry_date', '>', now())),
                'portfolioItems as published_portfolio_count' => fn (Builder $q) => $q->where('is_published', true),
            ])
            ->orderByDesc('completeness_score')
            ->orderByDesc('checked_credentials_count')
            ->orderByDesc('published_portfolio_count')
            ->orderByRaw('GREATEST(COALESCE(providers.last_verified_at, providers.updated_at), providers.updated_at) DESC')
            ->orderBy('providers.id');
    }
}
