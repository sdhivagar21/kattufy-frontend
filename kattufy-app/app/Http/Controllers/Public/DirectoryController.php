<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Directory\Models\Category;
use App\Modules\Directory\Models\CategoryGroup;
use App\Modules\Directory\Services\SearchService;
use App\Modules\Geography\Models\City;
use App\Modules\Geography\Models\Locality;
use App\Modules\Providers\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * /{group}/, /{category}/, /{category}/{city}/, /{category}/{provider}/ and
 * /{category}/{city}/{locality}/. City slugs are checked before provider
 * slugs, and provider slugs may never equal a city slug (Architecture §10).
 */
class DirectoryController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function section(string $category): View
    {
        $city = City::launch();

        if ($group = CategoryGroup::where('slug', $category)->where('is_active', true)->first()) {
            $group->load(['categories.cities' => fn ($q) => $q->where('cities.id', $city?->id)]);

            return view('directory.group', compact('group', 'city'));
        }

        $category = $this->category($category);
        $category->load(['group', 'services', 'cities' => fn ($q) => $q->where('cities.id', $city?->id)]);

        return view('directory.category', [
            'category' => $category,
            'city' => $city,
            'live' => $category->isLiveIn($city),
            'preview' => $city && $category->isLiveIn($city) ? $this->search->search($city, $category, log: false)->take(3) : collect(),
        ]);
    }

    public function child(Request $request, string $category, string $slug): View|RedirectResponse
    {
        $category = $this->category($category);

        if ($city = City::where('slug', $slug)->where('is_active', true)->first()) {
            return $this->listing($request, $category, $city);
        }

        $provider = Provider::published()
            ->where('slug', $slug)
            ->whereHas('categories', fn ($q) => $q->whereKey($category->id))
            ->with(['categories', 'locality', 'subtype', 'zones.localities', 'services', 'credentials.type', 'credentials.authority', 'portfolioItems.image', 'portfolioItems.locality'])
            ->firstOrFail();

        $more = $this->search->search($provider->city ?? City::launch(), $category, log: false)
            ->getCollection()->where('id', '!=', $provider->id)->take(3);

        return view('directory.profile', compact('provider', 'category', 'more'));
    }

    public function locality(Request $request, string $category, string $city, string $locality): View|RedirectResponse
    {
        $category = $this->category($category);
        $city = City::where('slug', $city)->firstOrFail();
        $locality = Locality::where('city_id', $city->id)->where('slug', $locality)->firstOrFail();

        return $this->listing($request, $category, $city, $locality);
    }

    private function listing(Request $request, Category $category, City $city, ?Locality $locality = null): View|RedirectResponse
    {
        $category->load(['group', 'cities' => fn ($q) => $q->where('cities.id', $city->id)]);

        if (! $category->isLiveIn($city)) {
            return view('directory.coming-soon', compact('category', 'city'));
        }

        $results = $this->search->search($city, $category, $locality);

        // Locality pages exist only with enough providers; otherwise use the city page.
        if ($locality && $results->total() < config('kattufy.locality_page_threshold')) {
            return redirect()->to(lroute('directory.child', [$category->slug, $city->slug]))
                ->with('status', __('directory.locality_too_few', ['area' => tr_field($locality, 'name')]));
        }

        $localityCounts = DB::table('localities')
            ->join('provider_zones', 'provider_zones.zone_id', '=', 'localities.zone_id')
            ->join('providers', 'providers.id', '=', 'provider_zones.provider_id')
            ->join('provider_categories', 'provider_categories.provider_id', '=', 'providers.id')
            ->where('localities.city_id', $city->id)
            ->where('provider_categories.category_id', $category->id)
            ->where('providers.status', 'published')
            ->whereNull('providers.deleted_at')
            ->groupBy('localities.id')
            ->selectRaw('localities.id AS locality_id, COUNT(DISTINCT providers.id) AS providers_count')
            ->get()
            ->pluck('providers_count', 'locality_id');

        return view('directory.listing', [
            'category' => $category,
            'city' => $city,
            'locality' => $locality,
            'results' => $results,
            'localities' => $city->localities()->get()->filter(fn ($l) => ($localityCounts[$l->id] ?? 0) >= config('kattufy.locality_page_threshold')),
        ]);
    }

    private function category(string $slug): Category
    {
        return Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }
}
