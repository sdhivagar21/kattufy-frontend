<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Accounts\Enums\Role;
use App\Modules\Directory\Models\Category;
use App\Modules\Enquiries\Models\Enquiry;
use App\Modules\Geography\Models\City;
use App\Modules\Geography\Models\Locality;
use App\Modules\Geography\Models\Zone;
use App\Modules\Directory\Models\ProviderSubtype;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Media;
use App\Modules\Providers\Models\Provider;
use App\Modules\Providers\Services\CompletenessCalculator;
use App\Modules\Verification\Models\Consent;
use App\Modules\Verification\Models\Credential;
use App\Modules\Verification\Models\CredentialType;
use App\Modules\Verification\Services\CredentialReviewService;
use App\Modules\Verification\Services\LifecycleService;
use App\Support\DemoAccounts;
use App\Support\Phone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The four documented demo accounts (fictional people, numbers in the unused
 * 90000 0000x range) and their businesses:
 *   provider@kattufy.demo — Northline Build Studio, approved and published
 *   pending@kattufy.demo  — Sample Pending Constructions, waiting for review
 * Sample documents are one-page PDFs marked SAMPLE, stored on the private disk.
 */
class DemoAccountsSeeder extends Seeder
{
    public function run(LifecycleService $lifecycle, CredentialReviewService $reviews, CompletenessCalculator $completeness): void
    {
        $users = [];
        foreach (DemoAccounts::ACCOUNTS as $key => $account) {
            $user = User::firstOrNew(['phone' => Phone::normalize($account['phone'])]);
            $user->fill(['name' => $account['name'], 'email' => $account['email'], 'locale' => 'en', 'status' => 'active']);
            $user->forceFill(['phone_verified_at' => $user->phone_verified_at ?? now()->subDays(23)])->save();
            $user->syncRoles([$account['role']]);
            $users[$key] = $user;
        }

        $admin = $users['admin'];
        $city = City::launch();

        // ---- Approved provider ------------------------------------------------
        if (! Provider::where('user_id', $users['provider']->id)->exists()) {
            $provider = $this->business($users['provider'], [
                'business_name_en' => 'Northline Build Studio',
                'slug' => 'northline-build-studio',
                'address' => '21 Sample Street, Anna Nagar, Chennai',
                'pincode' => '600040',
                'locality' => 'anna-nagar',
                'subtype' => 'design-and-build-firm',
                'zones' => ['west-chennai', 'central-chennai'],
                'description_en' => 'Northline Build Studio designs and builds independent houses in west and central Chennai. This is a fictional demo business.',
                'established_year' => 2016,
            ], $city);

            $this->credential($provider, 'pan-card', 'ABCDE1234F');
            $proof = $this->credential($provider, 'address-proof', null, 'sample-electricity-bill.pdf');
            $gst = $this->credential($provider, 'gst-certificate', null, 'sample-gst-certificate.pdf');
            $gcc = $this->credential($provider, 'gcc-contractor-registration', 'GCC/CR/2024/0418', 'sample-contractor-registration.pdf');

            $lifecycle->transition($provider, ProviderStatus::UnderReview, null, ['source' => 'demo_seed']);

            Auth::setUser($admin);
            foreach ([$proof, $gst, $gcc] as $credential) {
                $reviews->verify($credential, 'document_review', 'Demo review');
            }
            $provider->forceFill(['completeness_score' => $completeness->score($provider->fresh())])->save();
            $lifecycle->transition($provider->fresh(), ProviderStatus::Published, 'Details match the sample documents. Welcome to Kattufy.');
            Auth::forgetUser();
        }

        // ---- Pending provider ---------------------------------------------------
        if (! Provider::where('user_id', $users['pending']->id)->exists()) {
            $provider = $this->business($users['pending'], [
                'business_name_en' => 'Sample Pending Constructions',
                'slug' => 'sample-pending-constructions',
                'address' => '8 Demo Lane, Velachery, Chennai',
                'pincode' => '600042',
                'locality' => 'velachery',
                'subtype' => 'building-contractor',
                'zones' => ['south-chennai'],
                // Filled in by onboarding staff on the call (Architecture §6E), so the
                // reviewer only has to check the documents before approving.
                'description_en' => 'Sample Pending Constructions builds independent houses in south Chennai. This is a fictional demo business.',
                'established_year' => 2019,
            ], $city);

            $this->credential($provider, 'voter-id', 'KTF0007781');
            $this->credential($provider, 'address-proof', null, 'sample-rental-agreement.pdf');
            $this->credential($provider, 'gcc-contractor-registration', 'GCC/CR/2025/1102', 'sample-contractor-registration.pdf');

            $provider->forceFill(['completeness_score' => $completeness->score($provider->fresh())])->save();
            $lifecycle->transition($provider, ProviderStatus::UnderReview, null, ['source' => 'demo_seed']);
        }

        // ---- A sample enquiry for the admin list ---------------------------------
        if (! Enquiry::where('user_id', $users['user']->id)->exists()) {
            Enquiry::create([
                'reference_no' => 'KTF-E-DEMO01',
                'user_id' => $users['user']->id,
                'name' => $users['user']->name,
                'phone' => $users['user']->phone,
                'category_id' => Category::where('slug', 'plumbers')->value('id'),
                'city_id' => $city->id,
                'locality_id' => Locality::where('slug', 'velachery')->value('id'),
                'message' => 'Looking for a plumber to lay water lines for a new two-floor house. (Demo enquiry.)',
                'locale' => 'en',
                'source' => 'coming_soon',
                'status' => 'new',
            ]);
        }
    }

