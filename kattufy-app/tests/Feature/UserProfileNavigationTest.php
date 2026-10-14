<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_account_menu_exposes_profile_actions_and_dashboard_has_no_profile_card(): void
    {
        $user = User::where('email', 'user@kattufy.demo')->firstOrFail();

        $this->actingAs($user)->get('/user/dashboard/')
            ->assertOk()
            ->assertSee('Your Account')
            ->assertSee('Demo User')
            ->assertSee('/user/profile/?edit=1', false)
            ->assertSee('/user/profile/?share=1', false)
            ->assertDontSee('id="account-title"', false);
    }

    public function test_profile_edit_and_share_menu_links_open_the_requested_interface(): void
    {
        $user = User::where('email', 'user@kattufy.demo')->firstOrFail();

        $this->actingAs($user)->get('/user/profile/?edit=1')
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false);

        $this->get('/user/profile/?share=1')
            ->assertOk()
            ->assertSee('Public customer profiles are not available yet')
            ->assertSee('Your phone number, email address and home address remain private');
    }

    public function test_profile_update_changes_only_account_fields_and_keeps_session(): void
    {
        $user = User::where('email', 'user@kattufy.demo')->firstOrFail();

        $this->actingAs($user)->put('/user/profile/', [
            'name' => 'Demo User Updated',
            'email' => 'updated-user@kattufy.demo',
            'profile_address' => '12 Example Street, Chennai',
        ])->assertRedirect(url('/user/profile').'/')
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Demo User Updated',
            'email' => 'updated-user@kattufy.demo',
            'profile_address' => '12 Example Street, Chennai',
        ]);
    }

    public function test_profile_rejects_invalid_email_and_ignores_mobile_number_changes(): void
    {
        $user = User::where('email', 'user@kattufy.demo')->firstOrFail();
        $originalPhone = $user->phone;

        $this->actingAs($user)->from('/user/profile/')->put('/user/profile/', [
            'name' => 'Demo User',
            'email' => 'not-an-email',
            'profile_address' => '',
            'phone' => '+919999999999',
        ])->assertSessionHasErrors('email');

        $this->assertSame($originalPhone, $user->fresh()->phone);
    }

    public function test_logout_from_the_account_flow_uses_the_existing_login_redirect(): void
    {
        $user = User::where('email', 'user@kattufy.demo')->firstOrFail();

        $this->actingAs($user)->post('/logout', ['locale' => 'en'])
            ->assertRedirect(url('/login').'/');

        $this->assertGuest();
    }
}
