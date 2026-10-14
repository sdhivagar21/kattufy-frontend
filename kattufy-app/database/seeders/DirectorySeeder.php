<?php

namespace Database\Seeders;

use App\Modules\Directory\Models\Category;
use App\Modules\Directory\Models\CategoryGroup;
use App\Modules\Directory\Models\ProviderSubtype;
use App\Modules\Directory\Models\ProviderType;
use Illuminate\Database\Seeder;

/** Build-stage groups, the nine launch categories and provider types (from the V1 preview). */
class DirectorySeeder extends Seeder
{
    public const GROUPS = [
        'building-a-house' => ['architects', 'structural-engineers', 'civil-contractors', 'plumbers', 'electricians'],
        'interiors-and-finishing' => ['interior-designers', 'carpenters', 'painters'],
        'materials-and-supplies' => ['suppliers'],
    ];

    public const TYPES = [
        'professional' => ['Professional', 'தொழில்முறை நிபுணர்', ['independent-architect' => ['Independent architect', 'தனிக் கட்டடக் கலைஞர்'], 'architecture-firm' => ['Architecture firm', 'கட்டடக் கலை நிறுவனம்'], 'interior-design-studio' => ['Interior design studio', 'உள் அலங்கார வடிவமைப்பு ஸ்டுடியோ']]],
        'contractor' => ['Contractor', 'ஒப்பந்ததாரர்', ['building-contractor' => ['Building contractor', 'கட்டட ஒப்பந்ததாரர்'], 'design-and-build-firm' => ['Design and build firm', 'வடிவமைத்துக் கட்டும் நிறுவனம்']]],
        'supplier' => ['Supplier', 'விற்பனையாளர்', ['retail-showroom' => ['Retail showroom', 'சில்லறை விற்பனைக் காட்சியகம்'], 'distributor' => ['Distributor', 'விநியோகஸ்தர்']]],
    ];

    public function run(): void
    {
        $text = json_decode(file_get_contents(database_path('seeders/data/categories.json')), true);
        $sort = 0;

        foreach (self::GROUPS as $groupSlug => $categories) {
            $group = CategoryGroup::updateOrCreate(['slug' => $groupSlug], [
                'name_en' => $text[$groupSlug]['en'][0],
                'name_ta' => $text[$groupSlug]['ta'][0],
                'description_en' => $text[$groupSlug]['en'][1],
                'description_ta' => $text[$groupSlug]['ta'][1],
                'sort_order' => array_search($groupSlug, array_keys(self::GROUPS)),
            ]);

            foreach ($categories as $slug) {
                [$nameEn, $descEn] = $text[$slug]['en'];
                [$nameTa, $descTa] = $text[$slug]['ta'];

                Category::updateOrCreate(['slug' => $slug], [
                    'category_group_id' => $group->id,
                    'name_en' => $nameEn,
                    'name_ta' => $nameTa,
                    'description_en' => $descEn,
                    // The preview showed English where no Tamil copy existed yet.
                    'description_ta' => $descTa !== $descEn ? $descTa : null,
                    'meta_description_en' => mb_substr($descEn, 0, 170),
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]);
            }
        }

        $typeOrder = 0;
        foreach (self::TYPES as $slug => [$en, $ta, $subtypes]) {
            $type = ProviderType::updateOrCreate(['slug' => $slug], ['name_en' => $en, 'name_ta' => $ta, 'sort_order' => $typeOrder++]);
            $subOrder = 0;
            foreach ($subtypes as $subSlug => [$subEn, $subTa]) {
                ProviderSubtype::updateOrCreate(['provider_type_id' => $type->id, 'slug' => $subSlug], ['name_en' => $subEn, 'name_ta' => $subTa, 'sort_order' => $subOrder++]);
            }
        }
    }
}
