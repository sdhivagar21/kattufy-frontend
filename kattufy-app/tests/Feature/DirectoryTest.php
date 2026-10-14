<?php

namespace Tests\Feature;

use App\Modules\Enquiries\Models\ContactEvent;
use App\Modules\Providers\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginThroughOtp('9000000001', 'user@kattufy.demo');
    }

    public function test_category_listing_and_profile_pages_render(): void
    {
        $this->get('/building-a-house/')->assertOk()->assertSee('Architects');
        $this->get('/architects/')->assertOk()->assertSee('Live in Chennai');
        $this->get('/architects/chennai/')->assertOk()->assertSee('12 providers')->assertSee('Ezhil Architects');
        $this->get('/plumbers/chennai/')->assertOk()->assertSee('coming soon');
        $this->get('/ta/architects/chennai/')->assertOk()->assertSee('எழில் ஆர்கிடெக்ட்ஸ்');
    }

    public function test_profiles_never_print_provider_numbers(): void
    {
        $provider = Provider::where('slug', 'ezhil-architects')->first();

        $this->get('/architects/ezhil-architects/')->assertOk()
            ->assertSee('Ezhil Architects')
            ->assertSee('Council of Architecture')
            ->assertDontSee(substr($provider->phone, 3))
            ->assertDontSee($provider->email);
    }

    public function test_contact_reveal_returns_the_number_and_is_logged_once_a_day(): void
    {
        $provider = Provider::where('slug', 'ezhil-architects')->first();

        $this->postJson('/contact/'.$provider->id.'/reveal', ['action' => 'call'])
            ->assertOk()->assertJsonPath('tel', 'tel:'.$provider->phone)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson('/contact/'.$provider->id.'/reveal', ['action' => 'whatsapp'])->assertOk();

        $this->assertSame(1, ContactEvent::where('provider_id', $provider->id)->count());
    }

    public function test_search_by_text_and_pin_code(): void
    {
        $this->get('/search/?q=Ezhil')->assertOk()->assertSee('Ezhil Architects');
        $this->get('/search/?area=600042&category=architects')->assertOk()->assertSee('providers');
        $this->get('/search/?area=nowhere-at-all')->assertOk()->assertSee('could not find that area');
        $this->assertDatabaseHas('search_logs', ['query' => 'Ezhil']);
    }

    public function test_enquiry_is_saved_with_the_verified_number(): void
    {
        $this->post('/enquiry', [
            'name' => 'Demo User',
            'category_id' => \DB::table('categories')->where('slug', 'plumbers')->value('id'),
            'locality_id' => \DB::table('localities')->where('slug', 'adyar')->value('id'),
            'message' => 'Need a plumber for a new house next month.',
        ])->assertRedirect();

        $this->assertDatabaseHas('enquiries', ['phone' => '+919000000001', 'status' => 'new', 'message' => 'Need a plumber for a new house next month.']);
    }
}
