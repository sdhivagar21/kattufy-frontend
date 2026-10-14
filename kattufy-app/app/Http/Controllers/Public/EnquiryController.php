<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnquiryRequest;
use App\Modules\Directory\Models\Category;
use App\Modules\Enquiries\Models\Enquiry;
use App\Modules\Enquiries\Services\EnquiryService;
use App\Modules\Geography\Models\City;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** "Can't find what you need?" (Architecture §6D). The phone is the account's verified number. */
class EnquiryController extends Controller
{
    public function create(Request $request): View
    {
        return view('directory.enquiry', [
            'user' => $request->user(),
            'maskedPhone' => Phone::mask($request->user()->phone),
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'localities' => City::launch()?->localities()->get() ?? collect(),
            'selectedCategory' => Category::where('slug', $request->query('category'))->value('id'),
            'source' => in_array($request->query('source'), ['no_results', 'coming_soon'], true) ? $request->query('source') : 'direct',
        ]);
    }

    public function store(EnquiryRequest $request, EnquiryService $enquiries): RedirectResponse
    {
        $enquiry = $enquiries->create($request->user(), $request->validated());

        return redirect()->to(lroute('enquiry.sent', $enquiry->reference_no));
    }

    public function sent(Request $request, string $reference): View
    {
        $enquiry = Enquiry::where('reference_no', $reference)->where('user_id', $request->user()->id)->firstOrFail();

        return view('directory.enquiry-sent', compact('enquiry'));
    }
}