    private function business(User $user, array $data, City $city): Provider
    {
        $locality = Locality::where('slug', $data['locality'])->first();
        $subtype = ProviderSubtype::where('slug', $data['subtype'])->first();

        $provider = new Provider([
            'user_id' => $user->id,
            'business_name_en' => $data['business_name_en'],
            'slug' => $data['slug'],
            'description_en' => $data['description_en'],
            'city_id' => $city->id,
            'locality_id' => $locality?->id,
            'provider_type_id' => $subtype?->provider_type_id,
            'provider_subtype_id' => $subtype?->id,
            'phone' => $user->phone,
            'whatsapp_number' => $user->phone,
            'email' => $user->email,
            'address' => $data['address'],
            'pincode' => $data['pincode'],
            'established_year' => $data['established_year'],
            'allow_call' => true,
            'allow_whatsapp' => true,
            'allow_enquiry' => true,
            'portfolio_status' => 'none',
            'source' => 'self_registration',
        ]);
        $provider->forceFill(['claimed_at' => now()->subDays(20), 'phone_verified_at' => now()->subDays(20), 'consent_at' => now()->subDays(20)]);
        $provider->save();

        $provider->categories()->sync([Category::where('slug', 'civil-contractors')->value('id') => ['is_primary' => true]]);
        $provider->zones()->sync(Zone::whereIn('slug', $data['zones'])->pluck('id'));

        Consent::create([
            'provider_id' => $provider->id,
            'consented_at' => now()->subDays(20),
            'method' => 'web_form',
            'scope' => ['business_name', 'description', 'locality', 'portfolio', 'contact_via_kattufy'],
            'policy_version' => 'v1',
            'given_by_name' => $user->name,
        ]);

        app(LifecycleService::class)->transition($provider, ProviderStatus::Consented, null, ['source' => 'demo_seed']);

        return $provider;
    }

    private function credential(Provider $provider, string $typeSlug, ?string $number, ?string $filename = null): Credential
    {
        $type = CredentialType::where('slug', $typeSlug)->firstOrFail();

        $credential = Credential::create([
            'provider_id' => $provider->id,
            'credential_type_id' => $type->id,
            'issuing_authority_id' => $type->issuing_authority_id,
            'credential_number' => $number,
            'holder_name' => $provider->business_name_en,
            'expiry_date' => $type->has_expiry ? now()->addYears(2)->startOfMonth() : null,
        ]);

        if ($filename) {
            $pdf = $this->samplePdf($type->name_en, $provider->business_name_en);
            $path = 'credentials/'.$provider->id.'/'.Str::uuid().'.pdf';
            Storage::disk('local')->put($path, $pdf);

            $credential->morphMany(Media::class, 'mediable')->create([
                'collection' => Media::COLLECTION_DOCUMENT,
                'visibility' => 'private',
                'original_disk' => 'local',
                'original_path' => $path,
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($pdf),
                'original_filename' => $filename,
                'checksum_sha256' => hash('sha256', $pdf),
                'processing_status' => 'done',
                'processed_at' => now(),
            ]);
        }

        return $credential;
    }

    /** A minimal one-page PDF clearly marked as a sample. */
    private function samplePdf(string $title, string $business): string
    {
        $lines = ['SAMPLE DOCUMENT - KATTUFY DEMO', $title, $business, 'Fictional data. Not a real document.'];
        $text = "BT /F1 20 Tf 60 760 Td 26 TL\n";
        foreach ($lines as $line) {
            $text .= '('.addcslashes($line, '()\\').") Tj T*\n";
        }
        $text .= 'ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($text)." >>\nstream\n".$text."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
