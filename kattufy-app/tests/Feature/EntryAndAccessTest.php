<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryAndAccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_splash_shows_brand_image_and_moves_on_to_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('images/kattufy-splash.jpg', false)
            ->assertSee('Where Trust Takes Shape.', false)
            ->assertSee(url('/login').'/', false);
    }

    public function test_tamil_splash_leads_to_tamil_login(): void
    {
        $this->get('/ta')->assertOk()->assertSee(url('/ta/login').'/', false);
    }

    public function test_login_page_has_brand_slogan_language_theme_and_one_entry_point(): void
    {
        $this->get('/login/')
            ->assertOk()
            ->assertSee('Where Trust Takes Shape.')
            ->assertSee('Find people you can trust with your home.')
            ->assertSee('No password: confirm your mobile number with a one-time code.')
            ->assertSee('தமிழ்')
            ->assertSee('themeToggle', false)
            ->assertDontSee('Super Admin login') // staff sign in through this same form
            ->assertSee('Create an account')
            // Compact demo notice instead of the old account list.
            ->assertSee('No real SMS or email is sent.')
            ->assertDontSee('Demo access')
            ->assertDontSee('Use this account')
            ->assertDontSee('Pending provider')
            ->assertDontSee('provider@kattufy.demo');
    }

    public function test_demo_hints_are_hidden_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/login/')->assertOk()
            ->assertDontSee('Demo mode')
            ->assertDontSee('No real SMS or email is sent.');
        $this->get('/login/?demo=user')->assertOk()->assertDontSee('user@kattufy.demo');
        $this->get('/admin/login/?demo=admin')->assertOk()
            ->assertDontSee('admin@kattufy.demo')
            ->assertDontSee('Demo mode');
    }

    public function test_tamil_pages_are_marked_and_translated(): void
    {
        $this->get('/ta/login/')
            ->assertOk()
            ->assertSee('<html lang="ta"', false)
            ->assertSee('கட்டுஃபையில் உள்நுழையுங்கள்')
            ->assertSee('hreflang="en"', false);
    }

    public function test_theme_is_applied_before_first_paint(): void
    {
        $this->get('/login/')->assertSee("localStorage.getItem('theme')", false);
    }

    public function test_guests_are_sent_to_login_from_member_pages(): void
    {
        foreach (['/user/dashboard', '/provider/dashboard', '/architects', '/search', '/select-role'] as $page) {
            $this->get($page.'/')->assertRedirect(url('/login').'/');
        }
    }

    public function test_urls_without_trailing_slash_redirect_permanently(): void
    {
        config(['kattufy.trailing_slash_redirect' => true]);

        $this->get('/login')->assertStatus(301)->assertRedirect(url('/login').'/');
    }

    public function test_legal_pages_are_public(): void
    {
        $this->get('/privacy/')->assertOk()->assertSee('Privacy policy');
        $this->get('/ta/terms/')->assertOk()->assertSee('பயன்பாட்டு விதிமுறைகள்');
    }

    public function test_every_english_interface_string_has_a_tamil_translation(): void
    {
        $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
            $out = [];
            foreach ($items as $key => $value) {
                if (is_array($value) && ! array_is_list($value)) {
                    $out += $flatten($value, $prefix.$key.'.');
                } else {
                    $out[$prefix.$key] = true;
                }
            }

            return $out;
        };

        foreach (glob(lang_path('en/*.php')) as $file) {
            $name = basename($file);
            $missing = array_keys(array_diff_key($flatten(require $file), $flatten(require lang_path('ta/'.$name))));
            $this->assertSame([], $missing, "Missing Tamil keys in {$name}");
        }
    }
}
