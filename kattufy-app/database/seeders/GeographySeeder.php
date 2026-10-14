<?php

namespace Database\Seeders;

use App\Modules\Geography\Models\City;
use App\Modules\Geography\Models\District;
use App\Modules\Geography\Models\Locality;
use App\Modules\Geography\Models\Pincode;
use App\Modules\Geography\Models\State;
use App\Modules\Geography\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Tamil Nadu → Chennai, its five service zones and the launch localities with PIN codes. */
class GeographySeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/geography.json')), true);

        $state = State::updateOrCreate(['code' => 'TN'], ['name_en' => 'Tamil Nadu', 'name_ta' => 'தமிழ்நாடு', 'slug' => 'tamil-nadu']);
        $district = District::updateOrCreate(['state_id' => $state->id, 'slug' => 'chennai'], ['name_en' => 'Chennai', 'name_ta' => 'சென்னை']);
        $city = City::updateOrCreate(['slug' => 'chennai'], ['district_id' => $district->id, 'name_en' => 'Chennai', 'name_ta' => 'சென்னை', 'latitude' => 13.0827, 'longitude' => 80.2707]);

        $zones = [];
        $order = 0;
        foreach ($data['zones'] as $nameEn => $nameTa) {
            $zones[$nameEn] = Zone::updateOrCreate(
                ['city_id' => $city->id, 'slug' => Str::slug($nameEn)],
                ['name_en' => $nameEn, 'name_ta' => $nameTa, 'sort_order' => $order++],
            );
        }

        foreach ($data['localities'] as $i => $row) {
            $locality = Locality::updateOrCreate(
                ['city_id' => $city->id, 'slug' => $row['slug']],
                [
                    'zone_id' => $zones[$row['zone']]->id,
                    'name_en' => $row['name_en'],
                    'name_ta' => $row['name_ta'] !== $row['name_en'] ? $row['name_ta'] : null,
                    'aliases' => $row['aliases'] ? implode(', ', $row['aliases']) : null,
                    'sort_order' => $i,
                ],
            );

            foreach ($row['pincodes'] as $code) {
                $pincode = Pincode::firstOrCreate(['code' => $code]);
                $locality->pincodes()->syncWithoutDetaching([$pincode->id]);
            }
        }
    }
}
