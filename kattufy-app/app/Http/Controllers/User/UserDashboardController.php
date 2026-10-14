<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Modules\Directory\Models\CategoryGroup;
use App\Modules\Directory\Services\SearchService;
use App\Modules\Geography\Models\City;
use App\Support\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The customer area. */
class UserDashboardController extends Controller
{
    public function dashboard(Request $request, SearchService $search): View
    {
        $city = City::launch();

        return view('user.dashboard', [
            'user' => $request->user(),
            'city' => $city,
            'groups' => CategoryGroup::with(['categories.cities' => fn ($q) => $q->where('cities.id', $city?->id)])
                ->where('is_active', true)->orderBy('sort_order')->get(),
            'featured' => $city ? $search->search($city, log: false)->take(6) : collect(),
            'localities' => $city?->localities()->get() ?? collect(),
            'faqs' => array_slice(Faq::items(), 0, 5),
            // The signed-in user's own enquiries (read only).
            'enquiries' => \App\Modules\Enquiries\Models\Enquiry::where('user_id', $request->user()->id)
                ->with(['category', 'locality'])->latest()->limit(5)->get(),
        ]);
    }

    public function faqs(Request $request): View
    {
        return view('user.faqs', ['user' => $request->user(), 'faqs' => Faq::items()]);
    }

    public function profile(Request $request): View
    {
        return view('user.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'profile_address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill($validated)->save();

        return redirect()->to(lroute('user.profile'))->with('success', __('user.profile_saved'));
    }
}
