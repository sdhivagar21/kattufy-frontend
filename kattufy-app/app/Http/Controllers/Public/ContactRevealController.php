<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Accounts\Enums\Role;
use App\Modules\Enquiries\Exceptions\RevealLimitReached;
use App\Modules\Enquiries\Services\ContactRevealService;
use App\Modules\Providers\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** POST /contact/{provider}/reveal — returns the number as JSON, never cached. */
class ContactRevealController extends Controller
{
    public function __invoke(Request $request, Provider $provider, ContactRevealService $reveals): JsonResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['call', 'whatsapp'])], 'locale' => ['nullable', Rule::in(['en', 'ta'])]]);
        app()->setLocale($data['locale'] ?? 'en');

        if (! $request->user()->hasRole(Role::Customer->value)) {
            return $this->json(['message' => __('directory.reveal_customers_only')], 403);
        }

        try {
            return $this->json($reveals->reveal($request->user(), $provider, $data['action']));
        } catch (RevealLimitReached) {
            return $this->json(['message' => __('directory.reveal_limit', ['count' => config('kattufy.reveal_limit_per_day')])], 429);
        }
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)->header('Cache-Control', 'no-store, private');
    }
}
