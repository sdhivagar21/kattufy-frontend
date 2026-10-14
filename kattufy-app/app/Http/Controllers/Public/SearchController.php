<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Directory\Models\Category;
use App\Modules\Directory\Services\SearchService;
use App\Modules\Geography\Models\City;
use App\Modules\Geography\Services\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search, LocationResolver $locations): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:160'],
        ]);

        $city = City::launch();
        abort_unless($city, 404);

        $category = filled($data['category'] ?? null) ? Category::where('slug', $data['category'])->where('is_active', true)->first() : null;
        $locality = $locations->resolve($data['area'] ?? null);
        $areaNotFound = filled($data['area'] ?? null) && ! $locality;

        $results = $search->search($city, $category, $locality, $data['q'] ?? null);

        return view('directory.search', [
            'results' => $results,
            'query' => $data['q'] ?? '',
            'area' => $data['area'] ?? '',
            'category' => $category,
            'locality' => $locality,
            'areaNotFound' => $areaNotFound,
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'localities' => $city->localities()->get(),
        ]);
    }
}
