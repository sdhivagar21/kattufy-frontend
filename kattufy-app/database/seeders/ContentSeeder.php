<?php

namespace Database\Seeders;

use App\Modules\Content\Models\ContentPage;
use Illuminate\Database\Seeder;

/**
 * Guides and the ranking page come from the V1 preview. The legal pages were
 * placeholders there and stay clearly marked placeholders here: real policy
 * text must come from the owner before launch.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $content = json_decode(file_get_contents(database_path('seeders/data/content.json')), true)['pages'];

        foreach (['how-listings-are-ordered', 'choosing-an-architect', 'before-you-hire-a-contractor'] as $slug) {
            $page = $content[$slug];
            $hasTamil = filled($page['body_ta']) && $page['title_ta'] !== $page['title_en'];

            ContentPage::firstOrCreate(['slug' => $slug], [
                'page_type' => $page['type'],
                'title_en' => $page['title_en'],
                'title_ta' => $page['title_ta'],
                'body_en' => $page['body_en'],
                'body_ta' => $page['body_ta'],
                'ta_status' => $hasTamil ? 'reviewed' : 'draft',
                'status' => 'published',
                'published_at' => now()->subWeeks(2),
            ]);
        }

        $legal = [
            'privacy' => ['Privacy policy', 'தனியுரிமைக் கொள்கை'],
            'terms' => ['Terms of use', 'பயன்பாட்டு விதிமுறைகள்'],
            'disclaimer' => ['Disclaimer', 'பொறுப்புத் துறப்பு'],
        ];

        foreach ($legal as $slug => [$en, $ta]) {
            ContentPage::firstOrCreate(['slug' => $slug], [
                'page_type' => 'legal',
                'title_en' => $en,
                'title_ta' => $ta,
                'body_en' => "**Placeholder.** The final {$en} has not been written yet. It must be provided by Kattufy before launch and published from the admin panel (Website → Content pages).\n\nLast updated October 2026.",
                'body_ta' => "**தற்காலிக உள்ளடக்கம்.** இறுதியான {$ta} இன்னும் எழுதப்படவில்லை. அறிமுகத்துக்கு முன் கட்டுஃபை அதை வழங்கி, நிர்வாகப் பலகையிலிருந்து வெளியிட வேண்டும்.\n\nகடைசியாகப் புதுப்பித்தது அக்டோபர் 2026.",
                'ta_status' => 'reviewed',
                'status' => 'published',
                'published_at' => now()->subWeeks(2),
            ]);
        }
    }
}
