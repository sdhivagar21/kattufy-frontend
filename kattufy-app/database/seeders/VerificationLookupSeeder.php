<?php

namespace Database\Seeders;

use App\Modules\Directory\Models\Category;
use App\Modules\Geography\Models\State;
use App\Modules\Verification\Models\CredentialType;
use App\Modules\Verification\Models\IssuingAuthority;
use App\Modules\Verification\Models\VerificationRequirement;
use Illuminate\Database\Seeder;

/**
 * Issuing authorities, credential types and which categories require which
 * registration. Aadhaar is deliberately not a credential type.
 */
class VerificationLookupSeeder extends Seeder
{
    public function run(): void
    {
        $tn = State::where('code', 'TN')->value('id');

        $authorities = [
            'council-of-architecture' => ['Council of Architecture', 'இந்தியக் கட்டடக் கலைக் குழுமம்', null, 'https://www.coa.gov.in', 'https://www.coa.gov.in/index1.php?lang=1&level=1&sublinkid=1185&lid=1191'],
            'greater-chennai-corporation' => ['Greater Chennai Corporation', 'பெருநகர சென்னை மாநகராட்சி', $tn, 'https://chennaicorporation.gov.in', null],
            'income-tax-department' => ['Income Tax Department', 'வருமான வரித் துறை', null, 'https://www.incometax.gov.in', null],
            'election-commission' => ['Election Commission of India', 'இந்தியத் தேர்தல் ஆணையம்', null, 'https://voters.eci.gov.in', null],
            'transport-department' => ['Transport Department', 'போக்குவரத்துத் துறை', $tn, 'https://parivahan.gov.in', null],
            'passport-seva' => ['Passport Seva, Ministry of External Affairs', 'கடவுச்சீட்டு சேவை', null, 'https://www.passportindia.gov.in', null],
            'gst-network' => ['Goods and Services Tax Network', 'சரக்கு மற்றும் சேவை வரி வலையமைப்பு', null, 'https://www.gst.gov.in', 'https://services.gst.gov.in/services/searchtp'],
            'ministry-of-corporate-affairs' => ['Ministry of Corporate Affairs', 'பெருநிறுவன விவகார அமைச்சகம்', null, 'https://www.mca.gov.in', null],
        ];

        $ids = [];
        foreach ($authorities as $slug => [$en, $ta, $state, $web, $register]) {
            $ids[$slug] = IssuingAuthority::updateOrCreate(['slug' => $slug], [
                'name_en' => $en, 'name_ta' => $ta, 'state_id' => $state, 'website_url' => $web, 'register_url' => $register,
            ])->id;
        }

        // slug => [en, ta, authority, has_expiry, requires_document, number_pattern]
        $types = [
            'pan-card' => ['PAN', 'PAN', 'income-tax-department', false, false, '^[A-Z]{5}[0-9]{4}[A-Z]$'],
            'voter-id' => ['Voter ID', 'வாக்காளர் அடையாள அட்டை', 'election-commission', false, false, '^[A-Z]{3}[0-9]{7}$'],
            'driving-licence' => ['Driving licence', 'ஓட்டுநர் உரிமம்', 'transport-department', true, false, null],
            'passport' => ['Passport', 'கடவுச்சீட்டு', 'passport-seva', true, false, '^[A-Z][0-9]{7}$'],
            'address-proof' => ['Address proof', 'முகவரிச் சான்று', null, false, true, null],
            'company-registration-certificate' => ['Company registration certificate', 'நிறுவனப் பதிவுச் சான்றிதழ்', 'ministry-of-corporate-affairs', false, true, null],
            'gst-certificate' => ['GST registration certificate', 'GST பதிவுச் சான்றிதழ்', 'gst-network', false, true, null],
            'other-government-certificate' => ['Other government certificate', 'பிற அரசுச் சான்றிதழ்', null, false, true, null],
            'coa-registration' => ['Council of Architecture registration', 'கட்டடக் கலைக் குழுமப் பதிவு', 'council-of-architecture', true, true, null],
            'gcc-contractor-registration' => ['Contractor registration', 'ஒப்பந்ததாரர் பதிவு', 'greater-chennai-corporation', true, true, null],
        ];

        $order = 0;
        foreach ($types as $slug => [$en, $ta, $authority, $expiry, $document, $pattern]) {
            CredentialType::updateOrCreate(['slug' => $slug], [
                'name_en' => $en, 'name_ta' => $ta,
                'issuing_authority_id' => $authority ? $ids[$authority] : null,
                'has_expiry' => $expiry, 'requires_document' => $document, 'number_pattern' => $pattern,
                'is_active' => true, 'sort_order' => $order++,
            ]);
        }

        $requirements = [
            'architects' => 'coa-registration',
            'civil-contractors' => 'gcc-contractor-registration',
        ];

        foreach ($requirements as $categorySlug => $typeSlug) {
            $type = CredentialType::where('slug', $typeSlug)->first();
            VerificationRequirement::updateOrCreate(
                ['category_id' => Category::where('slug', $categorySlug)->value('id'), 'credential_type_id' => $type->id],
                ['issuing_authority_id' => $type->issuing_authority_id, 'is_required' => true, 'is_active' => true],
            );
        }
    }
}
