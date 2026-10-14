<?php

namespace Database\Seeders;

use App\Modules\Directory\Models\Category;
use App\Modules\Directory\Models\ProviderSubtype;
use App\Modules\Directory\Models\Service;
use App\Modules\Directory\Services\CategoryGateService;
use App\Modules\Geography\Models\City;
use App\Modules\Geography\Models\Locality;
use App\Modules\Geography\Models\Zone;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Media;
use App\Modules\Providers\Models\PortfolioItem;
use App\Modules\Providers\Models\Provider;
use App\Modules\Providers\Services\CompletenessCalculator;
use App\Modules\Verification\Models\Consent;
use App\Modules\Verification\Models\Credential;
use App\Modules\Verification\Models\CredentialType;
use App\Modules\Verification\Services\CredentialReviewService;
use App\Modules\Verification\Services\LifecycleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The fictional businesses from the V1 preview (database/seeders/data/
 * providers.json). Each one goes through the real lifecycle — consent, phone
 * check, review, credential checks, PublishGate — so the seed data obeys the
 * same rules as real providers. Re-running skips businesses that exist.
 */
class SampleDirectorySeeder extends Seeder
{
    private const AUTHORITY_TYPES = [
        'Council of Architecture' => 'coa-registration',
        'Greater Chennai Corporation' => 'gcc-contractor-registration',
    ];

    public function run(LifecycleService $lifecycle, CredentialReviewService $reviews, CompletenessCalculator $completeness, CategoryGateService $gate): void
    {
        $rows = json_decode(file_get_contents(database_path('seeders/data/providers.json')), true);
        $city = City::launch();
        $zones = Zone::where('city_id', $city->id)->get()->keyBy('name_en');
        $localities = Locality::where('city_id', $city->id)->with('pincodes')->get()->keyBy('name_en');
        $subtypes = ProviderSubtype::all()->keyBy('name_en');
        $types = CredentialType::all()->keyBy('slug');

        foreach ($rows as $i => $row) {
            if (Provider::withTrashed()->where('slug', $row['slug'])->exists()) {
                continue;
            }

            $categories = Category::whereIn('slug', $row['categories'])->get()->sortBy(fn ($c) => array_search($c->slug, $row['categories']))->values();
            $locality = $localities[$row['locality']] ?? null;
            $subtype = $subtypes[$row['subtype']] ?? null;
            $phone = '+9190000'.sprintf('1%04d', $i + 1);
            $listedSince = $row['listed_since'] ? Carbon::parse('1 '.$row['listed_since']) : now()->subMonths(3);

            $provider = new Provider([
                'business_name_en' => $row['name_en'],
                'business_name_ta' => $row['name_ta'] !== $row['name_en'] ? $row['name_ta'] : null,
                'slug' => $row['slug'],
                'description_en' => $row['description_en'],
                'description_ta' => $row['description_ta'],
                'description_ta_status' => $row['description_ta'] ? 'reviewed' : 'empty',
                'city_id' => $city->id,
                'locality_id' => $locality?->id,
                'provider_type_id' => $subtype?->provider_type_id,
                'provider_subtype_id' => $subtype?->id,
                'phone' => $phone,
                'whatsapp_number' => $phone,
                'email' => $row['slug'].'@sample.kattufy.demo',
                'allow_call' => true,
                'allow_whatsapp' => true,
                'allow_enquiry' => true,
                'address' => 'Sample address, '.$row['locality'].', Chennai',
                'pincode' => $locality?->pincodes->first()?->code,
                'established_year' => $row['since'],
                'working_hours' => collect($row['hours'])->mapWithKeys(fn ($h) => [strtolower($h[0]) => $h[1]])->all() ?: null,
                'portfolio_status' => $row['portfolio'] ? 'shown' : 'none',
                'source' => 'sample_seed',
            ]);
            $provider->forceFill(['consent_at' => $listedSince->copy()->subDays(10), 'phone_verified_at' => $listedSince->copy()->subDays(9)]);
            $provider->save();

            $provider->categories()->sync($categories->mapWithKeys(fn ($c, $k) => [$c->id => ['is_primary' => $k === 0]])->all());
            $provider->zones()->sync(collect($row['zones'])->map(fn ($z) => $zones[$z[0]]->id ?? null)->filter()->all());

            foreach ($row['specialisations'] as $name) {
                $service = Service::firstOrCreate(
                    ['category_id' => $categories->first()->id, 'slug' => Str::slug($name)],
                    ['name_en' => $name],
                );
                $provider->services()->syncWithoutDetaching([$service->id]);
            }

            Consent::create([
                'provider_id' => $provider->id,
                'consented_at' => $provider->consent_at,
                'method' => 'phone_call',
                'scope' => ['business_name', 'description', 'locality', 'portfolio', 'contact_via_kattufy'],
                'policy_version' => 'v1',
                'given_by_name' => 'Sample data',
            ]);

            $lifecycle->transition($provider, ProviderStatus::Consented, null, ['source' => 'sample_seed']);
            $lifecycle->transition($provider, ProviderStatus::UnderReview, null, ['source' => 'sample_seed']);

            // Address proof (sample: no file kept) and the registrations each category requires.
            $this->verified($reviews, $provider, $types['address-proof']);

            $needed = $provider->fresh()->requiredCredentialTypeIds();
            foreach ($row['credentials'] as $credential) {
                $slug = self::AUTHORITY_TYPES[$credential['authority']] ?? null;
                if ($slug) {
                    $needed[] = $types[$slug]->id;
                }
            }
            foreach (array_unique($needed) as $typeId) {
                $this->verified($reviews, $provider, $types->firstWhere('id', $typeId), 'SAMPLE/'.strtoupper(Str::random(6)));
            }

            foreach ($row['portfolio'] as $n => $item) {
                [$place, $year] = array_pad(array_map('trim', explode(',', $item['meta'])), 2, null);
                $portfolio = PortfolioItem::create([
                    'provider_id' => $provider->id,
                    'title_en' => $item['title'],
                    'title_ta' => $item['title_ta'] ?? null,
                    'locality_id' => $localities[$place]->id ?? null,
                    'completed_on' => $year ? $year.'-06-01' : null,
                    'is_published' => true,
                    'sort_order' => $n,
                ]);
                $portfolio->morphMany(Media::class, 'mediable')->create([
                    'collection' => Media::COLLECTION_PORTFOLIO,
                    'visibility' => 'public',
                    'original_disk' => 'public_path',
                    'original_path' => 'media/'.$item['image'].'-800.webp',
                    'variants' => ['400' => 'media/'.$item['image'].'-400.webp', '800' => 'media/'.$item['image'].'-800.webp'],
                    'mime_type' => 'image/webp',
                    'size_bytes' => (int) @filesize(public_path('media/'.$item['image'].'-800.webp')),
                    'width' => 1600,
                    'height' => 1000,
                    'alt_en' => $item['title'].' (sample illustration)',
                    'alt_ta' => isset($item['title_ta']) ? $item['title_ta'].' (மாதிரிப் படம்)' : null,
                    'processing_status' => 'done',
                    'processed_at' => now(),
                ]);
            }

            $provider = $provider->fresh();
            $provider->forceFill(['completeness_score' => $completeness->score($provider)])->save();

            $lifecycle->transition($provider, ProviderStatus::Published, null, ['source' => 'sample_seed']);
            $provider->forceFill(['published_at' => $listedSince])->save();
        }

        foreach (Category::all() as $category) {
            $gate->refresh($category, $city);
        }
    }

    private function verified(CredentialReviewService $reviews, Provider $provider, CredentialType $type, ?string $number = null): void
    {
        $credential = new Credential([
            'provider_id' => $provider->id,
            'credential_type_id' => $type->id,
            'issuing_authority_id' => $type->issuing_authority_id,
            'credential_number' => $number,
            'holder_name' => $provider->business_name_en,
            'expiry_date' => $type->has_expiry ? now()->addYears(2)->startOfMonth() : null,
        ]);
        $credential->save();

        $reviews->verify($credential, $number ? 'register_lookup' : 'document_review', 'Sample data');
    }
}
